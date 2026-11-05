<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Smithy\Tools\CodegenOutput\CodegenOutput;

require_once __DIR__ . '/../../tools/codegen-output/CodegenOutput.php';

/**
 * Cover committed codegen output filesystem operations.
 */
final class CodegenOutputTest extends TestCase
{
    private string $testRoot;

    public function testComparesIdenticalDirectories(): void
    {
        $generated = $this->testRoot . '/generated';
        $committed = $this->testRoot . '/committed';
        \mkdir($generated);
        \mkdir($committed);
        \file_put_contents($generated . '/same.php', 'same');
        \file_put_contents($committed . '/same.php', 'same');

        self::assertSame(
            [],
            CodegenOutput::compareDirectories($generated, $committed)
        );
    }

    public function testReportsAllDirectoryDifferenceTypes(): void
    {
        $generated = $this->testRoot . '/generated';
        $committed = $this->testRoot . '/committed';
        \mkdir($generated);
        \mkdir($committed);
        \file_put_contents($generated . '/added.php', 'added');
        \file_put_contents($generated . '/changed.php', 'new');
        \file_put_contents($committed . '/changed.php', 'old');
        \file_put_contents($committed . '/removed.php', 'removed');

        self::assertSame(
            [
                'add added.php',
                'update changed.php',
                'remove removed.php'
            ],
            CodegenOutput::compareDirectories($generated, $committed)
        );
    }

    public function testRestoresSnapshotWhenPublicationFails(): void
    {
        $stagingRoot = $this->testRoot . '/build';
        $stagingDirectory = $stagingRoot . '/staging/src';
        $outputRoot = $this->testRoot . '/codegen-output';
        $outputDirectory = $outputRoot . '/rpcv2-cbor/src';
        \mkdir($stagingDirectory, 0777, true);
        \mkdir($outputDirectory, 0777, true);
        \file_put_contents($stagingDirectory . '/new.php', 'new');
        \file_put_contents($outputDirectory . '/old.php', 'old');

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
            CodegenOutput::publishDirectory(
                $stagingDirectory,
                $outputDirectory,
                $stagingRoot,
                $outputRoot,
                $moveDirectory
            );
            self::fail('Expected generated output publication to fail.');
        } catch (\RuntimeException $error) {
            self::assertSame(
                'Unable to replace generated output; snapshot restored.',
                $error->getMessage()
            );
        }

        self::assertFileExists($outputDirectory . '/old.php');
        self::assertFileDoesNotExist($outputDirectory . '/new.php');
        self::assertFileExists($stagingDirectory . '/new.php');
        self::assertDirectoryDoesNotExist($outputDirectory . '.backup');
    }

    public function testPublishesSnapshotAndRemovesBackup(): void
    {
        $stagingRoot = $this->testRoot . '/build';
        $stagingDirectory = $stagingRoot . '/staging/src';
        $outputRoot = $this->testRoot . '/codegen-output';
        $outputDirectory = $outputRoot . '/rpcv2-cbor/src';
        \mkdir($stagingDirectory, 0777, true);
        \mkdir($outputDirectory, 0777, true);
        \file_put_contents($stagingDirectory . '/new.php', 'new');
        \file_put_contents($outputDirectory . '/old.php', 'old');

        CodegenOutput::publishDirectory(
            $stagingDirectory,
            $outputDirectory,
            $stagingRoot,
            $outputRoot
        );

        self::assertFileExists($outputDirectory . '/new.php');
        self::assertFileDoesNotExist($outputDirectory . '/old.php');
        self::assertDirectoryDoesNotExist($stagingDirectory);
        self::assertDirectoryDoesNotExist($outputDirectory . '.backup');
    }

    public function testRejectsMissingPathBelowRedirectedAncestor(): void
    {
        if (!\function_exists('symlink')) {
            self::markTestSkipped('Symbolic links are unavailable.');
        }

        $allowedRoot = $this->testRoot . '/build';
        $parent = $allowedRoot . '/staging';
        $externalDirectory = $this->testRoot . '/external';
        \mkdir($parent, 0777, true);
        \mkdir($externalDirectory);
        \symlink($externalDirectory, $parent . '/rpcv2-cbor');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Refusing redirected path');

        CodegenOutput::removeDirectory(
            $parent . '/rpcv2-cbor/src',
            $allowedRoot
        );
    }

    public function testRejectsTraversalBeforePublishing(): void
    {
        $stagingRoot = $this->testRoot . '/build';
        $stagingDirectory = $stagingRoot . '/staging/src';
        $outputRoot = $this->testRoot . '/codegen-output';
        $outputDirectory = $outputRoot
            . '/missing/../../outside/src';
        \mkdir($stagingDirectory, 0777, true);
        \mkdir($outputRoot);

        try {
            CodegenOutput::publishDirectory(
                $stagingDirectory,
                $outputDirectory,
                $stagingRoot,
                $outputRoot
            );
            self::fail('Expected parent traversal to be rejected.');
        } catch (\LogicException $error) {
            self::assertStringContainsString(
                'Refusing parent traversal',
                $error->getMessage()
            );
        }

        self::assertDirectoryDoesNotExist($this->testRoot . '/outside');
        self::assertDirectoryExists($stagingDirectory);
    }

    protected function setUp(): void
    {
        $temporaryRoot = \realpath(\sys_get_temp_dir());
        if ($temporaryRoot === false) {
            self::fail('Unable to resolve the temporary directory.');
        }

        $this->testRoot = $temporaryRoot
            . '/smithy-php-codegen-output-'
            . \bin2hex(\random_bytes(8));
        \mkdir($this->testRoot);
    }

    protected function tearDown(): void
    {
        self::removeTestDirectory($this->testRoot);
    }

    private static function removeTestDirectory(string $directory): void
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
