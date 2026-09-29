<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\ShapeType;
use Smithy\Protocol\HttpStructBindingExtension;
use Smithy\Protocol\HttpStructBindingExtensionProvider;
use Smithy\Tests\Protocol\Utils\HttpBindingFixtureSchemas;
use Smithy\Tests\Protocol\Utils\HttpBindingIntEnumFixture;
use Smithy\Tests\Protocol\Utils\HttpBindingStringEnumFixture;

#[CoversClass(HttpStructBindingExtensionProvider::class)]
final class HttpStructBindingExtensionProviderTest extends TestCase
{
    public function testReturnsNullForNonAggregateSchema(): void
    {
        $schema = HttpBindingFixtureSchemas::string();

        self::assertNull(
            $schema->getExtension(HttpStructBindingExtensionProvider::key())
        );
    }

    public function testClassifiesHttpBoundMembers(): void
    {
        $schema = HttpBindingFixtureSchemas::providerRequest();

        $extension = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );

        self::assertInstanceOf(HttpStructBindingExtension::class, $extension);
        self::assertSame(
            HttpStructBindingExtension::ROUTE_HEADER,
            $extension->memberRoutes[0]
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_QUERY,
            $extension->memberRoutes[1]
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_LABEL,
            $extension->memberRoutes[2]
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_PREFIX_HEADERS,
            $extension->memberRoutes[3]
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_QUERY_PARAMS,
            $extension->memberRoutes[4]
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_BODY_MEMBER,
            $extension->memberRoutes[5]
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_RESPONSE_CODE,
            $extension->memberRoutes[6]
        );
        self::assertSame([5, 6], $extension->bodyMemberIndices);
        self::assertSame(
            [1, 2, 4, 5],
            $extension->responseBodyMemberIndices
        );
        self::assertSame('x-test', $extension->headerMembers[0]);
        self::assertSame(['x-test' => true], $extension->headerWireNames);
        self::assertSame('mode', $extension->queryMembers[1]);
        self::assertSame(['mode' => true], $extension->httpQueryNames);
        self::assertSame(2, $extension->requestTimestampKinds[0]);
        self::assertSame(1, $extension->requestTimestampKinds[2]);
        self::assertSame(
            TimestampFormat::HttpDate,
            $extension->scalarTimestampFormats[0]
        );
        self::assertSame(6, $extension->responseCodeMemberIndex);
        self::assertSame(
            'body',
            $extension->bodySchema?->getMemberByIndex(5)?->memberName
        );
        self::assertSame(
            'status',
            $extension->bodySchema?->getMemberByIndex(6)?->memberName
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_BODY_MEMBER,
            $extension->writeInfo[6][0]
        );
        self::assertSame(
            'mode',
            $extension->responseBodySchema?->getMemberByIndex(1)?->memberName
        );
        self::assertSame(
            'id',
            $extension->responseBodySchema?->getMemberByIndex(2)?->memberName
        );
        self::assertSame(
            'extra',
            $extension->responseBodySchema?->getMemberByIndex(4)?->memberName
        );
        self::assertSame(
            'body',
            $extension->responseBodySchema?->getMemberByIndex(5)?->memberName
        );
        self::assertNull(
            $extension->responseBodySchema?->getMemberByIndex(6)
        );
    }

    public function testStoresScalarEnumHeaderTargetClasses(): void
    {
        $schema = HttpBindingFixtureSchemas::response();

        $extension = $schema->getExtension(
            HttpStructBindingExtensionProvider::key()
        );

        self::assertSame(
            [0, 6, 17, HttpBindingStringEnumFixture::class],
            $extension->responseHeaders['x-state']
        );
        self::assertSame(
            [0, 7, 18, HttpBindingIntEnumFixture::class],
            $extension->responseHeaders['x-priority']
        );
    }

    public function testRejectsInvalidModeledHeaderName(): void
    {
        $schema = new Schema(
            id: 'smithy.test#InvalidHeaderRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#InvalidHeaderRequest$value',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0,
                    traits: [Schema::T_HTTP_HEADER => "X-Test\r\n"]
                ),
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP header names must be valid tokens.'
        );

        $schema->getExtension(HttpStructBindingExtensionProvider::key());
    }

    public function testRejectsInvalidModeledPrefixHeaderName(): void
    {
        $schema = new Schema(
            id: 'smithy.test#InvalidPrefixHeaderRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#InvalidPrefixHeaderRequest$values',
                    type: ShapeType::Map,
                    memberName: 'values',
                    memberIndex: 0,
                    traits: [Schema::T_HTTP_PREFIX_HEADERS => 'X Meta-']
                ),
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP header names must be valid tokens.'
        );

        $schema->getExtension(HttpStructBindingExtensionProvider::key());
    }
}
