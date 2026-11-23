<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Model;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Model\EffectiveAuthSchemes;

final class EffectiveAuthSchemesTest extends TestCase
{
    public function testFindsPreludeAuthSchemesWithoutPreludeShapes(): void
    {
        foreach ([
            'smithy.api#httpApiKeyAuth',
            'smithy.api#httpBasicAuth',
            'smithy.api#httpBearerAuth',
            'smithy.api#httpDigestAuth'
        ] as $schemeId) {
            $this->assertSame(
                [$schemeId],
                EffectiveAuthSchemes::resolve(
                    [],
                    [$schemeId => []],
                    []
                )
            );
        }
    }

    public function testCountsKnownAuthSchemeIdsWithoutTraitShapes(): void
    {
        $this->assertSame(
            [
                'aws.auth#sigv4',
                'aws.auth#sigv4a',
                'smithy.api#httpBearerAuth'
            ],
            EffectiveAuthSchemes::resolve(
                [],
                [
                    'aws.auth#sigv4a' => [],
                    'smithy.api#httpBearerAuth' => [],
                    'aws.auth#sigv4' => ['name' => 'example'],
                    'aws.api#service' => []
                ],
                [],
                ['aws.auth#sigv4', 'aws.auth#sigv4a']
            )
        );
    }

    public function testKnownAuthSchemeIdsRequireServiceTrait(): void
    {
        $this->assertSame(
            [EffectiveAuthSchemes::NO_AUTH],
            EffectiveAuthSchemes::resolve(
                [],
                ['aws.api#service' => []],
                [],
                ['aws.auth#sigv4']
            )
        );
    }

    public function testExplicitAuthTakesPrecedenceOverKnownIds(): void
    {
        $this->assertSame(
            ['aws.auth#sigv4a'],
            EffectiveAuthSchemes::resolve(
                [],
                [
                    'aws.auth#sigv4' => [],
                    'aws.auth#sigv4a' => [],
                    'smithy.api#auth' => ['aws.auth#sigv4a']
                ],
                [],
                ['aws.auth#sigv4', 'aws.auth#sigv4a']
            )
        );
    }

    public function testUnknownTraitsWithoutDefinitionsFallBackToNoAuth(): void
    {
        $this->assertSame(
            [EffectiveAuthSchemes::NO_AUTH],
            EffectiveAuthSchemes::resolve(
                [],
                ['aws.auth#sigv4' => []],
                []
            )
        );
    }

    public function testTreatsNullMetadataAsAbsent(): void
    {
        $this->assertSame(
            ['example#ZAuth'],
            EffectiveAuthSchemes::resolve(
                [
                    'example#NullAuth' => [
                        'type' => 'structure',
                        'traits' => [
                            'smithy.api#authDefinition' => null
                        ]
                    ],
                    'example#ZAuth' => [
                        'type' => 'structure',
                        'traits' => [
                            'smithy.api#authDefinition' => []
                        ]
                    ]
                ],
                [
                    'example#NullAuth' => [],
                    'example#ZAuth' => [],
                    'smithy.api#auth' => null
                ],
                [
                    'smithy.api#auth' => null,
                    'smithy.api#optionalAuth' => null
                ]
            )
        );
    }

    public function testExplicitAuthIgnoresNullServiceTraits(): void
    {
        $this->assertSame(
            ['example#ZAuth'],
            EffectiveAuthSchemes::resolve(
                [],
                [
                    'example#AAuth' => null,
                    'example#ZAuth' => []
                ],
                [
                    'smithy.api#auth' => [
                        'example#AAuth',
                        'example#ZAuth'
                    ]
                ]
            )
        );
    }
}
