<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\ShapeCategory;

/**
 * The enum has no methods of its own - it exists as a marker used by
 * {@see \Smithy\Codegen\CodegenSettings::resolveNamespace()} to route
 * shapes into per-category sub-namespaces. The tests pin the case set
 * so a future addition here forces a paired change to
 * {@see \Smithy\Codegen\CodegenSettings} (which pattern-matches on
 * every case) and to the client-code that consumes those categories.
 */
final class ShapeCategoryTest extends TestCase
{
    public function testHasFourCases(): void
    {
        $this->assertCount(4, ShapeCategory::cases());
    }

    public function testExposesInputOutputErrorAndShapeCases(): void
    {
        $names = array_map(
            static fn(ShapeCategory $c): string => $c->name,
            ShapeCategory::cases()
        );

        $this->assertSame(['Input', 'Output', 'Error', 'Shape'], $names);
    }
}
