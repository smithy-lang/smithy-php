<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Generator\GeneratedFile;
use Smithy\Codegen\Generator\GeneratedOutputPublisher;
use Smithy\Codegen\Generator\SchemasGenerator;

#[CoversClass(GeneratedOutputPublisher::class)]
final class GeneratedOutputPublisherTest extends TestCase
{
    private string $testRoot;

    protected function setUp(): void
    {
        $temporaryRoot = \realpath(\sys_get_temp_dir());
        if ($temporaryRoot === false) {
            self::fail('Unable to resolve the temporary directory.');
        }

        $this->testRoot = $temporaryRoot
            . DIRECTORY_SEPARATOR
            . 'smithy-php-output-publisher-'
            . \bin2hex(\random_bytes(8));
        \mkdir($this->testRoot);
    }

    protected function tearDown(): void
    {
        $this->removeTestDirectory($this->testRoot);
    }

    public function testReplacesManagedOutputAndRemovesStaleFiles(): void
    {
        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($outputDirectory);
        \file_put_contents(
            $outputDirectory . '/Schemas.php',
            $this->schemasSource('old schemas')
        );
        \file_put_contents($outputDirectory . '/Stale.php', 'stale');

        (new GeneratedOutputPublisher())->publish(
            [
                new GeneratedFile(
                    $outputDirectory . '/Schemas.php',
                    $this->schemasSource('new schemas')
                ),
                new GeneratedFile(
                    $outputDirectory . '/Shape/Current.php',
                    'current'
                )
            ],
            $outputDirectory
        );

        self::assertSame(
            $this->schemasSource('new schemas'),
            \file_get_contents($outputDirectory . '/Schemas.php')
        );
        self::assertSame(
            'current',
            \file_get_contents(
                $outputDirectory . '/Shape/Current.php'
            )
        );
        self::assertFileDoesNotExist(
            $outputDirectory . '/Stale.php'
        );
        self::assertSame(
            ['generated'],
            $this->directoryEntries($this->testRoot)
        );
    }

    public function testRestoresExistingOutputWhenPublicationFails(): void
    {
        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($outputDirectory);
        \file_put_contents(
            $outputDirectory . '/Schemas.php',
            $this->schemasSource('old schemas')
        );
        \file_put_contents($outputDirectory . '/Existing.php', 'existing');

        $moveCount = 0;
        $moveDirectory = static function (
            string $source,
            string $destination
        ) use (&$moveCount): bool {
            $moveCount++;
            if ($moveCount === 2) {
                return false;
            }

            return \rename($source, $destination);
        };

        try {
            (new GeneratedOutputPublisher($moveDirectory))->publish(
                [
                    new GeneratedFile(
                        $outputDirectory . '/Schemas.php',
                        $this->schemasSource('new schemas')
                    ),
                    new GeneratedFile(
                        $outputDirectory . '/Current.php',
                        'current'
                    )
                ],
                $outputDirectory
            );
            self::fail('Expected generated output publication to fail.');
        } catch (\RuntimeException $error) {
            self::assertSame(
                'Unable to replace generated output; snapshot restored.',
                $error->getMessage()
            );
        }

        self::assertSame(
            $this->schemasSource('old schemas'),
            \file_get_contents($outputDirectory . '/Schemas.php')
        );
        self::assertSame(
            'existing',
            \file_get_contents($outputDirectory . '/Existing.php')
        );
        self::assertFileDoesNotExist(
            $outputDirectory . '/Current.php'
        );
        self::assertSame(
            ['generated'],
            $this->directoryEntries($this->testRoot)
        );
    }

    public function testRefusesNonemptyUnmanagedOutput(): void
    {
        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($outputDirectory);
        \file_put_contents($outputDirectory . '/Manual.php', 'manual');

        try {
            (new GeneratedOutputPublisher())->publish(
                [
                    new GeneratedFile(
                        $outputDirectory . '/Schemas.php',
                        'schemas'
                    )
                ],
                $outputDirectory
            );
            self::fail('Expected unmanaged output to be rejected.');
        } catch (\LogicException $error) {
            self::assertStringContainsString(
                'without a generated Schemas.php registry',
                $error->getMessage()
            );
        }

        self::assertSame(
            'manual',
            \file_get_contents($outputDirectory . '/Manual.php')
        );
    }

    public function testRefusesHandwrittenSchemasFile(): void
    {
        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($outputDirectory);
        \file_put_contents(
            $outputDirectory . '/Schemas.php',
            '<?php final class Schemas {}'
        );
        \file_put_contents($outputDirectory . '/Manual.php', 'manual');

        try {
            (new GeneratedOutputPublisher())->publish(
                [
                    new GeneratedFile(
                        $outputDirectory . '/Schemas.php',
                        $this->schemasSource('generated')
                    )
                ],
                $outputDirectory
            );
            self::fail('Expected handwritten output to be rejected.');
        } catch (\LogicException $error) {
            self::assertStringContainsString(
                'without a generated Schemas.php registry',
                $error->getMessage()
            );
        }

        self::assertSame(
            'manual',
            \file_get_contents($outputDirectory . '/Manual.php')
        );
        self::assertSame(
            '<?php final class Schemas {}',
            \file_get_contents($outputDirectory . '/Schemas.php')
        );
    }

    public function testRejectsDuplicateGeneratedPaths(): void
    {
        $outputDirectory = $this->testRoot . '/generated';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Duplicate generated output path: Schemas.php'
        );

        (new GeneratedOutputPublisher())->publish(
            [
                new GeneratedFile(
                    $outputDirectory . '/Schemas.php',
                    'first'
                ),
                new GeneratedFile(
                    $outputDirectory . '/Schemas.php',
                    'second'
                )
            ],
            $outputDirectory
        );
    }

    public function testNormalizesConfiguredOutputBeforePublishing(): void
    {
        $configuredOutput = $this->testRoot
            . '/missing/../generated';
        $resolvedOutput = $this->testRoot . '/generated';

        (new GeneratedOutputPublisher())->publish(
            [
                new GeneratedFile(
                    $configuredOutput . '/Schemas.php',
                    $this->schemasSource('schemas')
                )
            ],
            $configuredOutput
        );

        self::assertSame(
            $this->schemasSource('schemas'),
            \file_get_contents($resolvedOutput . '/Schemas.php')
        );
        self::assertDirectoryDoesNotExist($this->testRoot . '/missing');
    }

    public function testPreservesOutputDirectoryPermissions(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped(
                'Windows does not expose POSIX directory permissions.'
            );
        }

        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($outputDirectory, 0700);
        \chmod($outputDirectory, 0700);
        \file_put_contents(
            $outputDirectory . '/Schemas.php',
            $this->schemasSource('old schemas')
        );

        $outputMetadata = \stat($outputDirectory);
        self::assertNotFalse($outputMetadata);
        $stagingMode = null;
        $stagingOwner = null;
        $stagingGroup = null;
        $moveCount = 0;
        $moveDirectory = static function (
            string $source,
            string $destination
        ) use (
            &$moveCount,
            &$stagingMode,
            &$stagingOwner,
            &$stagingGroup
        ): bool {
            $moveCount++;
            if ($moveCount === 2) {
                $metadata = \stat($source);
                if ($metadata !== false) {
                    $stagingMode = $metadata['mode'] & 07777;
                    $stagingOwner = $metadata['uid'];
                    $stagingGroup = $metadata['gid'];
                }
            }

            return \rename($source, $destination);
        };

        (new GeneratedOutputPublisher($moveDirectory))->publish(
            [
                new GeneratedFile(
                    $outputDirectory . '/Schemas.php',
                    $this->schemasSource('new schemas')
                )
            ],
            $outputDirectory
        );

        $permissions = \fileperms($outputDirectory);
        self::assertNotFalse($permissions);
        self::assertSame(0700, $stagingMode);
        self::assertSame(0700, $permissions & 07777);
        self::assertSame($outputMetadata['uid'], $stagingOwner);
        self::assertSame($outputMetadata['gid'], $stagingGroup);
        $publishedMetadata = \stat($outputDirectory);
        self::assertNotFalse($publishedMetadata);
        self::assertSame(
            $outputMetadata['uid'],
            $publishedMetadata['uid']
        );
        self::assertSame(
            $outputMetadata['gid'],
            $publishedMetadata['gid']
        );
    }

    public function testAppliesUmaskToNewOutputDirectory(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped(
                'Windows does not expose POSIX directory permissions.'
            );
        }

        $outputDirectory = $this->testRoot . '/generated';
        $previousUmask = \umask(0077);
        try {
            (new GeneratedOutputPublisher())->publish(
                [
                    new GeneratedFile(
                        $outputDirectory . '/Schemas.php',
                        $this->schemasSource('schemas')
                    )
                ],
                $outputDirectory
            );
        } finally {
            \umask($previousUmask);
        }

        $permissions = \fileperms($outputDirectory);
        self::assertNotFalse($permissions);
        self::assertSame(0700, $permissions & 07777);
    }

    public function testKeepsLexicalPathAcrossExistingSymlink(): void
    {
        if (!\function_exists('symlink')) {
            self::markTestSkipped('Symbolic links are unavailable.');
        }

        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($outputDirectory);
        \mkdir($outputDirectory . '/Actual');
        \file_put_contents(
            $outputDirectory . '/Schemas.php',
            $this->schemasSource('old schemas')
        );
        if (!@\symlink(
            $outputDirectory . '/Actual',
            $outputDirectory . '/Shape'
        )) {
            self::markTestSkipped(
                'Unable to create a directory symlink.'
            );
        }

        (new GeneratedOutputPublisher())->publish(
            [
                new GeneratedFile(
                    $outputDirectory . '/Schemas.php',
                    $this->schemasSource('new schemas')
                ),
                new GeneratedFile(
                    $outputDirectory . '/Shape/Foo.php',
                    'foo'
                )
            ],
            $outputDirectory
        );

        self::assertFileExists($outputDirectory . '/Shape/Foo.php');
        self::assertFalse(\is_link($outputDirectory . '/Shape'));
        self::assertDirectoryDoesNotExist(
            $outputDirectory . '/Actual'
        );
    }

    public function testReplacesDanglingSymlinkInManagedOutput(): void
    {
        if (!\function_exists('symlink')) {
            self::markTestSkipped('Symbolic links are unavailable.');
        }

        $outputDirectory = $this->testRoot . '/generated';
        $targetDirectory = $outputDirectory . '/Missing';
        \mkdir($outputDirectory);
        \mkdir($targetDirectory);
        \file_put_contents(
            $outputDirectory . '/Schemas.php',
            $this->schemasSource('old schemas')
        );
        if (!@\symlink(
            $targetDirectory,
            $outputDirectory . '/Shape'
        )) {
            self::markTestSkipped(
                'Unable to create a directory symlink.'
            );
        }
        \rmdir($targetDirectory);

        (new GeneratedOutputPublisher())->publish(
            [
                new GeneratedFile(
                    $outputDirectory . '/Schemas.php',
                    $this->schemasSource('new schemas')
                ),
                new GeneratedFile(
                    $outputDirectory . '/Shape/Foo.php',
                    'foo'
                )
            ],
            $outputDirectory
        );

        self::assertFileExists($outputDirectory . '/Shape/Foo.php');
        self::assertFalse(\is_link($outputDirectory . '/Shape'));
    }

    public function testAcceptsSameNamespaceSchemaReferences(): void
    {
        foreach ([
            'Smithy\\Core\\Serde',
            'Smithy\\Core\\Shape'
        ] as $index => $namespace) {
            $outputDirectory = $this->testRoot
                . "/generated-{$index}";
            \mkdir($outputDirectory);
            \file_put_contents(
                $outputDirectory . '/Schemas.php',
                $this->schemasSource('old schemas', $namespace)
            );

            (new GeneratedOutputPublisher())->publish(
                [
                    new GeneratedFile(
                        $outputDirectory . '/Schemas.php',
                        $this->schemasSource('new schemas', $namespace)
                    )
                ],
                $outputDirectory
            );

            self::assertSame(
                $this->schemasSource('new schemas', $namespace),
                \file_get_contents($outputDirectory . '/Schemas.php')
            );
        }
    }

    public function testReportsPostCommitBackupCleanupFailure(): void
    {
        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($outputDirectory);
        \file_put_contents(
            $outputDirectory . '/Schemas.php',
            $this->schemasSource('old schemas')
        );
        \file_put_contents($outputDirectory . '/Existing.php', 'existing');
        $directoryRemover = static function (
            string $_directory,
            string $_parent
        ): void {
            throw new \RuntimeException('Forced cleanup failure.');
        };

        try {
            (new GeneratedOutputPublisher(
                directoryRemover: $directoryRemover
            ))->publish(
                [
                    new GeneratedFile(
                        $outputDirectory . '/Schemas.php',
                        $this->schemasSource('new schemas')
                    )
                ],
                $outputDirectory
            );
            self::fail('Expected generated backup cleanup to fail.');
        } catch (\RuntimeException $error) {
            self::assertStringContainsString(
                'Generated output was published, but backup cleanup failed',
                $error->getMessage()
            );
        }

        self::assertSame(
            $this->schemasSource('new schemas'),
            \file_get_contents($outputDirectory . '/Schemas.php')
        );
        self::assertFileDoesNotExist(
            $outputDirectory . '/Existing.php'
        );

        $backups = \glob(
            $this->testRoot . '/.generated.smithy-backup-*'
        );
        self::assertIsArray($backups);
        self::assertCount(1, $backups);
        self::assertFileExists($backups[0] . '/Existing.php');
    }

    public function testRejectsCurrentWorkingDirectoryAsOutput(): void
    {
        $workingDirectory = \getcwd();
        self::assertNotFalse($workingDirectory);

        try {
            (new GeneratedOutputPublisher())->publish(
                [
                    new GeneratedFile(
                        $workingDirectory . '/Schemas.php',
                        $this->schemasSource('schemas')
                    )
                ],
                '.'
            );
            self::fail('Expected the working directory to be rejected.');
        } catch (\LogicException $error) {
            self::assertSame(
                'Generated output cannot contain the current working '
                    . 'directory.',
                $error->getMessage()
            );
        }

        self::assertSame($workingDirectory, \getcwd());
    }

    public function testRejectsAncestorOfWorkingDirectoryAsOutput(): void
    {
        $originalDirectory = \getcwd();
        self::assertNotFalse($originalDirectory);
        $outputDirectory = $this->testRoot . '/workspace';
        $workingDirectory = $outputDirectory . '/nested';
        \mkdir($workingDirectory, 0755, true);

        try {
            self::assertTrue(\chdir($workingDirectory));
            try {
                (new GeneratedOutputPublisher())->publish(
                    [
                        new GeneratedFile(
                            $outputDirectory . '/Schemas.php',
                            $this->schemasSource('schemas')
                        )
                    ],
                    $outputDirectory
                );
                self::fail(
                    'Expected a working-directory ancestor to be rejected.'
                );
            } catch (\LogicException $error) {
                self::assertSame(
                    'Generated output cannot contain the current working '
                        . 'directory.',
                    $error->getMessage()
                );
            }
        } finally {
            \chdir($originalDirectory);
        }

        self::assertDirectoryExists($workingDirectory);
    }

    public function testRejectsSymlinkOutputRoot(): void
    {
        if (!\function_exists('symlink')) {
            self::markTestSkipped('Symbolic links are unavailable.');
        }

        $targetDirectory = $this->testRoot . '/target';
        $outputDirectory = $this->testRoot . '/generated';
        \mkdir($targetDirectory);
        \file_put_contents(
            $targetDirectory . '/Schemas.php',
            $this->schemasSource('old schemas')
        );
        \file_put_contents($targetDirectory . '/Manual.php', 'manual');
        if (!@\symlink($targetDirectory, $outputDirectory)) {
            self::markTestSkipped('Unable to create a directory symlink.');
        }

        try {
            try {
                (new GeneratedOutputPublisher())->publish(
                    [
                        new GeneratedFile(
                            $outputDirectory . '/Schemas.php',
                            $this->schemasSource('new schemas')
                        )
                    ],
                    $outputDirectory
                );
                self::fail('Expected a symlink output root to be rejected.');
            } catch (\LogicException $error) {
                self::assertStringContainsString(
                    'is not a managed directory',
                    $error->getMessage()
                );
            }

            self::assertSame(
                'manual',
                \file_get_contents($targetDirectory . '/Manual.php')
            );
            self::assertSame(
                $this->schemasSource('old schemas'),
                \file_get_contents($targetDirectory . '/Schemas.php')
            );
        } finally {
            $removed = @\unlink($outputDirectory);
            if (!$removed && DIRECTORY_SEPARATOR === '\\') {
                @\rmdir($outputDirectory);
            }
        }
    }

    private function schemasSource(
        string $label,
        string $namespace = 'Smithy\\Tests\\Generated'
    ): string
    {
        $signature = SchemasGenerator::GENERATED_FILE_SIGNATURE;
        $imports = [];
        if ($namespace !== 'Smithy\\Core\\Serde') {
            $imports[] = 'use Smithy\\Core\\Serde\\Schema;';
        }
        if ($namespace !== 'Smithy\\Core\\Shape') {
            $imports[] = 'use Smithy\\Core\\Shape\\ShapeType;';
        }
        $importBlock = \implode("\n", $imports);

        return <<<PHP
<?php

namespace {$namespace};

{$importBlock}

/**
 * {$signature}
 */
final class Schemas {}

// {$label}
PHP;
    }

    /**
     * @return list<string>
     */
    private function directoryEntries(string $directory): array
    {
        $entries = \scandir($directory);
        if ($entries === false) {
            self::fail("Unable to inspect test directory: {$directory}");
        }

        return \array_values(\array_diff($entries, ['.', '..']));
    }

    private function removeTestDirectory(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isLink() || !$entry->isDir()) {
                \unlink($entry->getPathname());
            } else {
                \rmdir($entry->getPathname());
            }
        }

        \rmdir($directory);
    }
}
