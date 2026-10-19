<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Dynamic\DynamicOperation;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\{
    SchemaConverter,
    TypeRegistry
};
use Smithy\Core\Shape\Document\StructDocument;
use Smithy\Http\Message\{
    HttpTransportRequest,
    HttpTransportResponse,
    Response
};
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;

#[CoversClass(DynamicOperation::class)]
final class DynamicOperationTest extends TestCase
{
    public function testCreatesSchemaBackedValuesAndRuntimeMetadata(): void
    {
        $operation = $this->operation();

        $input = $operation->createInputFromValue(['message' => 'hello']);
        $output = $operation->createOutput(['world']);

        self::assertInstanceOf(StructDocument::class, $input);
        self::assertInstanceOf(StructDocument::class, $output);
        self::assertSame('Call', $operation->getName());
        self::assertSame('example#Call', $operation->getId());
        self::assertSame('example#Service', $operation->getServiceId());
        self::assertNull($operation->getRequestTarget());
        self::assertSame(
            ['example#Auth', 'smithy.api#noAuth'],
            $operation->getEffectiveAuthSchemes()
        );
        self::assertSame(
            'hello',
            $input->getMemberValue(
                $operation->getInputSchema()->getMember('message')
            )
        );
    }

    public function testResolvesModeledErrorDiscriminators(): void
    {
        $operation = $this->operation();
        $schemas = $operation->getErrorSchemas();
        $schema = $operation->resolveErrorSchema('example#Failure');
        self::assertNotNull($schema);

        self::assertSame('example#Failure', $schema->id);
        self::assertSame(
            $schema,
            $operation->resolveErrorSchema('example#Failure')
        );
        self::assertSame(
            $schema,
            $operation->resolveErrorSchema('Failure')
        );
        self::assertSame(
            $schema,
            $operation->resolveErrorSchema('BadCall')
        );
        self::assertSame(
            ['example#ServiceFailure', 'example#Failure'],
            \array_map(
                static fn($error): string => $error->id,
                $schemas
            )
        );
        self::assertNull($operation->resolveErrorSchema('Unknown'));
    }

    public function testDetectsStreamingMembersAndScopesRuntimeIdentity(): void
    {
        $first = $this->operation('example#Upload');
        $second = $this->operation('example#Upload');

        self::assertSame(
            'data',
            $first->getInputStreamMember()?->memberName
        );
        self::assertSame(
            'events',
            $first->getOutputStreamMember()?->memberName
        );
        self::assertNotSame(
            $first->getRuntimeIdentity(),
            $second->getRuntimeIdentity()
        );
    }

    public function testUsesExistingProtocolImplementations(): void
    {
        $operation = $this->operation();
        $protocol = new RpcV2CborProtocol();
        $request = $protocol->serializeRequest(
            $operation,
            $operation->createInputFromValue(['message' => 'hello'])
        );
        $httpRequest = HttpTransportRequest::expect($request)->request;

        self::assertSame(
            '/service/Service/operation/Call',
            $httpRequest->getUri()->getPath()
        );

        $output = $protocol->deserializeResponse(
            $operation,
            new HttpTransportResponse(new Response(
                headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                body: (string) $httpRequest->getBody()
            ))
        );
        self::assertInstanceOf(StructDocument::class, $output);
        self::assertSame(
            'hello',
            $output->getMemberValue(
                $operation->getOutputSchema()->getMember('message')
            )
        );
    }

    public function testAppliesNestedDefaultsAfterDeserialization(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Service' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#Call']
                    ]
                ],
                'example#Call' => [
                    'type' => 'operation',
                    'output' => ['target' => 'example#Output']
                ],
                'example#Output' => [
                    'type' => 'structure',
                    'members' => [
                        'nested' => ['target' => 'example#Defaults'],
                        'items' => ['target' => 'example#DefaultsList'],
                        'values' => ['target' => 'example#DefaultsMap']
                    ],
                    'traits' => ['smithy.api#output' => []]
                ],
                'example#Defaults' => [
                    'type' => 'structure',
                    'members' => [
                        'value' => [
                            'target' => 'smithy.api#String',
                            'traits' => [
                                'smithy.api#default' => 'default'
                            ]
                        ]
                    ]
                ],
                'example#DefaultsList' => [
                    'type' => 'list',
                    'member' => ['target' => 'example#Defaults']
                ],
                'example#DefaultsMap' => [
                    'type' => 'map',
                    'key' => ['target' => 'smithy.api#String'],
                    'value' => ['target' => 'example#Defaults']
                ]
            ]
        ]);
        $converter = new SchemaConverter($model);
        $operation = new DynamicOperation(
            $model,
            new TypeRegistry($model, $converter),
            'example#Service',
            'example#Call'
        );

        $output = (new RpcV2CborProtocol())->deserializeResponse(
            $operation,
            new HttpTransportResponse(new Response(
                headers: ['Smithy-Protocol' => 'rpc-v2-cbor'],
                body: \hex2bin(
                    'a3666e6573746564a0656974656d7381a0'
                        . '6676616c756573a1656669727374a0'
                )
            ))
        );
        self::assertInstanceOf(StructDocument::class, $output);

        $schema = $operation->getOutputSchema();
        $nested = $output->getMemberValue($schema->getMember('nested'));
        self::assertInstanceOf(StructDocument::class, $nested);
        self::assertSame(
            'default',
            $nested->getMemberValue(
                $nested->getSchema()->getMember('value')
            )
        );

        $items = $output->getMemberValue($schema->getMember('items'));
        self::assertInstanceOf(StructDocument::class, $items[0]);
        self::assertSame(
            'default',
            $items[0]->getMemberValue(
                $items[0]->getSchema()->getMember('value')
            )
        );

        $values = $output->getMemberValue($schema->getMember('values'));
        self::assertInstanceOf(StructDocument::class, $values['first']);
        self::assertSame(
            'default',
            $values['first']->getMemberValue(
                $values['first']->getSchema()->getMember('value')
            )
        );
    }

    public function testResolvesTransformedErrorDiscriminators(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Service' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#Call']
                    ],
                    'errors' => [
                        ['target' => 'example#TransformedError']
                    ]
                ],
                'example#Call' => [
                    'type' => 'operation',
                    'errors' => [
                        ['target' => 'example#TransformedError']
                    ]
                ],
                'example#TransformedError' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#error' => 'client',
                        'smithy.synthetic#originalShapeId' =>
                            'original.example#OriginalError'
                    ]
                ]
            ]
        ]);
        $converter = new SchemaConverter($model);
        $operation = new DynamicOperation(
            $model,
            new TypeRegistry($model, $converter),
            'example#Service',
            'example#Call'
        );
        $schema = $operation->getErrorSchemas()[0];

        self::assertSame('original.example#OriginalError', $schema->id);
        self::assertCount(1, $operation->getErrorSchemas());
        self::assertSame(
            $schema,
            $operation->resolveErrorSchema('example#TransformedError')
        );
        self::assertSame(
            $schema,
            $operation->resolveErrorSchema(
                'original.example#OriginalError'
            )
        );
        self::assertSame(
            $schema,
            $operation->resolveErrorSchema('OriginalError')
        );
    }

    public function testReportsOriginalServiceIdAfterTransforms(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#TransformedService' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#TransformedOperation']
                    ],
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'original.example#CanonicalService'
                    ]
                ],
                'example#TransformedOperation' => [
                    'type' => 'operation',
                    'traits' => [
                        'smithy.synthetic#originalShapeId' =>
                            'original.example#CanonicalOperation'
                    ]
                ]
            ]
        ]);
        $converter = new SchemaConverter($model);
        $operation = new DynamicOperation(
            $model,
            new TypeRegistry($model, $converter),
            'example#TransformedService',
            'example#TransformedOperation',
            'smithy.protocols#rpcv2Cbor'
        );

        self::assertSame(
            'original.example#CanonicalService',
            $operation->getServiceId()
        );

        $request = (new RpcV2CborProtocol())->serializeRequest(
            $operation,
            $operation->createInputFromValue(null)
        );
        $request = HttpTransportRequest::expect($request)->request;
        self::assertSame(
            '/service/CanonicalService/operation/CanonicalOperation',
            $request->getUri()->getPath()
        );
    }

    public function testRejectsOperationOutsideSelectedService(): void
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#FirstService' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#FirstOperation']
                    ]
                ],
                'example#SecondService' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#SecondOperation']
                    ]
                ],
                'example#FirstOperation' => ['type' => 'operation'],
                'example#SecondOperation' => ['type' => 'operation']
            ]
        ]);
        $converter = new SchemaConverter($model);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Operation example#SecondOperation does not belong to service '
                . 'example#FirstService.'
        );

        new DynamicOperation(
            $model,
            new TypeRegistry($model, $converter),
            'example#FirstService',
            'example#SecondOperation'
        );
    }

    private function operation(
        string $operationId = 'example#Call'
    ): DynamicOperation
    {
        $model = SmithyModel::fromArray([
            'smithy' => '2.0',
            'shapes' => [
                'example#Service' => [
                    'type' => 'service',
                    'operations' => [
                        ['target' => 'example#Call'],
                        ['target' => 'example#Upload']
                    ],
                    'errors' => [
                        ['target' => 'example#ServiceFailure']
                    ],
                    'traits' => [
                        'example#Auth' => []
                    ]
                ],
                'example#Auth' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#authDefinition' => []
                    ]
                ],
                'example#Call' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#Message'],
                    'output' => ['target' => 'example#Message'],
                    'errors' => [
                        ['target' => 'example#Failure']
                    ],
                    'traits' => [
                        'smithy.api#optionalAuth' => []
                    ]
                ],
                'example#Upload' => [
                    'type' => 'operation',
                    'input' => ['target' => 'example#StreamInput'],
                    'output' => ['target' => 'example#StreamOutput']
                ],
                'example#Message' => [
                    'type' => 'structure',
                    'members' => [
                        'message' => ['target' => 'smithy.api#String']
                    ]
                ],
                'example#StreamInput' => [
                    'type' => 'structure',
                    'members' => [
                        'data' => ['target' => 'example#StreamingBlob']
                    ]
                ],
                'example#StreamOutput' => [
                    'type' => 'structure',
                    'members' => [
                        'events' => ['target' => 'example#Events']
                    ]
                ],
                'example#StreamingBlob' => [
                    'type' => 'blob',
                    'traits' => [
                        'smithy.api#streaming' => []
                    ]
                ],
                'example#Events' => [
                    'type' => 'union',
                    'members' => [
                        'message' => ['target' => 'smithy.api#String']
                    ],
                    'traits' => [
                        'smithy.api#streaming' => []
                    ]
                ],
                'example#Failure' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#error' => 'client',
                        'example#errorCode' => ['code' => 'BadCall']
                    ]
                ],
                'example#ServiceFailure' => [
                    'type' => 'structure',
                    'traits' => [
                        'smithy.api#error' => 'server'
                    ]
                ]
            ]
        ]);
        $converter = new SchemaConverter($model);

        return new DynamicOperation(
            $model,
            new TypeRegistry($model, $converter),
            'example#Service',
            $operationId,
            'smithy.protocols#rpcv2Cbor',
            ['example#errorCode' => 'code']
        );
    }
}
