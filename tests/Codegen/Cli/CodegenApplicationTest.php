<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Cli\CodegenApplication;

#[CoversClass(CodegenApplication::class)]
final class CodegenApplicationTest extends TestCase
{
    /** @var list<string> */
    private array $paths = [];

    protected function tearDown(): void
    {
        foreach ($this->paths as $path) {
            $this->removePath($path);
        }
    }

    public function testReturnsSuccessAndWritesGeneratedFiles(): void
    {
        $outputDirectory = $this->temporaryPath('output');
        [$application, $stdout, $stderr] = $this->application();

        $status = $application->run([
            'smithy-php-codegen',
            __DIR__ . '/../../fixtures/defaults-model.json',
            '--namespace=Smithy\\Generated',
            '--output=' . $outputDirectory,
            '--organization=flat'
        ]);

        $this->assertSame(CodegenApplication::SUCCESS, $status);
        $this->assertFileExists(
            $outputDirectory . DIRECTORY_SEPARATOR . 'Settings.php'
        );
        $this->assertStringContainsString(
            'Generated ',
            $this->contents($stdout)
        );
        $this->assertSame('', $this->contents($stderr));
    }

    public function testGeneratesSelectedOperationsFromCommandLine(): void
    {
        $outputDirectory = $this->temporaryPath('output');
        [$application, $stdout, $stderr] = $this->application();

        $status = $application->run([
            'smithy-php-codegen',
            __DIR__ . '/../../fixtures/trimmed-client-model.json',
            '--namespace=Smithy\\PetStore',
            '--output=' . $outputDirectory,
            '--organization=flat',
            '--operations=GetPet'
        ]);

        $this->assertSame(CodegenApplication::SUCCESS, $status);
        $this->assertFileExists(
            $outputDirectory . '/GetPetOperation.php'
        );
        $this->assertFileDoesNotExist(
            $outputDirectory . '/ListPetsOperation.php'
        );
        $this->assertSame('', $this->contents($stderr));
    }

    public function testReturnsInvalidUsageForUnknownOption(): void
    {
        [$application, $stdout, $stderr] = $this->application();

        $status = $application->run([
            'smithy-php-codegen',
            'model.json',
            '--unknown=value'
        ]);

        $this->assertSame(CodegenApplication::INVALID_USAGE, $status);
        $this->assertSame('', $this->contents($stdout));
        $this->assertStringContainsString(
            'Unknown option: --unknown',
            $this->contents($stderr)
        );
    }

    public function testReturnsInvalidUsageForMissingOptionValue(): void
    {
        [$application, $stdout, $stderr] = $this->application();

        $status = $application->run([
            'smithy-php-codegen',
            'model.json',
            '--namespace'
        ]);

        $this->assertSame(CodegenApplication::INVALID_USAGE, $status);
        $this->assertSame('', $this->contents($stdout));
        $this->assertStringContainsString(
            '--namespace requires a value',
            $this->contents($stderr)
        );
    }

    public function testRejectsConfigCombinedWithDirectSettings(): void
    {
        $configPath = $this->writeConfig([
            'namespace' => 'Smithy\\Generated',
            'outputDir' => $this->temporaryPath('output')
        ]);
        [$application, $stdout, $stderr] = $this->application();

        $status = $application->run([
            'smithy-php-codegen',
            __DIR__ . '/../../fixtures/defaults-model.json',
            '--config=' . $configPath,
            '--namespace=Smithy\\Other'
        ]);

        $this->assertSame(CodegenApplication::INVALID_USAGE, $status);
        $this->assertSame('', $this->contents($stdout));
        $this->assertStringContainsString(
            '--config cannot be combined with direct settings',
            $this->contents($stderr)
        );
    }

    public function testRejectsUnknownConfigSetting(): void
    {
        $configPath = $this->writeConfig([
            'namespace' => 'Smithy\\Generated',
            'outputDir' => $this->temporaryPath('output'),
            'unknown' => true
        ]);
        [$application, $stdout, $stderr] = $this->application();

        $status = $application->run([
            'smithy-php-codegen',
            __DIR__ . '/../../fixtures/defaults-model.json',
            '--config=' . $configPath
        ]);

        $this->assertSame(CodegenApplication::INVALID_USAGE, $status);
        $this->assertSame('', $this->contents($stdout));
        $this->assertStringContainsString(
            'Unknown codegen setting: unknown',
            $this->contents($stderr)
        );
    }

    public function testPackagedCommandIsExecutable(): void
    {
        $command = __DIR__ . '/../../../bin/smithy-php-codegen';

        $this->assertFileExists($command);
        [$status, $stdout, $stderr] = $this->runPhpCommand(
            $command,
            '--help'
        );

        $this->assertSame(0, $status);
        $this->assertStringContainsString('Usage:', $stdout);
        $this->assertSame('', $stderr);
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->assertTrue(\is_executable($command));
        }
    }

    /**
     * @return array{CodegenApplication, resource, resource}
     */
    private function application(): array
    {
        $stdout = \fopen('php://memory', 'w+');
        $stderr = \fopen('php://memory', 'w+');
        if ($stdout === false || $stderr === false) {
            throw new \RuntimeException('Could not open test streams');
        }

        return [
            new CodegenApplication($stdout, $stderr),
            $stdout,
            $stderr
        ];
    }

    /**
     * @param resource $stream
     */
    private function contents(mixed $stream): string
    {
        \rewind($stream);
        $contents = \stream_get_contents($stream);

        return $contents === false ? '' : $contents;
    }

    /**
     * @return array{int, string, string}
     */
    private function runPhpCommand(string $path, string $argument): array
    {
        $process = \proc_open(
            [PHP_BINARY, $path, $argument],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );
        if (!\is_resource($process)) {
            throw new \RuntimeException('Could not start PHP process');
        }

        $stdout = \stream_get_contents($pipes[1]);
        $stderr = \stream_get_contents($pipes[2]);
        \fclose($pipes[1]);
        \fclose($pipes[2]);
        $status = \proc_close($process);

        return [
            $status,
            $stdout === false ? '' : $stdout,
            $stderr === false ? '' : $stderr
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function writeConfig(array $config): string
    {
        $path = $this->temporaryPath('config.json');
        $result = \file_put_contents(
            $path,
            \json_encode($config, JSON_THROW_ON_ERROR)
        );
        if ($result === false) {
            throw new \RuntimeException('Could not write test config');
        }

        return $path;
    }

    private function temporaryPath(string $suffix): string
    {
        $path = \sys_get_temp_dir()
            . '/smithy-codegen-'
            . \bin2hex(\random_bytes(6))
            . '-'
            . $suffix;
        $this->paths[] = $path;

        return $path;
    }

    /**
     * @return void
     */
    private function removePath(string $path): void
    {
        if (\is_file($path)) {
            \unlink($path);

            return;
        }
        if (!\is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                \rmdir($file->getPathname());
            } else {
                \unlink($file->getPathname());
            }
        }
        \rmdir($path);
    }
}
