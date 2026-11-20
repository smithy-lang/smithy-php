<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Auth\Aws;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Auth\Aws\SigV4AuthScheme;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;

#[CoversClass(SigV4AuthScheme::class)]
final class SigV4AuthSchemeTest extends TestCase
{
    public function testReturnsSigningPropertiesWithoutOperation(): void
    {
        $scheme = new SigV4AuthScheme('s3', 'us-west-2');

        $this->assertSame(
            ['signingName' => 's3', 'signingRegion' => 'us-west-2'],
            $scheme->getSignerProperties([])
        );
    }

    public function testOmitsUnsignedPayloadWhenTraitIsAbsent(): void
    {
        $scheme = new SigV4AuthScheme('s3', 'us-west-2');

        $this->assertSame(
            ['signingName' => 's3', 'signingRegion' => 'us-west-2'],
            $scheme->getSignerProperties([], $this->operation([]))
        );
    }

    public function testSetsUnsignedPayloadFromOperationTrait(): void
    {
        $scheme = new SigV4AuthScheme('s3', 'us-west-2');

        $this->assertSame(
            [
                'signingName' => 's3',
                'signingRegion' => 'us-west-2',
                'unsignedPayload' => true,
            ],
            $scheme->getSignerProperties(
                [],
                $this->operation([SigV4AuthScheme::UNSIGNED_PAYLOAD_TRAIT => []])
            )
        );
    }

    /**
     * @param array<string, mixed> $traitsById
     */
    private function operation(array $traitsById): OperationInterface
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getSchema')->willReturn(new Schema(
            id: 'smithy.tests#Operation',
            type: ShapeType::Operation,
            traitsById: $traitsById
        ));

        return $operation;
    }
}
