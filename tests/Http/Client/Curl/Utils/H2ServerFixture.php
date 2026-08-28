<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

final class H2ServerFixture
{
    /** @var resource|null */
    private $process;

    /** @var array<int, resource> */
    private array $pipes;

    private function __construct(
        private readonly string $dir,
        private readonly string $portFile,
        private readonly string $captureFile,
        private readonly string $scriptFile,
        private readonly ?string $certFile,
        mixed $process,
        array $pipes,
        private readonly int $port,
        private readonly bool $tls
    )
    {
        $this->process = $process;
        $this->pipes = $pipes;
    }

    public static function start(
        bool $tls = false,
        bool $responseTrailers = false
    ): self
    {
        $dir = \sys_get_temp_dir()
            . '/smithy-curl-h2-test-' . \bin2hex(\random_bytes(6));
        if (!\mkdir($dir) && !\is_dir($dir)) {
            throw new \RuntimeException('Unable to create H2 test server dir.');
        }

        $scriptFile = $dir . '/server.php';
        $portFile = $dir . '/port';
        $captureFile = $dir . '/capture.json';
        $certFile = $tls ? $dir . '/server.pem' : null;
        if ($certFile !== null) {
            self::writeSelfSignedCertificate($certFile);
        }
        \file_put_contents($scriptFile, self::serverScript());

        $command = [
            PHP_BINARY,
            $scriptFile,
            $portFile,
            $captureFile,
            $tls ? 'tls' : 'cleartext',
            $certFile ?? '',
            $responseTrailers ? 'trailers' : 'standard'
        ];
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = \proc_open($command, $descriptorSpec, $pipes);
        if (!\is_resource($process)) {
            throw new \RuntimeException('Unable to start H2 test server.');
        }
        \fclose($pipes[0]);

        $port = LocalServerFixture::waitForPort(
            $portFile,
            $captureFile,
            $process,
            $pipes
        );

        return new self(
            $dir,
            $portFile,
            $captureFile,
            $scriptFile,
            $certFile,
            $process,
            $pipes,
            $port,
            $tls
        );
    }

    public function endpoint(): string
    {
        $scheme = $this->tls ? 'https' : 'http';

        return "{$scheme}://127.0.0.1:{$this->port}";
    }

    /**
     * @return array{
     *     protocol: string,
     *     streamId: int,
     *     body: string
     * }
     */
    public function capture(): array
    {
        $capture = LocalServerFixture::readCapture($this->captureFile);

        if (!isset($capture['body']) || !\is_string($capture['body'])) {
            throw new \RuntimeException('Invalid captured H2 body.');
        }

        return $capture;
    }

    public function stop(): void
    {
        if ($this->process !== null) {
            $status = \proc_get_status($this->process);
            if ($status['running']) {
                \proc_terminate($this->process);
            }
            \proc_close($this->process);
            $this->process = null;
        }

        foreach ($this->pipes as $pipe) {
            if (\is_resource($pipe)) {
                \fclose($pipe);
            }
        }

        $files = [
            $this->portFile,
            $this->captureFile,
            $this->scriptFile
        ];
        if ($this->certFile !== null) {
            $files[] = $this->certFile;
        }
        foreach ($files as $file) {
            if (\file_exists($file)) {
                \unlink($file);
            }
        }

        if (\is_dir($this->dir)) {
            \rmdir($this->dir);
        }
    }

    private static function writeSelfSignedCertificate(string $path): void
    {
        $key = \openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ]);
        if ($key === false) {
            throw new \RuntimeException('Unable to create test TLS key.');
        }

        $csr = \openssl_csr_new(['commonName' => '127.0.0.1'], $key);
        if ($csr === false) {
            throw new \RuntimeException('Unable to create test TLS CSR.');
        }

        $cert = \openssl_csr_sign($csr, null, $key, 1);
        if ($cert === false) {
            throw new \RuntimeException('Unable to sign test TLS cert.');
        }

        if (!\openssl_x509_export($cert, $certPem)
            || !\openssl_pkey_export($key, $keyPem)
        ) {
            throw new \RuntimeException('Unable to export test TLS cert.');
        }

        \file_put_contents($path, $certPem . $keyPem);
    }

    private static function serverScript(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

$portFile = $argv[1];
$captureFile = $argv[2];
$mode = $argv[3] ?? 'cleartext';
$certFile = $argv[4] ?? '';
$responseMode = $argv[5] ?? 'standard';

function failCapture(string $captureFile, string $message): void
{
    file_put_contents($captureFile, json_encode(['error' => $message]));
    exit(1);
}

function readExact($conn, int $length): ?string
{
    $data = '';
    while (strlen($data) < $length && !feof($conn)) {
        $chunk = fread($conn, $length - strlen($data));
        if ($chunk === false) {
            return null;
        }

        if ($chunk === '') {
            usleep(1000);
            continue;
        }
        $data .= $chunk;
    }

    return strlen($data) === $length ? $data : null;
}

function writeFrame(
    $conn,
    int $type,
    int $flags,
    int $streamId,
    string $payload
): void
{
    $length = strlen($payload);
    fwrite(
        $conn,
        chr(($length >> 16) & 0xff)
            . chr(($length >> 8) & 0xff)
            . chr($length & 0xff)
            . chr($type)
            . chr($flags)
            . pack('N', $streamId & 0x7fffffff)
            . $payload
    );
}

$context = $mode === 'tls'
    ? stream_context_create([
        'ssl' => [
            'local_cert' => $certFile,
            'allow_self_signed' => true,
            'verify_peer' => false,
            'alpn_protocols' => 'h2'
        ]
    ])
    : stream_context_create();

$server = @stream_socket_server(
    'tcp://127.0.0.1:0',
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    $context
);
if ($server === false) {
    failCapture($captureFile, $errstr);
}

$address = stream_socket_get_name($server, false);
$port = (int) substr(strrchr($address, ':'), 1);
file_put_contents($portFile, (string) $port);

$conn = @stream_socket_accept($server, 10);
if ($conn === false) {
    failCapture($captureFile, 'accept failed');
}
stream_set_timeout($conn, 10);

if ($mode === 'tls') {
    $enabled = @stream_socket_enable_crypto(
        $conn,
        true,
        STREAM_CRYPTO_METHOD_TLS_SERVER
    );
    if ($enabled !== true) {
        failCapture($captureFile, 'TLS handshake failed');
    }
}

$preface = readExact($conn, 24);
if ($preface !== "PRI * HTTP/2.0\r\n\r\nSM\r\n\r\n") {
    failCapture($captureFile, 'missing HTTP/2 client preface');
}

writeFrame($conn, 4, 0, 0, '');
$body = '';
$streamId = 0;

while (true) {
    $header = readExact($conn, 9);
    if ($header === null) {
        failCapture($captureFile, 'unexpected EOF');
    }

    $length = (ord($header[0]) << 16)
        | (ord($header[1]) << 8)
        | ord($header[2]);
    $type = ord($header[3]);
    $flags = ord($header[4]);
    $frameStreamId = unpack('N', substr($header, 5, 4))[1] & 0x7fffffff;
    $payload = $length === 0 ? '' : readExact($conn, $length);
    if ($payload === null) {
        failCapture($captureFile, 'truncated frame');
    }

    if ($type === 4) {
        if (($flags & 0x1) === 0) {
            writeFrame($conn, 4, 0x1, 0, '');
        }
        continue;
    }

    if ($type === 1) {
        $streamId = $frameStreamId;
        if (($flags & 0x1) !== 0) {
            break;
        }
        continue;
    }

    if ($type === 0) {
        if ($streamId === 0) {
            $streamId = $frameStreamId;
        }

        if (($flags & 0x8) !== 0 && $payload !== '') {
            $padding = ord($payload[0]);
            $payload = substr($payload, 1, strlen($payload) - $padding - 1);
        }
        $body .= $payload;
        if (($flags & 0x1) !== 0) {
            break;
        }
    }
}

file_put_contents($captureFile, json_encode([
    'protocol' => 'HTTP/2',
    'streamId' => $streamId,
    'body' => base64_encode($body)
]));

if ($responseMode === 'trailers') {
    writeFrame($conn, 1, 0x4, $streamId, "\x88");
    writeFrame($conn, 0, 0, $streamId, 'OK');
    usleep(500000);
    $trailers = "\x00\x0ax-checksum\x05first"
        . "\x00\x0ax-checksum\x06second"
        . "\x00\x0cx-request-id\x02h2";
    writeFrame($conn, 1, 0x5, $streamId, $trailers);
} else {
    writeFrame($conn, 1, 0x5, $streamId, "\x88");
}
writeFrame($conn, 7, 0, 0, pack('N', $streamId) . pack('N', 0));
usleep(100000);
fclose($conn);
fclose($server);
PHP;
    }
}
