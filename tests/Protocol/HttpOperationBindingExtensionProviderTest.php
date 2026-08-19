<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Protocol\HttpOperationBindingExtension;
use Smithy\Protocol\HttpOperationBindingExtensionProvider;
use Smithy\Tests\Protocol\Utils\HttpBindingOperationFixture;
use Smithy\Tests\Protocol\Utils\HttpBindingFixtureSchemas;

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
        self::assertNull($extension->pathQueryPairs);
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
            [['/things/', null, false], [null, 0, false]],
            $extension->pathSegments
        );
        self::assertSame([['fixed', 'true']], $extension->pathQueryPairs);
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
}
