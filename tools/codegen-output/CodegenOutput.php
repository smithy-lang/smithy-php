<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tools\CodegenOutput;

/**
 * Filesystem operations used to manage committed codegen output.
 *
 * @internal
 */
final class CodegenOutput
{
    /**
     * @return list<string>
     */
    public static function compareDirectories(
        string $generated,
        string $committed
    ): array
    {
        $generatedFiles = self::collectFiles($generated);
        $committedFiles = self::collectFiles($committed);
        $paths = \array_unique([
            ...\array_keys($generatedFiles),
            ...\array_keys($committedFiles)
        ]);
        \sort($paths);

        $differences = [];
        foreach ($paths as $path) {
            if (!isset($committedFiles[$path])) {
                $differences[] = "add {$path}";
            } elseif (!isset($generatedFiles[$path])) {
                $differences[] = "remove {$path}";
            } elseif ($generatedFiles[$path] !== $committedFiles[$path]) {
                $differences[] = "update {$path}";
            }
        }

        return $differences;
    }

    public static function lintPhpFiles(string $directory): void
    {
        foreach (\array_keys(self::collectFiles($directory)) as $path) {
            if (!\str_ends_with($path, '.php')) {
                continue;
            }

            $output = [];
            $status = 0;
            \exec(
                \escapeshellarg(PHP_BINARY)
                    . ' -l '
                    . \escapeshellarg($directory . '/' . $path)
                    . ' 2>&1',
                $output,
                $status
            );

            if ($status !== 0) {
                throw new \RuntimeException(
                    "Generated PHP failed lint: {$path}\n"
                        . \implode("\n", $output)
                );
            }
        }
    }

    /**
     * @param callable(string, string): bool|null $moveDirectory
     */
    public static function publishDirectory(
        string $stagingDirectory,
        string $outputDirectory,
        string $stagingRoot,
        string $outputRoot,
        ?callable $moveDirectory = null
    ): void
    {
        self::validatePath($stagingDirectory, $stagingRoot);
        self::validatePath($outputDirectory, $outputRoot);

        if (!\is_dir($stagingDirectory)) {
            throw new \LogicException(
                "Generated staging directory not found: {$stagingDirectory}"
            );
        }

        $outputParent = \dirname($outputDirectory);
        if (!\is_dir($outputParent)
            && !\mkdir($outputParent, 0777, true)
        ) {
            throw new \RuntimeException(
                'Unable to create generated output directory.'
            );
        }
        self::validatePath($outputDirectory, $outputRoot);

        $backupDirectory = $outputDirectory . '.backup';
        self::validatePath($backupDirectory, $outputRoot);
        if (\file_exists($backupDirectory)
            || \is_link($backupDirectory)
        ) {
            throw new \LogicException(
                "Generated output backup already exists: {$backupDirectory}"
            );
        }

        if (\file_exists($outputDirectory)
            && !\is_dir($outputDirectory)
        ) {
            throw new \LogicException(
                "Generated output is not a directory: {$outputDirectory}"
            );
        }

        $moveDirectory ??= static fn(
            string $source,
            string $destination
        ): bool => \rename($source, $destination);

        $hasSnapshot = \is_dir($outputDirectory);
        if ($hasSnapshot
            && !$moveDirectory($outputDirectory, $backupDirectory)
        ) {
            throw new \RuntimeException(
                'Unable to back up existing generated output.'
            );
        }

        try {
            $published = $moveDirectory(
                $stagingDirectory,
                $outputDirectory
            );
        } catch (\Throwable $error) {
            self::restoreSnapshot(
                $hasSnapshot,
                $backupDirectory,
                $outputDirectory,
                $moveDirectory,
                $error
            );
        }

        if (!$published) {
            self::restoreSnapshot(
                $hasSnapshot,
                $backupDirectory,
                $outputDirectory,
                $moveDirectory
            );
        }

        if ($hasSnapshot) {
            self::removeDirectory($backupDirectory, $outputRoot);
        }
    }

    public static function removeDirectory(
        string $directory,
        string $allowedRoot
    ): void
    {
        self::validatePath($directory, $allowedRoot);
        if (!\file_exists($directory)) {
            return;
        }
        if (!\is_dir($directory)) {
            throw new \LogicException(
                "Refusing to remove non-directory {$directory}."
            );
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

    public static function validatePath(
        string $path,
        string $allowedRoot
    ): void
    {
        $path = self::normalizePath($path);
        $allowedRoot = self::normalizePath($allowedRoot);
        foreach ([$path, $allowedRoot] as $candidate) {
            if (\in_array(
                '..',
                \explode(DIRECTORY_SEPARATOR, $candidate),
                true
            )) {
                throw new \LogicException(
                    "Refusing parent traversal in path {$candidate}."
                );
            }
        }

        if (self::pathsEqual($path, $allowedRoot)
            || !self::pathStartsWith(
                $path,
                $allowedRoot . DIRECTORY_SEPARATOR
            )
        ) {
            throw new \LogicException(
                "Path is outside allowed root {$allowedRoot}: {$path}"
            );
        }

        self::validateUnredirectedPath($allowedRoot);
        self::validateUnredirectedPath($path);
    }

    /**
     * @return array<string, string>
     */
    private static function collectFiles(string $directory): array
    {
        if (!\is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $relativePath = \substr(
                $file->getPathname(),
                \strlen($directory) + 1
            );
            $files[$relativePath] = \hash_file(
                'sha256',
                $file->getPathname()
            );
        }
        \ksort($files);

        return $files;
    }

    private static function normalizePath(string $path): string
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = \str_replace('/', DIRECTORY_SEPARATOR, $path);
        }

        return \rtrim($path, DIRECTORY_SEPARATOR);
    }

    private static function pathStartsWith(
        string $path,
        string $prefix
    ): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = \strtolower($path);
            $prefix = \strtolower($prefix);
        }

        return \str_starts_with($path, $prefix);
    }

    private static function pathsEqual(string $left, string $right): bool
    {
        return DIRECTORY_SEPARATOR === '\\'
            ? \strcasecmp($left, $right) === 0
            : $left === $right;
    }

    /**
     * @param callable(string, string): bool $moveDirectory
     */
    private static function restoreSnapshot(
        bool $hasSnapshot,
        string $backupDirectory,
        string $outputDirectory,
        callable $moveDirectory,
        ?\Throwable $failure = null
    ): never
    {
        if ($hasSnapshot
            && !$moveDirectory($backupDirectory, $outputDirectory)
        ) {
            throw new \RuntimeException(
                "Unable to restore generated output; backup retained at "
                    . $backupDirectory,
                0,
                $failure
            );
        }

        throw new \RuntimeException(
            $hasSnapshot
                ? 'Unable to replace generated output; snapshot restored.'
                : 'Unable to publish generated output.',
            0,
            $failure
        );
    }

    private static function validateUnredirectedPath(string $path): void
    {
        $path = self::normalizePath($path);
        $existingPath = $path;
        while (!\file_exists($existingPath)
            && !\is_link($existingPath)
        ) {
            $parent = \dirname($existingPath);
            if ($parent === $existingPath) {
                throw new \LogicException(
                    "Unable to resolve path {$path}."
                );
            }
            $existingPath = $parent;
        }

        if (\is_link($existingPath)) {
            throw new \LogicException(
                "Refusing redirected path {$path}."
            );
        }

        $resolvedPath = \realpath($existingPath);
        if ($resolvedPath === false
            || !self::pathsEqual(
                self::normalizePath($resolvedPath),
                $existingPath
            )
        ) {
            throw new \LogicException(
                "Refusing redirected path {$path}."
            );
        }
    }
}
