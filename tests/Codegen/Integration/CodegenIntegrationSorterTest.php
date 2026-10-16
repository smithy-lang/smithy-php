<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Integration\AbstractCodegenIntegration;
use Smithy\Codegen\Integration\CodegenIntegrationSorter;

#[CoversClass(CodegenIntegrationSorter::class)]
final class CodegenIntegrationSorterTest extends TestCase
{
    public function testOrdersByDependenciesThenPriority(): void
    {
        $ordered = CodegenIntegrationSorter::sort([
            new SortableIntegration('last', 100, after: ['first']),
            new SortableIntegration('first'),
            new SortableIntegration('middle', 10)
        ]);

        $this->assertSame(
            ['middle', 'first', 'last'],
            \array_map(
                static fn(SortableIntegration $integration): string =>
                    $integration->name(),
                $ordered
            )
        );
    }

    public function testRejectsDuplicateNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Duplicate codegen integration name: duplicate'
        );

        CodegenIntegrationSorter::sort([
            new SortableIntegration('duplicate'),
            new SortableIntegration('duplicate')
        ]);
    }

    public function testRejectsDependencyCycles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Codegen integration ordering contains a cycle: first, second'
        );

        CodegenIntegrationSorter::sort([
            new SortableIntegration('first', after: ['second']),
            new SortableIntegration('second', after: ['first'])
        ]);
    }

    public function testOrdersNumericStringNames(): void
    {
        $ordered = CodegenIntegrationSorter::sort([
            new SortableIntegration('2', after: ['1']),
            new SortableIntegration('1')
        ]);

        $this->assertSame(
            ['1', '2'],
            \array_map(
                static fn(SortableIntegration $integration): string =>
                    $integration->name(),
                $ordered
            )
        );
    }
}

final class SortableIntegration extends AbstractCodegenIntegration
{
    /**
     * @param list<string> $before
     * @param list<string> $after
     */
    public function __construct(
        private readonly string $integrationName,
        private readonly int $integrationPriority = 0,
        private readonly array $before = [],
        private readonly array $after = []
    ) {}

    public function name(): string
    {
        return $this->integrationName;
    }

    public function priority(): int
    {
        return $this->integrationPriority;
    }

    public function runBefore(): array
    {
        return $this->before;
    }

    public function runAfter(): array
    {
        return $this->after;
    }
}
