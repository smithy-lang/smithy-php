<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Http\Message\HttpTransportRequest;
use Smithy\Protocol\AbstractHttpBindingProtocol;
use Smithy\Protocol\HttpErrorDeserializer;
use Smithy\Protocol\HttpOperationBindingExtension;
use Smithy\Protocol\HttpOperationBindingExtensionProvider;
use Smithy\Protocol\ProtocolSettings;
use Smithy\Tests\Protocol\Utils\HttpBindingOperationFixture;
use Smithy\Tests\Protocol\Utils\HttpBindingFixtureSchemas;
use Smithy\Tests\Protocol\Utils\HttpBindingInputShapeFixture;

#[CoversClass(AbstractHttpBindingProtocol::class)]
#[CoversClass(HttpErrorDeserializer::class)]
#[CoversClass(HttpOperationBindingExtensionProvider::class)]
final class HttpOperationBindingExtensionProviderTest extends TestCase
{
    public function testReturnsDefaultsWhenHttpTraitMissing(): void
    {
        $schema = HttpBindingFixtureSchemas::operation();

        $extension = $schema->getExtension(
            HttpOperationBindingExtensionProvider::key()
        );

        self::assertInstanceOf(
            HttpOperationBindingExtension::class,
            $extension
        );
        self::assertSame('POST', $extension->method);
        self::assertSame('/', $extension->path);
        self::assertNull($extension->pathSegments);
        self::assertNull($extension->pathQueryString);
        self::assertNull($extension->httpResponseCode);
    }

    public function testBuildsExtensionFromHttpTrait(): void
    {
        $operation = HttpBindingOperationFixture::instance();
        $schema = $operation->getSchema();

        $extension = $schema->getExtension(
            HttpOperationBindingExtensionProvider::key()
        );

        self::assertSame('GET', $extension->method);
        self::assertSame('/things/{id}', $extension->path);
        self::assertSame(
            [['/things/', null, false], [null, 2, false]],
            $extension->pathSegments
        );
        self::assertSame(
            'fixed=true&flag',
            $extension->pathQueryString
        );
        self::assertSame(206, $extension->httpResponseCode);
        self::assertSame(
            HttpBindingOperationFixture::class,
            $operation->getRuntimeIdentity()
        );
        self::assertSame(
            HttpBindingOperationFixture::inputSchema(),
            $operation->getInputSchema()
        );
        self::assertSame(
            HttpBindingOperationFixture::outputSchema(),
            $operation->getOutputSchema()
        );
        self::assertInstanceOf(
            \Smithy\Tests\Protocol\Utils\HttpBindingInputShapeFixture::class,
            $operation->createInput([])
        );
        self::assertInstanceOf(
            \Smithy\Tests\Protocol\Utils\HttpBindingOutputShapeFixture::class,
            $operation->createOutput([])
        );
        self::assertNull($operation->getRequestTarget());
        self::assertNull($operation->resolveErrorSchema('Unknown'));
    }

    public function testProtocolSerializesStaticOperationQuery(): void
    {
        $codec = new JsonCodec();
        $errorDeserializer = new class($codec) extends HttpErrorDeserializer {
            protected function parseError(
                ResponseInterface $response
            ): array
            {
                throw new \LogicException('Not used by this test.');
            }
        };
        $protocol = new class(
            $codec,
            $errorDeserializer,
            new ProtocolSettings()
        ) extends AbstractHttpBindingProtocol {
            protected static string $defaultContentType = 'application/json';

            public function getId(): string
            {
                return 'smithy.tests#httpBinding';
            }
        };
        $input = new HttpBindingInputShapeFixture(
            mode: true,
            id: 'thing'
        );

        $request = $protocol->serializeRequest(
            HttpBindingOperationFixture::instance(),
            $input
        );
        $request = HttpTransportRequest::expect($request)->request;

        self::assertSame(
            'mode=true&fixed=true&flag',
            $request->getUri()->getQuery()
        );
    }

    public function testProtocolBindsEndpointAfterRelativeSerialization(): void
    {
        $codec = new JsonCodec();
        $errorDeserializer = new class($codec) extends HttpErrorDeserializer {
            protected function parseError(
                ResponseInterface $response
            ): array
            {
                throw new \LogicException('Not used by this test.');
            }
        };
        $protocol = new class(
            $codec,
            $errorDeserializer,
            new ProtocolSettings()
        ) extends AbstractHttpBindingProtocol {
            protected static string $defaultContentType = 'application/json';

            public function getId(): string
            {
                return 'smithy.tests#httpBinding';
            }
        };
        $input = new HttpBindingInputShapeFixture(
            mode: true,
            id: 'thing'
        );
        $transportRequest = $protocol->serializeRequest(
            HttpBindingOperationFixture::instance(),
            $input
        );
        $request = HttpTransportRequest::expect($transportRequest)->request;

        self::assertSame(
            '/things/thing?mode=true&fixed=true&flag',
            (string) $request->getUri()
        );
        self::assertSame('', $request->getHeaderLine('Host'));

        $transportRequest = $protocol->setServiceEndpoint(
            $transportRequest,
            new Endpoint('https://example.com/base')
        );
        $request = HttpTransportRequest::expect($transportRequest)->request;

        self::assertSame(
            'https://example.com/base/things/thing?mode=true&fixed=true&flag',
            (string) $request->getUri()
        );
        self::assertSame('example.com', $request->getHeaderLine('Host'));
    }
}
