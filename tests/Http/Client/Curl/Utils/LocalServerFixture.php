<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

final class LocalServerFixture
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
        mixed $process,
        array $pipes,
        private readonly int $port
    )
    {
        $this->process = $process;
        $this->pipes = $pipes;
    }

    public static function start(
        bool $closeAfterBodyStart = false,
        float $responseDelay = 0.0,
        ?string $responseMode = null
    ): self
    {
        $dir = \sys_get_temp_dir()
            . '/smithy-curl-test-' . \bin2hex(\random_bytes(6));
        if (!\mkdir($dir) && !\is_dir($dir)) {
            throw new \RuntimeException('Unable to create test server dir.');
        }

        $scriptFile = $dir . '/server.php';
        $portFile = $dir . '/port';
        $captureFile = $dir . '/capture.json';
        \file_put_contents($scriptFile, self::serverScript());

        $command = [
            PHP_BINARY,
            $scriptFile,
            $portFile,
            $captureFile,
            $responseMode
                ?? (
                    $closeAfterBodyStart
                        ? 'close-after-body-start'
                        : 'capture'
                ),
            (string) (int) ($responseDelay * 1000000)
        ];
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = \proc_open($command, $descriptorSpec, $pipes);
        if (!\is_resource($process)) {
            throw new \RuntimeException('Unable to start test server.');
        }
        \fclose($pipes[0]);

        $port = self::waitForPort(
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
            $process,
            $pipes,
            $port
        );
    }

    public function endpoint(): string
    {
        return "http://127.0.0.1:{$this->port}";
    }

    /**
     * @return array{
     *     method: string,
     *     path: string,
     *     protocol: string,
     *     headers: array<string, list<string>>,
     *     body: string
     * }
     */
    public function capture(): array
    {
        return self::readCapture($this->captureFile);
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
        foreach ($files as $file) {
            if (\file_exists($file)) {
                \unlink($file);
            }
        }

        if (\is_dir($this->dir)) {
            \rmdir($this->dir);
        }
    }

    /**
     * @param array<int, resource> $pipes
     */
    /**
     * @return array<string, mixed>
     */
    public static function readCapture(string $captureFile): array
    {
        $deadline = \microtime(true) + 5.0;
        while (!\file_exists($captureFile)) {
            if (\microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for capture.');
            }
            \usleep(10000);
        }

        $json = \file_get_contents($captureFile);
        if ($json === false) {
            throw new \RuntimeException('Unable to read capture.');
        }
        $capture = \json_decode($json, true);
        if (!\is_array($capture)) {
            throw new \RuntimeException('Invalid capture JSON.');
        }

        if (isset($capture['error'])) {
            throw new \RuntimeException((string) $capture['error']);
        }

        $capture['body'] = \base64_decode($capture['body'], true);
        if (!\is_string($capture['body'])) {
            throw new \RuntimeException('Invalid captured body.');
        }

        return $capture;
    }

    public static function waitForPort(
        string $portFile,
        string $captureFile,
        mixed $process,
        array $pipes
    ): int
    {
        $deadline = \microtime(true) + 5.0;
        while (true) {
            if (\file_exists($portFile)) {
                $portContents = \file_get_contents($portFile);
                $portText = \is_string($portContents)
                    ? \trim($portContents)
                    : '';
                if (\ctype_digit($portText)) {
                    $port = (int) $portText;
                    if ($port > 0) {
                        return $port;
                    }
                }
            }

            if (\file_exists($captureFile)) {
                $json = \file_get_contents($captureFile);
                $capture = \is_string($json) ? \json_decode($json, true) : null;
                if (\is_array($capture) && isset($capture['error'])) {
                    throw new \RuntimeException((string) $capture['error']);
                }
            }

            $status = \proc_get_status($process);
            if (!$status['running']) {
                $stderr = \stream_get_contents($pipes[2]);
                throw new \RuntimeException(
                    'Test server exited before binding: ' . $stderr
                );
            }

            if (\microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out starting test server.');
            }
            \usleep(10000);
        }
    }

    private static function serverScript(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

$portFile = $argv[1];
$captureFile = $argv[2];
$mode = $argv[3] ?? 'capture';
$responseDelay = (int) ($argv[4] ?? 0);

$server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if ($server === false) {
    file_put_contents($captureFile, json_encode(['error' => $errstr]));
    exit(1);
}

$address = stream_socket_get_name($server, false);
$port = (int) substr(strrchr($address, ':'), 1);
file_put_contents($portFile, (string) $port);

$conn = @stream_socket_accept($server, 10);
if ($conn === false) {
    file_put_contents($captureFile, json_encode(['error' => 'accept failed']));
    exit(1);
}
stream_set_timeout($conn, 10);

$head = '';
while (!str_contains($head, "\r\n\r\n")) {
    $chunk = fread($conn, 8192);
    if ($chunk === false || $chunk === '') {
        if (feof($conn)) {
            break;
        }
        usleep(1000);
        continue;
    }
    $head .= $chunk;
}

$parts = explode("\r\n\r\n", $head, 2);
$headerBlock = $parts[0] ?? '';
$body = $parts[1] ?? '';
$lines = explode("\r\n", $headerBlock);
$requestLine = array_shift($lines) ?? '';
$requestParts = explode(' ', $requestLine, 3);
$headers = [];

foreach ($lines as $line) {
    if (!str_contains($line, ':')) {
        continue;
    }
    [$name, $value] = explode(':', $line, 2);
    $headers[strtolower(trim($name))][] = trim($value);
}

if ($mode === 'reject-before-body') {
    file_put_contents($captureFile, json_encode([
        'method' => $requestParts[0] ?? '',
        'path' => $requestParts[1] ?? '',
        'protocol' => $requestParts[2] ?? '',
        'headers' => $headers,
        'body' => base64_encode($body)
    ]));
    fwrite(
        $conn,
        "HTTP/1.1 417 Expectation Failed\r\n"
            . "Content-Length: 0\r\n"
            . "Connection: close\r\n\r\n"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if (isset($headers['expect'])) {
    foreach ($headers['expect'] as $value) {
        if (strtolower($value) === '100-continue') {
            fwrite($conn, "HTTP/1.1 100 Continue\r\n\r\n");
            break;
        }
    }
}

if ($mode === 'close-after-body-start') {
    while ($body === '' && !feof($conn)) {
        $chunk = fread($conn, 8192);
        if ($chunk === false || $chunk === '') {
            usleep(1000);
            continue;
        }
        $body .= $chunk;
    }
    file_put_contents($captureFile, json_encode([
        'method' => $requestParts[0] ?? '',
        'path' => $requestParts[1] ?? '',
        'protocol' => $requestParts[2] ?? '',
        'headers' => $headers,
        'body' => base64_encode($body)
    ]));
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'reject-after-body-start') {
    while ($body === '' && !feof($conn)) {
        $chunk = fread($conn, 8192);
        if ($chunk === false || $chunk === '') {
            usleep(1000);
            continue;
        }
        $body .= $chunk;
    }
    file_put_contents($captureFile, json_encode([
        'method' => $requestParts[0] ?? '',
        'path' => $requestParts[1] ?? '',
        'protocol' => $requestParts[2] ?? '',
        'headers' => $headers,
        'body' => base64_encode($body)
    ]));
    fwrite(
        $conn,
        "HTTP/1.1 413 Content Too Large\r\n"
            . "Content-Length: 0\r\n"
            . "Connection: close\r\n\r\n"
    );
    usleep(100000);
    fclose($conn);
    fclose($server);
    exit(0);
}

if (isset($headers['content-length'])) {
    $length = (int) $headers['content-length'][0];
    while (strlen($body) < $length && !feof($conn)) {
        $chunk = fread($conn, $length - strlen($body));
        if ($chunk === false || $chunk === '') {
            usleep(1000);
            continue;
        }
        $body .= $chunk;
    }
    $body = substr($body, 0, $length);
} elseif (isset($headers['transfer-encoding'])
    && strtolower($headers['transfer-encoding'][0]) === 'chunked'
) {
    $decoded = '';
    while (true) {
        while (($pos = strpos($body, "\r\n")) === false && !feof($conn)) {
            $chunk = fread($conn, 8192);
            if ($chunk === false || $chunk === '') {
                usleep(1000);
                continue;
            }
            $body .= $chunk;
        }
        $pos = strpos($body, "\r\n");
        if ($pos === false) {
            break;
        }
        $line = substr($body, 0, $pos);
        $body = substr($body, $pos + 2);
        $size = hexdec(trim(explode(';', $line, 2)[0]));
        if ($size === 0) {
            break;
        }
        while (strlen($body) < $size + 2 && !feof($conn)) {
            $chunk = fread($conn, $size + 2 - strlen($body));
            if ($chunk === false || $chunk === '') {
                usleep(1000);
                continue;
            }
            $body .= $chunk;
        }
        $decoded .= substr($body, 0, $size);
        $body = substr($body, $size + 2);
    }
    $body = $decoded;
}

file_put_contents($captureFile, json_encode([
    'method' => $requestParts[0] ?? '',
    'path' => $requestParts[1] ?? '',
    'protocol' => $requestParts[2] ?? '',
    'headers' => $headers,
    'body' => base64_encode($body)
]));

if ($responseDelay > 0) {
    usleep($responseDelay);
}

if (in_array(
    $mode,
    [
        'valid-stream-response',
        'invalid-status-response',
        'invalid-reason-response',
        'invalid-header-response'
    ],
    true
)) {
    $statusLine = match ($mode) {
        'invalid-status-response' => "HTTP/1.1 600 Invalid\r\n",
        'invalid-reason-response' => "HTTP/1.1 200 Invalid\x01Reason\r\n",
        default => "HTTP/1.1 200 OK\r\n"
    };
    $extraHeader = $mode === 'invalid-header-response'
        ? "Bad Name: value\r\n"
        : '';
    fwrite(
        $conn,
        $statusLine
            . $extraHeader
            . "Content-Length: 2\r\n"
            . "Connection: close\r\n\r\nOK"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'empty-response') {
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nContent-Length: 0\r\n"
            . "Connection: close\r\n\r\n"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'truncate-response') {
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nContent-Length: 10\r\n"
            . "Connection: close\r\n\r\npart"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'slow-response') {
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nContent-Length: 11\r\n"
            . "Connection: close\r\n\r\nfirst"
    );
    fflush($conn);
    usleep(500000);
    fwrite($conn, 'second');
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'stream-response') {
    $responseBody = str_repeat('0123456789abcdef', 8192);
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nContent-Length: "
            . strlen($responseBody)
            . "\r\nConnection: close\r\n\r\n"
    );
    foreach (str_split($responseBody, 8192) as $responseChunk) {
        fwrite($conn, $responseChunk);
        fflush($conn);
        usleep(1000);
    }
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'large-response') {
    $responseBody = str_repeat('0123456789abcdef', 49152);
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nContent-Length: "
            . strlen($responseBody)
            . "\r\nConnection: close\r\n\r\n"
    );
    foreach (str_split($responseBody, 64 * 1024) as $responseChunk) {
        fwrite($conn, $responseChunk);
    }
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'threshold-response') {
    $responseBody = str_repeat('0123456789abcdef', 65536);
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nContent-Length: "
            . strlen($responseBody)
            . "\r\nConnection: close\r\n\r\n"
    );
    foreach (str_split($responseBody, 64 * 1024) as $responseChunk) {
        fwrite($conn, $responseChunk);
    }
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'above-threshold-response') {
    $responseBody = str_repeat('0123456789abcdef', 65537);
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nContent-Length: "
            . strlen($responseBody)
            . "\r\nConnection: close\r\n\r\n"
    );
    foreach (str_split($responseBody, 64 * 1024) as $responseChunk) {
        fwrite($conn, $responseChunk);
    }
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'unknown-length-response') {
    $responseBody = str_repeat('0123456789abcdef', 4096);
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\nConnection: close\r\n\r\n"
    );
    fwrite($conn, $responseBody);
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'response-trailers') {
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\n"
            . "Transfer-Encoding: chunked\r\n"
            . "Trailer: X-Checksum\r\n"
            . "Connection: close\r\n\r\n"
            . "5\r\nfirst\r\n"
            . "6\r\nsecond\r\n"
    );
    fflush($conn);
    usleep(500000);
    fwrite(
        $conn,
        "0\r\n"
            . "X-Checksum: first\r\n"
            . "x-checksum: second\r\n\r\n"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'redirect-response') {
    fwrite(
        $conn,
        "HTTP/1.1 302 Found\r\n"
            . "Location: /next\r\n"
            . "Content-Length: 0\r\n"
            . "Connection: close\r\n\r\n"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'large-head-response') {
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\n"
            . "Content-Length: 1048576\r\n"
            . "Connection: close\r\n\r\n"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if ($mode === 'discard-reuse-response') {
    $responseBody = str_repeat('d', 128 * 1024);
    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\n"
            . "Transfer-Encoding: chunked\r\n"
            . "Trailer: X-Discarded\r\n"
            . "Connection: keep-alive\r\n\r\n"
    );
    foreach (str_split($responseBody, 8192) as $responseChunk) {
        fwrite(
            $conn,
            dechex(strlen($responseChunk))
                . "\r\n"
                . $responseChunk
                . "\r\n"
        );
    }
    fwrite($conn, "0\r\nX-Discarded: complete\r\n\r\n");
    fflush($conn);

    $nextHead = '';
    while (!str_contains($nextHead, "\r\n\r\n")) {
        $chunk = fread($conn, 8192);
        if ($chunk === false || $chunk === '') {
            if (feof($conn)) {
                break;
            }
            usleep(1000);
            continue;
        }
        $nextHead .= $chunk;
    }

    if (!str_contains($nextHead, "\r\n\r\n")) {
        fclose($conn);
        fclose($server);
        exit(1);
    }

    fwrite(
        $conn,
        "HTTP/1.1 200 OK\r\n"
            . "Content-Length: 2\r\n"
            . "Connection: close\r\n\r\nOK"
    );
    fclose($conn);
    fclose($server);
    exit(0);
}

if (in_array(
    $mode,
    [
        'gzip-response',
        'tiny-gzip-response',
        'gzip-bomb-response',
        'gzip-205-bomb-response',
        'gzip-205-memory-bomb-response',
        'slow-gzip-response',
        'chunked-gzip-response',
        'deflate-response'
    ],
    true
)) {
    $responseBody = match ($mode) {
        'gzip-bomb-response',
        'gzip-205-bomb-response' => str_repeat('a', 12 * 1024 * 1024),
        'tiny-gzip-response' => 'decoded',
        default => str_repeat('decoded-body-', 1024)
    };
    $encoding = $mode === 'deflate-response'
        ? 'deflate'
        : 'gzip';
    if ($mode === 'gzip-205-memory-bomb-response') {
        $context = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]);
        if ($context === false) {
            fclose($conn);
            fclose($server);
            exit(1);
        }
        $encodedBody = '';
        $chunk = str_repeat('a', 1024 * 1024);
        for ($i = 0; $i < 128; $i++) {
            $encodedChunk = deflate_add(
                $context,
                $chunk,
                $i === 127 ? ZLIB_FINISH : ZLIB_NO_FLUSH
            );
            if ($encodedChunk === false) {
                fclose($conn);
                fclose($server);
                exit(1);
            }
            $encodedBody .= $encodedChunk;
        }
    } else {
        $encodedBody = $encoding === 'deflate'
            ? gzcompress($responseBody)
            : gzencode($responseBody);
    }
    if (!is_string($encodedBody)) {
        fclose($conn);
        fclose($server);
        exit(1);
    }

    if ($mode === 'chunked-gzip-response') {
        fwrite(
            $conn,
            "HTTP/1.1 200 OK\r\n"
                . "Content-Encoding: gzip\r\n"
                . "Transfer-Encoding: chunked\r\n"
                . "Connection: close\r\n\r\n"
        );
        foreach (str_split($encodedBody, 97) as $encodedChunk) {
            fwrite(
                $conn,
                dechex(strlen($encodedChunk))
                    . "\r\n"
                    . $encodedChunk
                    . "\r\n"
            );
        }
        fwrite($conn, "0\r\n\r\n");
        fclose($conn);
        fclose($server);
        exit(0);
    }

    $statusLine = in_array(
        $mode,
        ['gzip-205-bomb-response', 'gzip-205-memory-bomb-response'],
        true
    )
        ? 'HTTP/1.1 205 Reset Content'
        : 'HTTP/1.1 200 OK';
    fwrite(
        $conn,
        "{$statusLine}\r\n"
            . "Content-Encoding: {$encoding}\r\n"
            . "Content-Length: "
            . strlen($encodedBody)
            . "\r\nConnection: close\r\n\r\n"
    );
    fflush($conn);
    if ($mode === 'slow-gzip-response') {
        usleep(500000);
    }
    fwrite($conn, $encodedBody);
    fclose($conn);
    fclose($server);
    exit(0);
}

fwrite(
    $conn,
    "HTTP/1.1 200 OK\r\nContent-Length: 2\r\nConnection: close\r\n\r\nOK"
);
fclose($conn);
fclose($server);
PHP;
    }
}
