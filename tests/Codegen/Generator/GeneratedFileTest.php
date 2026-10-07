<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Generator\GeneratedFile;

final class GeneratedFileTest extends TestCase
{
    public function testConstructorAssignsPathAndContentReadonly(): void
    {
        $file = new GeneratedFile(
            path: '/tmp/example/Foo.php',
            content: "<?php\n\nclass Foo {}\n"
        );

        $this->assertSame('/tmp/example/Foo.php', $file->path);
        $this->assertSame("<?php\n\nclass Foo {}\n", $file->content);
    }

    public function testWriteToDiskCreatesFileWithExactContent(): void
    {
        $dir = sys_get_temp_dir() . '/smithy-generated-file-test-' . uniqid();
        $path = $dir . '/Sample.php';
        $content = "<?php\n\nfinal class Sample {}\n";

        $file = new GeneratedFile($path, $content);
        $file->writeToDisk();

        try {
            $this->assertFileExists($path);
            $this->assertSame($content, file_get_contents($path));
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }

    public function testWriteToDiskCreatesNestedDirectoryTree(): void
    {
        $root = sys_get_temp_dir() . '/smithy-generated-file-test-' . uniqid();
        // Three-deep to prove `mkdir` runs recursively. if the class only
        // handled the immediate parent, the middle level would fail.
        $path = $root . '/a/b/c/Deep.php';

        $file = new GeneratedFile($path, "<?php\n");
        $file->writeToDisk();

        try {
            $this->assertFileExists($path);
        } finally {
            @unlink($path);
            @rmdir($root . '/a/b/c');
            @rmdir($root . '/a/b');
            @rmdir($root . '/a');
            @rmdir($root);
        }
    }

    public function testWriteToDiskOverwritesExistingFile(): void
    {
        $dir = sys_get_temp_dir() . '/smithy-generated-file-test-' . uniqid();
        $path = $dir . '/Rewritable.php';
        mkdir($dir, 0755, true);
        file_put_contents($path, 'stale content');

        try {
            (new GeneratedFile($path, "<?php\n// fresh\n"))->writeToDisk();
            $this->assertSame("<?php\n// fresh\n", file_get_contents($path));
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }

    public function testWriteToDiskRaisesWhenPathIsUnwritable(): void
    {
        // Writing under an existing regular file forces
        // `is_dir(dirname($path))`
        // false and `mkdir()` to fail - the "directory can't be created"
        // branch.
        // `mkdir()` emits a PHP warning before the SUT throws. swallow it
        // locally
        // so the test asserts on the exception path without polluting the run.
        $blocker = tempnam(sys_get_temp_dir(), 'smithy-blocker-');
        $path = $blocker . '/inside/File.php';

        set_error_handler(static fn() => true, E_WARNING);
        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches(
                '/Failed to create directory/'
            );
            (new GeneratedFile($path, "<?php\n"))->writeToDisk();
        } finally {
            restore_error_handler();
            @unlink($blocker);
        }
    }

    public function testWriteToDiskRejectsPathOutsideAllowedRoot(): void
    {
        $root = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $outside = dirname($root) . '/outside.php';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('outside');

        (new GeneratedFile($outside, "<?php\n"))
            ->writeToDisk($root);
    }

    public function testWriteToDiskRejectsTraversalBelowMissingDirectory(): void
    {
        $root = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $path = $root . '/missing/../../outside.php';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('parent traversal');

        (new GeneratedFile($path, "<?php\n"))
            ->writeToDisk($root);
    }

    public function testWriteToDiskRejectsTraversalAfterSymlink(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped(
                'Creating symlinks is not consistently available on Windows.'
            );
        }

        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $root = $base . '/root';
        $outside = $base . '/outside';
        $deep = $outside . '/deep';
        mkdir($root, 0755, true);
        mkdir($deep, 0755, true);
        symlink($deep, $root . '/redirect');
        $path = $root . '/redirect/../Escaped.php';

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('parent traversal');

            (new GeneratedFile($path, "<?php\n"))
                ->writeToDisk($root);
        } finally {
            @unlink($outside . '/Escaped.php');
            @unlink($root . '/redirect');
            @rmdir($root);
            @rmdir($deep);
            @rmdir($outside);
            @rmdir($base);
        }
    }

    public function testWriteToDiskTreatsBackslashAsLiteralOnPosix(): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            $this->markTestSkipped('This assertion is specific to POSIX.');
        }

        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $root = $base . '/root';
        $path = $root . '\\Escaped.php';
        mkdir($root, 0755, true);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('outside');

            (new GeneratedFile($path, "<?php\n"))
                ->writeToDisk($root);
        } finally {
            @unlink($path);
            @rmdir($root);
            @rmdir($base);
        }
    }

    public function testWriteToDiskRejectsStreamWrapper(): void
    {
        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $root = $base . '/root';
        $outside = $base . '/outside';
        mkdir($root, 0755, true);
        mkdir($outside, 0755, true);
        $path = 'file://' . $outside . '/Escaped.php';

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('stream wrappers');

            (new GeneratedFile($path, "<?php\n"))
                ->writeToDisk($root);
        } finally {
            @unlink($outside . '/Escaped.php');
            @rmdir($root);
            @rmdir($outside);
            @rmdir($base);
        }
    }

    public function testWriteToDiskRejectsStreamWrapperOutputRoot(): void
    {
        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $root = $base . '/root';
        $path = $root . '/Escaped.php';
        mkdir($root, 0755, true);

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('stream wrappers');

            (new GeneratedFile($path, "<?php\n"))
                ->writeToDisk('file://' . $root);
        } finally {
            @unlink($path);
            @rmdir($root);
            @rmdir($base);
        }
    }

    public function testWriteToDiskSupportsParentRelativeOutputRoot(): void
    {
        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $workingDirectory = $base . '/work';
        $root = $base . '/generated';
        $path = '../generated/Nested/File.php';
        mkdir($workingDirectory, 0755, true);
        $originalDirectory = \getcwd();

        try {
            \chdir($workingDirectory);
            (new GeneratedFile($path, "<?php\n"))
                ->writeToDisk('../generated');

            $this->assertFileExists($root . '/Nested/File.php');
        } finally {
            if ($originalDirectory !== false) {
                \chdir($originalDirectory);
            }
            @unlink($root . '/Nested/File.php');
            @rmdir($root . '/Nested');
            @rmdir($root);
            @rmdir($workingDirectory);
            @rmdir($base);
        }
    }

    public function testSupportsNormalizedRootWithMissingIntermediate(): void
    {
        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $workingDirectory = $base . '/work';
        $root = $workingDirectory . '/generated';
        $path = 'build/../generated/File.php';
        mkdir($workingDirectory, 0755, true);
        $originalDirectory = \getcwd();

        try {
            \chdir($workingDirectory);
            (new GeneratedFile($path, "<?php\n"))
                ->writeToDisk('build/../generated');

            $this->assertFileExists($root . '/File.php');
            $this->assertDirectoryDoesNotExist(
                $workingDirectory . '/build'
            );
        } finally {
            if ($originalDirectory !== false) {
                \chdir($originalDirectory);
            }
            @unlink($root . '/File.php');
            @rmdir($root);
            @rmdir($workingDirectory);
            @rmdir($base);
        }
    }

    public function testNormalizedRootRejectsDestinationSymlink(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped(
                'Creating symlinks is not consistently available on Windows.'
            );
        }

        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $workingDirectory = $base . '/work';
        $root = $workingDirectory . '/generated';
        $target = $root . '/Other.php';
        $link = $root . '/File.php';
        mkdir($root, 0755, true);
        file_put_contents($target, 'original');
        symlink($target, $link);
        $originalDirectory = \getcwd();

        try {
            \chdir($workingDirectory);

            try {
                (new GeneratedFile(
                    'missing/../generated/File.php',
                    'replacement'
                ))->writeToDisk('missing/../generated');
                $this->fail('Expected the destination symlink to be rejected.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString(
                    'symlink',
                    $exception->getMessage()
                );
                $this->assertSame('original', file_get_contents($target));
            }
        } finally {
            if ($originalDirectory !== false) {
                \chdir($originalDirectory);
            }
            @unlink($link);
            @unlink($target);
            @rmdir($root);
            @rmdir($workingDirectory);
            @rmdir($base);
        }
    }

    public function testWriteToDiskRejectsHardLinkedDestination(): void
    {
        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $root = $base . '/root';
        $outside = $base . '/outside';
        $outsideFile = $outside . '/Shared.php';
        $destination = $root . '/Generated.php';
        mkdir($root, 0755, true);
        mkdir($outside, 0755, true);
        file_put_contents($outsideFile, 'original');

        if (!@\link($outsideFile, $destination)) {
            @unlink($outsideFile);
            @rmdir($root);
            @rmdir($outside);
            @rmdir($base);
            $this->markTestSkipped(
                'Creating hard links is not available on this filesystem.'
            );
        }

        try {
            try {
                (new GeneratedFile($destination, 'replacement'))
                    ->writeToDisk($root);
                $this->fail('Expected the hard-linked file to be rejected.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString(
                    'multiple hard links',
                    $exception->getMessage()
                );
                $this->assertSame(
                    'original',
                    file_get_contents($outsideFile)
                );
            }
        } finally {
            @unlink($destination);
            @unlink($outsideFile);
            @rmdir($root);
            @rmdir($outside);
            @rmdir($base);
        }
    }

    public function testPathNormalizationPreservesUncRootOnWindows(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('This assertion is specific to Windows.');
        }

        $method = new \ReflectionMethod(
            GeneratedFile::class,
            'absolutePath'
        );

        $this->assertSame(
            '\\\\server\\share\\out\\File.php',
            $method->invoke(
                null,
                '\\\\server\\share\\out\\File.php'
            )
        );
    }

    public function testResolvesCurrentDriveRelativePathOnWindows(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('This assertion is specific to Windows.');
        }

        $currentDirectory = \realpath('.');
        $this->assertNotFalse($currentDirectory);
        $drive = \substr($currentDirectory, 0, 2);
        $method = new \ReflectionMethod(
            GeneratedFile::class,
            'absolutePath'
        );

        $this->assertSame(
            \rtrim($currentDirectory, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'generated'
                . DIRECTORY_SEPARATOR
                . 'File.php',
            $method->invoke(null, $drive . 'generated\\File.php')
        );
    }

    public function testResolvesOtherDriveRelativePathOnWindows(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('This assertion is specific to Windows.');
        }

        $currentDirectory = \realpath('.');
        $this->assertNotFalse($currentDirectory);
        $currentDrive = \strtoupper(\substr($currentDirectory, 0, 2));
        $otherDrive = null;
        $otherDirectory = null;
        foreach (\range('A', 'Z') as $letter) {
            $candidateDrive = $letter . ':';
            if ($candidateDrive === $currentDrive) {
                continue;
            }

            $candidateDirectory = \realpath($candidateDrive . '.');
            if ($candidateDirectory !== false) {
                $otherDrive = $candidateDrive;
                $otherDirectory = $candidateDirectory;
                break;
            }
        }

        if ($otherDrive === null || $otherDirectory === null) {
            $this->markTestSkipped('No second Windows drive is available.');
        }

        $method = new \ReflectionMethod(
            GeneratedFile::class,
            'absolutePath'
        );
        $this->assertSame(
            \rtrim($otherDirectory, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'generated'
                . DIRECTORY_SEPARATOR
                . 'File.php',
            $method->invoke(null, $otherDrive . 'generated\\File.php')
        );
    }

    public function testWindowsPathContainmentPreservesCase(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('This assertion is specific to Windows.');
        }

        $method = new \ReflectionMethod(
            GeneratedFile::class,
            'pathStartsWith'
        );
        $this->assertFalse(
            $method->invoke(
                null,
                'C:\\base\\ROOT\\File.php',
                'C:\\base\\root\\'
            )
        );
    }

    public function testRejectsCaseDistinctSiblingRoot(): void
    {
        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $root = $base . '/root';
        $caseDistinctRoot = $base . '/ROOT';
        $path = $caseDistinctRoot . '/File.php';
        mkdir($root, 0755, true);
        @mkdir($caseDistinctRoot);

        try {
            $rootMetadata = \stat($root);
            $caseDistinctMetadata = \stat($caseDistinctRoot);
            if ($rootMetadata !== false
                && $caseDistinctMetadata !== false
                && $rootMetadata['dev'] === $caseDistinctMetadata['dev']
                && $rootMetadata['ino'] === $caseDistinctMetadata['ino']
            ) {
                $this->markTestSkipped(
                    'This filesystem does not support case-distinct siblings.'
                );
            }

            file_put_contents($path, "<?php\n");
            $method = new \ReflectionMethod(
                GeneratedFile::class,
                'isPathWithinRoot'
            );

            $this->assertFalse(
                $method->invoke(null, $path, $root)
            );
        } finally {
            @unlink($path);
            @rmdir($caseDistinctRoot);
            @rmdir($root);
            @rmdir($base);
        }
    }

    public function testWriteToDiskRejectsSymlinkEscape(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped(
                'Creating symlinks is not consistently available on Windows.'
            );
        }

        $base = sys_get_temp_dir()
            . '/smithy-generated-file-test-' . uniqid();
        $root = $base . '/root';
        $outside = $base . '/outside';
        mkdir($root, 0755, true);
        mkdir($outside, 0755, true);
        symlink($outside, $root . '/redirect');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('outside');

            (new GeneratedFile(
                $root . '/redirect/Escaped.php',
                "<?php\n"
            ))->writeToDisk($root);
        } finally {
            @unlink($root . '/redirect');
            @rmdir($root);
            @rmdir($outside);
            @rmdir($base);
        }
    }
}
