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
}
