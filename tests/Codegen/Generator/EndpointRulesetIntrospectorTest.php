<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Generator\EndpointRulesetIntrospector;
use Smithy\Codegen\Model\SmithyModel;

final class EndpointRulesetIntrospectorTest extends TestCase
{

    public function testParametersReturnsEmptyListWhenServiceHasNoRulesetTrait(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#PlainService' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => []
                ]
            ]
        ]));

        $introspector = new EndpointRulesetIntrospector($model);

        $this->assertSame(
            [],
            $introspector->parameters('smithy.example#PlainService')
        );
        $this->assertSame(
            [],
            $introspector->extraParameters('smithy.example#PlainService')
        );
    }

    public function testParametersReturnsEmptyListForUnknownServiceId(): void
    {
        $model = SmithyModel::fromJson('{"smithy": "2.0"}');
        $introspector = new EndpointRulesetIntrospector($model);

        $this->assertSame(
            [],
            $introspector->parameters('smithy.example#Nonexistent')
        );
    }

    public function testParametersReadsFromEndpointBddTrait(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [
                                'Region' => [
                                    'type' => 'string',
                                    'required' => true,
                                    'builtIn' => 'AWS::Region',
                                    'documentation' => 'The region to route to.'
                                ],
                                'ForcePathStyle' => [
                                    'type' => 'boolean',
                                    'default' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $params = $introspector->parameters('smithy.example#Svc');

        $this->assertCount(2, $params);
        $this->assertSame('Region', $params[0]->pascalName);
        $this->assertSame('region', $params[0]->phpPropertyName);
        $this->assertSame('string', $params[0]->phpType);
        $this->assertTrue($params[0]->required);
        $this->assertSame('AWS::Region', $params[0]->builtIn);
        $this->assertSame('The region to route to.', $params[0]->documentation);

        $this->assertSame('ForcePathStyle', $params[1]->pascalName);
        $this->assertSame('forcePathStyle', $params[1]->phpPropertyName);
        $this->assertSame('bool', $params[1]->phpType);
        $this->assertFalse($params[1]->required);
        $this->assertFalse($params[1]->default);
    }

    public function testParametersFallsBackToEndpointRuleSetTraitWhenBddAbsent(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointRuleSet' => [
                            'parameters' => [
                                'Endpoint' => [
                                    'type' => 'string',
                                    'builtIn' => 'SDK::Endpoint'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $params = $introspector->parameters('smithy.example#Svc');

        $this->assertCount(1, $params);
        $this->assertSame('Endpoint', $params[0]->pascalName);
    }

    public function testEndpointBddTakesPrecedenceOverEndpointRuleSetWhenBothPresent(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [
                                'FromBdd' => ['type' => 'string']
                            ]
                        ],
                        'smithy.rules#endpointRuleSet' => [
                            'parameters' => [
                                'FromRuleSet' => ['type' => 'string']
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $params = $introspector->parameters('smithy.example#Svc');

        $this->assertCount(1, $params);
        $this->assertSame('FromBdd', $params[0]->pascalName);
    }

    public function testExtraParametersFiltersOutBaseOwnedParameters(): void
    {
        // All four base-owned parameters plus one service-specific.
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [
                                'Region' => [
                                    'type' => 'string',
                                    'required' => true
                                ],
                                'UseFIPS' => [
                                    'type' => 'boolean',
                                    'default' => false
                                ],
                                'UseDualStack' => [
                                    'type' => 'boolean',
                                    'default' => false
                                ],
                                'Endpoint' => ['type' => 'string'],
                                'ForcePathStyle' => [
                                    'type' => 'boolean',
                                    'default' => false
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector(
            $model,
            [
                'Region',
                'UseFIPS',
                'UseDualStack',
                'Endpoint'
            ]
        );

        // The full list contains all five.
        $this->assertCount(5, $introspector->parameters('smithy.example#Svc'));

        // Only the non-base-owned parameter is left after filtering.
        $extra = $introspector->extraParameters('smithy.example#Svc');
        $this->assertCount(1, $extra);
        $this->assertSame('ForcePathStyle', $extra[0]->pascalName);
    }

    public function testExtraParametersPreservesOriginalOrderOfNonBaseParams(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [
                                'Alpha' => ['type' => 'string'],
                                'Region' => [
                                    'type' => 'string',
                                    'required' => true
                                ],
                                'Beta' => [
                                    'type' => 'boolean',
                                    'default' => false
                                ],
                                'UseFIPS' => [
                                    'type' => 'boolean',
                                    'default' => false
                                ],
                                'Gamma' => ['type' => 'string']
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector(
            $model,
            [
                'Region',
                'UseFIPS',
                'UseDualStack',
                'Endpoint'
            ]
        );

        $extra = $introspector->extraParameters('smithy.example#Svc');
        $names = array_map(static fn($p) => $p->pascalName, $extra);

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $names);
    }

    #[DataProvider('rulesetTypeMappings')]
    public function testMapsRulesetTypeToPhpScalarEquivalent(
        string $rulesetType,
        string $expectedPhpType
    ): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [
                                'P' => ['type' => $rulesetType]
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $params = $introspector->parameters('smithy.example#Svc');
        $this->assertSame($expectedPhpType, $params[0]->phpType);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function rulesetTypeMappings(): array
    {
        return [
            'string' => ['string', 'string'],
            'boolean' => ['boolean', 'bool'],
            'stringArray' => ['stringArray', 'array'],
            'lowercase boolean' => ['BOOLEAN', 'bool'],
            'lowercase stringarray' => ['StringArray', 'array'],
            'unknown type falls back to mixed' => ['weirdType', 'mixed'],
            'empty string falls back to mixed' => ['', 'mixed']
        ];
    }

    public function testParametersReturnsEmptyWhenRulesetIsNotAnArray(): void
    {
        // Malformed model: the ruleset trait is a raw string. Introspector
        // should
        // not blow up - it returns an empty list.
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => 'not-an-array'
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $this->assertSame([], $introspector->parameters('smithy.example#Svc'));
    }

    public function testParametersSkipsNonArrayParameterDefinitions(): void
    {
        // One well-formed parameter, one bad. The bad one is dropped silently.
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [
                                'Good' => ['type' => 'string'],
                                'Bad' => 'not-an-array'
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $params = $introspector->parameters('smithy.example#Svc');

        $this->assertCount(1, $params);
        $this->assertSame('Good', $params[0]->pascalName);
    }

    public function testMissingParametersBlockYieldsEmptyResult(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => []
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $this->assertSame([], $introspector->parameters('smithy.example#Svc'));
    }

    public function testDefaultIsPassedThroughVerbatimAndRequiredFlagsAreBooleanCoerced(): void
    {
        // Defaults stay unchanged. Required is coerced to bool.
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [
                                'BoolFlag' => [
                                    'type' => 'boolean',
                                    'default' => true,
                                    'required' => 1
                                ],
                                'StringDefault' => [
                                    'type' => 'string',
                                    'default' => 'us-east-1'
                                ],
                                'ArrayDefault' => [
                                    'type' => 'stringArray',
                                    'default' => ['a', 'b']
                                ],
                                'NoDefault' => [
                                    'type' => 'string'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]));
        $introspector = new EndpointRulesetIntrospector($model);

        $params = $introspector->parameters('smithy.example#Svc');

        $this->assertTrue($params[0]->default);
        $this->assertTrue(
            $params[0]->required,
            'integer 1 should coerce to true'
        );
        $this->assertSame('us-east-1', $params[1]->default);
        $this->assertSame(['a', 'b'], $params[2]->default);
        $this->assertNull($params[3]->default);
    }
}
