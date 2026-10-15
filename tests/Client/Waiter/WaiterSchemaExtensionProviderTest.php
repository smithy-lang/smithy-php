<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Waiter\{
    Acceptor,
    WaiterDefinition,
    WaiterDefinitionNormalizer,
    WaiterSchemaExtension,
    WaiterSchemaExtensionProvider
};
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

#[CoversClass(WaiterSchemaExtensionProvider::class)]
#[CoversClass(WaiterSchemaExtension::class)]
#[CoversClass(WaiterDefinition::class)]
#[CoversClass(WaiterDefinitionNormalizer::class)]
final class WaiterSchemaExtensionProviderTest extends TestCase
{
    public function testLazilyMaterializesModeledWaiters(): void
    {
        $schema = new Schema(
            id: 'example#GetThing',
            type: ShapeType::Operation,
            traits: [
                Schema::T_WAITABLE => [
                    'ThingReady' => [
                        'documentation' => 'Wait until ready.',
                        'deprecated' => true,
                        'tags' => ['thing'],
                        'acceptors' => [
                            [
                                'state' => 'retry',
                                'matcher' => [
                                    'errorType' => 'NotReady'
                                ]
                            ],
                            [
                                'state' => 'failure',
                                'matcher' => [
                                    'output' => [
                                        'path' => 'status',
                                        'expected' => 'failed',
                                        'comparator' => 'stringEquals'
                                    ]
                                ],
                                'message' => 'reason'
                            ],
                            [
                                'state' => 'success',
                                'matcher' => [
                                    'inputOutput' => [
                                        'path' => 'output.status',
                                        'expected' => 'ready',
                                        'comparator' => 'stringEquals'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $first = $schema->getExtension(
            WaiterSchemaExtensionProvider::key()
        );
        $second = $schema->getExtension(
            WaiterSchemaExtensionProvider::key()
        );

        self::assertInstanceOf(WaiterSchemaExtension::class, $first);
        self::assertSame($first, $second);
        $definition = $first->get('ThingReady');
        self::assertNotNull($definition);
        self::assertSame($definition, $first->get('tHiNgReAdY'));
        self::assertSame(2, $definition->minDelay);
        self::assertSame(120, $definition->maxDelay);
        self::assertSame('Wait until ready.', $definition->documentation);
        self::assertTrue($definition->deprecated);
        self::assertSame(['thing'], $definition->tags);
        self::assertTrue($definition->requiresOutput);
        self::assertSame(
            [
                Acceptor::RETRY,
                Acceptor::FAILURE,
                Acceptor::SUCCESS
            ],
            \array_map(
                static fn(Acceptor $acceptor): int => $acceptor->state,
                $definition->acceptors
            )
        );
    }

    public function testReturnsNullWithoutWaitableTrait(): void
    {
        $schema = new Schema(
            id: 'example#GetThing',
            type: ShapeType::Operation
        );

        self::assertNull($schema->getExtension(
            WaiterSchemaExtensionProvider::key()
        ));
    }

    public function testReturnsNullForMarkerOrEmptyWaitableTrait(): void
    {
        foreach ([true, []] as $trait) {
            $schema = new Schema(
                id: 'example#GetThing',
                type: ShapeType::Operation,
                traits: [Schema::T_WAITABLE => $trait]
            );

            self::assertNull($schema->getExtension(
                WaiterSchemaExtensionProvider::key()
            ));
        }
    }

    public function testRejectsOutputMatcherWithoutOperationOutput(): void
    {
        $schema = new Schema(
            id: 'example#GetThing',
            type: ShapeType::Operation,
            traits: [
                Schema::T_WAITABLE => [
                    'ThingReady' => [
                        'acceptors' => [[
                            'state' => 'success',
                            'matcher' => [
                                'output' => [
                                    'path' => 'status',
                                    'expected' => 'ready',
                                    'comparator' => 'stringEquals'
                                ]
                            ]
                        ]]
                    ]
                ]
            ]
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'output matchers require an operation output'
        );

        $schema->getExtension(
            WaiterSchemaExtensionProvider::key(false)
        );
    }

    public function testRejectsInvalidRuntimeMetadata(): void
    {
        $schema = new Schema(
            id: 'example#GetThing',
            type: ShapeType::Operation,
            traits: [
                Schema::T_WAITABLE => [
                    'ThingReady' => [
                        'acceptors' => []
                    ]
                ]
            ]
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'acceptors must be a non-empty list'
        );

        $schema->getExtension(WaiterSchemaExtensionProvider::key());
    }
}
