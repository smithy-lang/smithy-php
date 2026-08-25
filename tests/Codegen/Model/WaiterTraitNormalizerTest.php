<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Model\SmithyModel;

final class WaiterTraitNormalizerTest extends TestCase
{
    public function testReturnsEmptyMapWhenTraitIsAbsent(): void
    {
        $model = $this->modelWithoutWaiters();

        $this->assertSame(
            [],
            $model->getValidatedWaiters('smithy.example#GetThing')
        );
    }

    public function testNormalizesDefaultsAndPreservesAcceptorOrder(): void
    {
        $model = $this->model([
            'ThingReady' => [
                'acceptors' => [
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
                            'success' => true
                        ]
                    ],
                    [
                        'state' => 'retry',
                        'matcher' => [
                            'errorType' => 'NotFound'
                        ]
                    ]
                ]
            ]
        ]);

        $this->assertSame(
            [
                'ThingReady' => [
                    'documentation' => null,
                    'acceptors' => [
                        [
                            'state' => 'failure',
                            'matcher' => [
                                'type' => 'output',
                                'path' => 'status',
                                'expected' => 'failed',
                                'comparator' => 'stringEquals'
                            ],
                            'message' => 'reason'
                        ],
                        [
                            'state' => 'success',
                            'matcher' => [
                                'type' => 'success',
                                'path' => null,
                                'expected' => true,
                                'comparator' => null
                            ],
                            'message' => null
                        ],
                        [
                            'state' => 'retry',
                            'matcher' => [
                                'type' => 'errorType',
                                'path' => null,
                                'expected' => 'NotFound',
                                'comparator' => null
                            ],
                            'message' => null
                        ]
                    ],
                    'minDelay' => 2,
                    'maxDelay' => 120,
                    'deprecated' => false,
                    'tags' => []
                ]
            ],
            $model->getValidatedWaiters('smithy.example#GetThing')
        );
    }

    public function testNormalizesOptionalWaiterMetadata(): void
    {
        $waiter = self::validWaiter();
        $waiter['documentation'] = 'Wait for the thing.';
        $waiter['minDelay'] = 5;
        $waiter['maxDelay'] = 30;
        $waiter['deprecated'] = true;
        $waiter['tags'] = ['resource', 'terminal'];
        $model = $this->model(['ThingReady' => $waiter]);

        $normalized = $model->getValidatedWaiters(
            'smithy.example#GetThing'
        )['ThingReady'];

        $this->assertSame('Wait for the thing.', $normalized['documentation']);
        $this->assertSame(5, $normalized['minDelay']);
        $this->assertSame(30, $normalized['maxDelay']);
        $this->assertTrue($normalized['deprecated']);
        $this->assertSame(
            ['resource', 'terminal'],
            $normalized['tags']
        );
    }

    public function testNormalizesInputOutputMatcher(): void
    {
        $model = $this->model([
            'ThingReady' => [
                'acceptors' => [
                    [
                        'state' => 'success',
                        'matcher' => [
                            'inputOutput' => [
                                'path' => 'input.id == output.id',
                                'expected' => 'true',
                                'comparator' => 'booleanEquals'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $matcher = $model->getValidatedWaiters(
            'smithy.example#GetThing'
        )['ThingReady']['acceptors'][0]['matcher'];

        $this->assertSame('inputOutput', $matcher['type']);
        $this->assertSame('input.id == output.id', $matcher['path']);
        $this->assertSame('true', $matcher['expected']);
        $this->assertSame('booleanEquals', $matcher['comparator']);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidTraits(): iterable
    {
        yield 'trait must be map' => [
            'invalid',
            'trait value must be a waiter map'
        ];
        yield 'waiter name' => [
            ['thingReady' => self::validWaiter()],
            'waiter names must use UpperCamelCase'
        ];
        yield 'waiter definition' => [
            ['ThingReady' => 'invalid'],
            'definition must be an object'
        ];
        yield 'minimum delay' => [
            [
                'ThingReady' => self::validWaiter([
                    'minDelay' => 0
                ])
            ],
            'minDelay must be between 1 and maxDelay'
        ];
        yield 'minimum above maximum' => [
            [
                'ThingReady' => self::validWaiter([
                    'minDelay' => 10,
                    'maxDelay' => 5
                ])
            ],
            'minDelay must be between 1 and maxDelay'
        ];
        yield 'maximum delay' => [
            [
                'ThingReady' => self::validWaiter([
                    'maxDelay' => 0
                ])
            ],
            'maxDelay must be an integer'
        ];
        yield 'acceptors must be list' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => ['state' => 'success']
                ])
            ],
            'acceptors must be a non-empty list'
        ];
        yield 'acceptors require success' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'retry',
                            'matcher' => ['success' => false]
                        ]
                    ]
                ])
            ],
            'acceptors must contain a success state'
        ];
        yield 'acceptor state' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'unknown',
                            'matcher' => ['success' => true]
                        ]
                    ]
                ])
            ],
            'state must be success, failure, or retry'
        ];
        yield 'matcher union' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'success',
                            'matcher' => [
                                'success' => true,
                                'errorType' => 'NotFound'
                            ]
                        ]
                    ]
                ])
            ],
            'matcher must set exactly one member'
        ];
        yield 'success matcher type' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'success',
                            'matcher' => ['success' => 'true']
                        ]
                    ]
                ])
            ],
            'success matcher must be a boolean'
        ];
        yield 'error matcher type' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'success',
                            'matcher' => ['errorType' => '']
                        ]
                    ]
                ])
            ],
            'errorType matcher must be a non-empty string'
        ];
        yield 'path comparator' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'success',
                            'matcher' => [
                                'output' => [
                                    'path' => 'status',
                                    'expected' => 'ready',
                                    'comparator' => 'equals'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
            'comparator is not recognized'
        ];
        yield 'boolean expected' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'success',
                            'matcher' => [
                                'output' => [
                                    'path' => 'ready',
                                    'expected' => 'yes',
                                    'comparator' => 'booleanEquals'
                                ]
                            ]
                        ]
                    ]
                ])
            ],
            'booleanEquals expected must be "true" or "false"'
        ];
        yield 'message on success' => [
            [
                'ThingReady' => self::validWaiter([
                    'acceptors' => [
                        [
                            'state' => 'success',
                            'matcher' => ['success' => true],
                            'message' => 'reason'
                        ]
                    ]
                ])
            ],
            'message is only valid for a failure state'
        ];
        yield 'tags type' => [
            [
                'ThingReady' => self::validWaiter([
                    'tags' => ['valid', 1]
                ])
            ],
            'tags must be a list of strings'
        ];
    }

    #[DataProvider('invalidTraits')]
    public function testRejectsInvalidTrait(
        mixed $trait,
        string $expectedMessage
    ): void
    {
        $model = $this->model($trait);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($expectedMessage);
        $model->getValidatedWaiters('smithy.example#GetThing');
    }

    public function testRejectsOutputMatcherWithoutOperationOutput(): void
    {
        $model = $this->model(
            ['ThingReady' => self::outputWaiter()],
            hasOutput: false
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'output matchers require an operation output'
        );
        $model->getValidatedWaiters('smithy.example#GetThing');
    }

    public function testRejectsEventStreamOperation(): void
    {
        $model = $this->model(
            ['ThingReady' => self::validWaiter()],
            eventStream: true
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'waiters cannot target an event-stream operation'
        );
        $model->getValidatedWaiters('smithy.example#GetThing');
    }

    public function testRejectsCaseInsensitiveDuplicateWithinOperation(): void
    {
        $model = $this->model([
            'ThingReady' => self::validWaiter(),
            'THINGREADY' => self::validWaiter()
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'waiter name must be case-insensitively unique'
        );
        $model->getValidatedWaiters('smithy.example#GetThing');
    }

    public function testRejectsDuplicateWaiterAcrossServiceClosure(): void
    {
        $model = $this->serviceModel([
            'smithy.example#GetThing' => [
                'ThingReady' => self::validWaiter()
            ],
            'smithy.example#DescribeThing' => [
                'THINGREADY' => self::validWaiter()
            ]
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('duplicate waiter name THINGREADY');
        $model->getValidatedServiceWaiters(
            'smithy.example#ExampleService'
        );
    }

    public function testReturnsWaitersGroupedByServiceOperation(): void
    {
        $model = $this->serviceModel([
            'smithy.example#GetThing' => [
                'ThingReady' => self::validWaiter()
            ],
            'smithy.example#DescribeThing' => [
                'ThingDeleted' => self::validWaiter()
            ]
        ]);

        $waiters = $model->getValidatedServiceWaiters(
            'smithy.example#ExampleService'
        );

        $this->assertSame(
            ['ThingReady'],
            \array_keys($waiters['smithy.example#GetThing'])
        );
        $this->assertSame(
            ['ThingDeleted'],
            \array_keys($waiters['smithy.example#DescribeThing'])
        );
    }

    /**
     * @param mixed $waiters
     */
    private function model(
        mixed $waiters,
        bool $hasOutput = true,
        bool $eventStream = false
    ): SmithyModel
    {
        $operation = [
            'type' => 'operation',
            'input' => ['target' => 'smithy.example#GetThingInput'],
            'traits' => [
                'smithy.waiters#waitable' => $waiters
            ]
        ];
        if ($hasOutput) {
            $operation['output'] = [
                'target' => 'smithy.example#GetThingOutput'
            ];
        }

        $inputMembers = [];
        $shapes = [
            'smithy.example#GetThing' => $operation,
            'smithy.example#GetThingOutput' => [
                'type' => 'structure',
                'members' => []
            ]
        ];
        if ($eventStream) {
            $inputMembers['events'] = [
                'target' => 'smithy.example#Events'
            ];
            $shapes['smithy.example#Events'] = [
                'type' => 'union',
                'members' => [],
                'traits' => [
                    'smithy.api#streaming' => new \stdClass()
                ]
            ];
        }
        $shapes['smithy.example#GetThingInput'] = [
            'type' => 'structure',
            'members' => $inputMembers
        ];

        return SmithyModel::fromJson(\json_encode(
            [
                'smithy' => '2.0',
                'shapes' => $shapes
            ],
            JSON_THROW_ON_ERROR
        ));
    }

    private function modelWithoutWaiters(): SmithyModel
    {
        return SmithyModel::fromJson(\json_encode(
            [
                'smithy' => '2.0',
                'shapes' => [
                    'smithy.example#GetThing' => [
                        'type' => 'operation'
                    ]
                ]
            ],
            JSON_THROW_ON_ERROR
        ));
    }

    /**
     * @param array<string, array<string, mixed>> $waitersByOperation
     */
    private function serviceModel(array $waitersByOperation): SmithyModel
    {
        $operations = [];
        $shapes = [
            'smithy.example#ExampleService' => [
                'type' => 'service',
                'operations' => []
            ]
        ];

        foreach ($waitersByOperation as $operationId => $waiters) {
            $operations[] = ['target' => $operationId];
            $shapes[$operationId] = [
                'type' => 'operation',
                'input' => ['target' => 'smithy.api#Unit'],
                'output' => ['target' => 'smithy.example#Output'],
                'traits' => [
                    'smithy.waiters#waitable' => $waiters
                ]
            ];
        }
        $shapes['smithy.example#ExampleService']['operations'] =
            $operations;
        $shapes['smithy.example#Output'] = [
            'type' => 'structure',
            'members' => []
        ];

        return SmithyModel::fromJson(\json_encode(
            [
                'smithy' => '2.0',
                'shapes' => $shapes
            ],
            JSON_THROW_ON_ERROR
        ));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function validWaiter(array $overrides = []): array
    {
        return \array_replace(
            [
                'acceptors' => [
                    [
                        'state' => 'success',
                        'matcher' => ['success' => true]
                    ]
                ]
            ],
            $overrides
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function outputWaiter(): array
    {
        return [
            'acceptors' => [
                [
                    'state' => 'success',
                    'matcher' => [
                        'output' => [
                            'path' => 'status',
                            'expected' => 'ready',
                            'comparator' => 'stringEquals'
                        ]
                    ]
                ]
            ]
        ];
    }
}
