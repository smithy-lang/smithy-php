<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Tests\Client\Plugin\Utils\{
    RecordingPluginFixture,
    SecondaryRecordingPluginFixture
};
use Smithy\Tests\Client\Fake\FakeProtocol;
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(PluginCollection::class)]
final class PluginCollectionTest extends TestCase
{
    public function testAppliesPluginsInOrder(): void
    {
        $calls = new \ArrayObject();
        $first = new RecordingPluginFixture('first', $calls);
        $second = new SecondaryRecordingPluginFixture('second', $calls);

        (new PluginCollection($first, $second))->apply($this->config());

        self::assertSame(['first', 'second'], $calls->getArrayCopy());
    }

    public function testAddAndPrependReturnNewOrderedCollections(): void
    {
        $calls = new \ArrayObject();
        $first = new RecordingPluginFixture('first', $calls);
        $second = new SecondaryRecordingPluginFixture('second', $calls);

        $empty = new PluginCollection();
        $added = $empty->add($first);
        $prepended = $added->prepend($second);

        $empty->apply($this->config());
        self::assertSame([], $calls->getArrayCopy());

        $prepended->apply($this->config());
        self::assertSame(['second', 'first'], $calls->getArrayCopy());
    }

    public function testFirstPluginOfEachConcreteClassWins(): void
    {
        $calls = new \ArrayObject();
        $first = new RecordingPluginFixture('first', $calls);
        $duplicate = new RecordingPluginFixture('duplicate', $calls);

        $collection = new PluginCollection($first, $duplicate);

        self::assertSame($collection, $collection->add($duplicate));
        self::assertSame($collection, $collection->prepend($duplicate));

        $collection->apply($this->config());
        self::assertSame(['first'], $calls->getArrayCopy());
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            )
        );
    }
}
