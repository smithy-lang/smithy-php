<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SchemaExtensionKey;
use Smithy\Core\Serde\SchemaExtensionProviderInterface;
use Smithy\Core\Shape\ShapeType;

class SchemaExtensionTest extends TestCase
{
    public function testExtensionKeyAssignsUniqueIds(): void
    {
        $provider1 = $this->createMock(SchemaExtensionProviderInterface::class);
        $provider2 = $this->createMock(SchemaExtensionProviderInterface::class);
        $key1 = new SchemaExtensionKey($provider1);
        $key2 = new SchemaExtensionKey($provider2);
        $this->assertNotEquals($key1->id, $key2->id);
    }

    public function testGetExtensionCachesNullResults(): void
    {
        $callCount = 0;
        $provider = new class($callCount) implements
            SchemaExtensionProviderInterface
        {
            public function __construct(private int &$count) {}

            public function provide(Schema $schema): mixed
            {
                ++$this->count;

                return null;
            }
        };
        $key = new SchemaExtensionKey($provider);
        $schema = new Schema(id: 'test#Foo', type: ShapeType::Structure);

        $this->assertNull($schema->getExtension($key));
        $this->assertNull($schema->getExtension($key));
        $this->assertSame(1, $callCount);
    }

    public function testGetExtensionComputesOnceAndCaches(): void
    {
        $callCount = 0;

        $provider = new class($callCount) implements
            SchemaExtensionProviderInterface
        {
            public function __construct(private int &$count) {}
            public function provide(Schema $schema): mixed
            {
                $this->count++;
                return 'computed_' . $schema->id;
            }
        };

        $key = new SchemaExtensionKey($provider);
        $schema = new Schema(id: 'test#Bar', type: ShapeType::Structure);

        // First call computes
        $result1 = $schema->getExtension($key);
        $this->assertEquals('computed_test#Bar', $result1);
        $this->assertEquals(1, $callCount);

        // Second call returns cached
        $result2 = $schema->getExtension($key);
        $this->assertEquals('computed_test#Bar', $result2);
        $this->assertEquals(1, $callCount); // NOT incremented
    }

    public function testProviderCanReturnNull(): void
    {
        $provider = new class implements SchemaExtensionProviderInterface {
            public function provide(Schema $schema): mixed { return null; }
        };
        $key = new SchemaExtensionKey($provider);

        $schema = new Schema(id: 'test#Baz', type: ShapeType::String_);
        $this->assertNull($schema->getExtension($key));
    }

}
