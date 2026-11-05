<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Generator\EndpointRulesetParameter;

final class EndpointRulesetParameterTest extends TestCase
{
    public function testConstructorAssignsAllFieldsAndDefaults(): void
    {
        // Positional args exercise the full parameter list. Only the two
        // required fields are supplied. the rest fall through to defaults.
        $param = new EndpointRulesetParameter(
            pascalName: 'UseFIPS',
            phpPropertyName: 'useFIPS',
            phpType: 'bool'
        );

        $this->assertSame('UseFIPS', $param->pascalName);
        $this->assertSame('useFIPS', $param->phpPropertyName);
        $this->assertSame('bool', $param->phpType);
        $this->assertNull($param->default);
        $this->assertFalse($param->required);
        $this->assertNull($param->builtIn);
        $this->assertNull($param->documentation);
    }

    public function testConstructorRetainsRequiredAndBuiltInMetadata(): void
    {
        $param = new EndpointRulesetParameter(
            pascalName: 'Region',
            phpPropertyName: 'region',
            phpType: 'string',
            required: true,
            builtIn: 'AWS::Region',
            documentation: 'The AWS region to route requests to.'
        );

        $this->assertTrue($param->required);
        $this->assertSame('AWS::Region', $param->builtIn);
        $this->assertSame(
            'The AWS region to route requests to.',
            $param->documentation
        );
    }

    /**
     * @param mixed $default
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('defaultValueVariants')]
    public function testConstructorPreservesRawDefaultValue(mixed $default): void
    {
        $param = new EndpointRulesetParameter(
            pascalName: 'AnyParam',
            phpPropertyName: 'anyParam',
            phpType: 'mixed',
            default: $default
        );

        $this->assertSame($default, $param->default);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function defaultValueVariants(): array
    {
        return [
            'string default' => ['us-east-1'],
            'bool false default' => [false],
            'bool true default' => [true],
            'int default' => [0],
            'array default' => [['a', 'b']],
            'null default' => [null]
        ];
    }
}
