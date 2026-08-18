<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\TimestampFormat;
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
            HttpStructBindingExtension::ROUTE_BODY,
            $extension->memberRoutes[5]
        );
        self::assertSame(
            HttpStructBindingExtension::ROUTE_RESPONSE_CODE,
            $extension->memberRoutes[6]
        );
        self::assertSame([5], $extension->bodyMemberIndices);
        self::assertSame('x-test', $extension->headerMembers[0]);
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
}
