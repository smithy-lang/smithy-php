<?php

declare(strict_types=1);

namespace Smithy\Tests\Json;

use PHPUnit\Framework\TestCase;
use Smithy\Json\{
    JsonCodec,
    JsonDeserializer,
    JsonSerializer
};
use Smithy\Tests\Json\Fixture\SampleStruct;

final class JsonCodecTest extends TestCase
{
    public function testCreateSerializerReturnsIndependentInstances(): void
    {
        $codec = new JsonCodec();

        $first = $codec->createSerializer();
        $second = $codec->createSerializer();

        self::assertInstanceOf(JsonSerializer::class, $first);
        self::assertInstanceOf(JsonSerializer::class, $second);
        self::assertNotSame($first, $second);

        $alpha = new SampleStruct(name: 'alpha');
        $alpha->serialize($first, 0);
        self::assertSame('{"name":"alpha"}', $first->flush());

        $beta = new SampleStruct(name: 'beta');
        $beta->serialize($second, 0);

        self::assertSame('{"name":"beta"}', $second->flush());
    }

    public function testAcquireAndReleaseSerializerReusesInstance(): void
    {
        $codec = new JsonCodec();

        $first = $codec->acquireSerializer();
        self::assertInstanceOf(JsonSerializer::class, $first);

        (new SampleStruct(name: 'alpha'))->serialize($first, 0);
        self::assertSame('{"name":"alpha"}', $first->flush());
        $codec->releaseSerializer($first);

        $second = $codec->acquireSerializer();
        self::assertSame($first, $second);

        (new SampleStruct(name: 'beta'))->serialize($second, 0);
        self::assertSame('{"name":"beta"}', $second->flush());
    }

    public function testCreateDeserializerReturnsJsonDeserializer(): void
    {
        $codec = new JsonCodec();

        self::assertInstanceOf(
            JsonDeserializer::class,
            $codec->createDeserializer('{}')
        );
    }
}
