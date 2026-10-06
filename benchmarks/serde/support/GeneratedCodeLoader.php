<?php

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

/**
 * Registers the disposable benchmark codegen directory with the autoloader.
 */
final class GeneratedCodeLoader
{
    /** @var \Closure(string): void */
    private \Closure $loader;

    private bool $registered = false;

    public function __construct(
        private readonly string $namespace,
        private readonly string $directory
    ) {
        $prefix = \rtrim($namespace, '\\') . '\\';
        $directory = \rtrim($directory, '/\\');
        $this->loader = static function (string $class) use (
            $prefix,
            $directory
        ): void {
            if (!\str_starts_with($class, $prefix)) {
                return;
            }

            $relative = \substr($class, \strlen($prefix));
            $path = $directory
                . DIRECTORY_SEPARATOR
                . \str_replace('\\', DIRECTORY_SEPARATOR, $relative)
                . '.php';
            if (\is_file($path)) {
                require $path;
            }
        };
    }

    public function register(): void
    {
        if (!$this->registered) {
            \spl_autoload_register($this->loader);
            $this->registered = true;
        }
    }

    public function unregister(): void
    {
        if ($this->registered) {
            \spl_autoload_unregister($this->loader);
            $this->registered = false;
        }
    }

    public function operationClass(string $operation): string
    {
        return \rtrim($this->namespace, '\\')
            . '\\Operation\\'
            . $operation
            . 'Operation';
    }
}
