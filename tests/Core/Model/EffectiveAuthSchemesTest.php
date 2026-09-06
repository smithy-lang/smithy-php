<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Model;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Model\EffectiveAuthSchemes;

final class EffectiveAuthSchemesTest extends TestCase
{
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
