<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Protocol\HttpStructBindingExtension;
use Smithy\Tests\Protocol\Utils\HttpBindingFixtureSchemas;

#[CoversClass(HttpStructBindingExtension::class)]
final class HttpStructBindingExtensionTest extends TestCase
{
    public function testStoresConstructorValues(): void
    {
        $schema = HttpBindingFixtureSchemas::request();
        $extension = new HttpStructBindingExtension(
            memberRoutes: [0 => HttpStructBindingExtension::ROUTE_HEADER],
            writeInfo: [
                0 => [HttpStructBindingExtension::ROUTE_HEADER, 'x-test']
            ],
            headerMembers: [0 => 'x-test'],
            headerWireNames: ['x-test' => true],
            bodyMemberIndices: [1],
            bodySchema: $schema,
            requestTimestampKinds: [2 => 1],
            responseCodeMemberIndex: 3,
            responseBodyMemberIndices: [4],
            responseBodySchema: $schema
        );

        self::assertSame(
            HttpStructBindingExtension::ROUTE_HEADER,
            $extension->memberRoutes[0]
        );
        self::assertSame('x-test', $extension->headerMembers[0]);
        self::assertSame(
            ['x-test' => true],
            $extension->headerWireNames
        );
        self::assertSame([1], $extension->bodyMemberIndices);
        self::assertSame($schema, $extension->bodySchema);
        self::assertSame(1, $extension->requestTimestampKinds[2]);
        self::assertSame(3, $extension->responseCodeMemberIndex);
        self::assertSame([4], $extension->responseBodyMemberIndices);
        self::assertSame($schema, $extension->responseBodySchema);
    }
}
