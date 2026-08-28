<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Http\Message\Stream;

#[CoversClass(Stream::class)]
final class StreamTest extends TestCase
{
    public function testCreatesTemporaryStreamFromString(): void
    {
        $stream = Stream::create('payload');

        $this->assertSame('payload', $stream->getContents());
    }

    public function testReadZeroReturnsEmptyString(): void
    {
        $stream = Stream::create('payload');

        $this->assertSame('', $stream->read(0));
        $this->assertSame('pay', $stream->read(3));
    }

    public function testReadRejectsNegativeLength(): void
    {
        $stream = Stream::create('payload');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Read length must be non-negative.'
        );

        $stream->read(-1);
    }

    public function testRejectsNonStreamResource(): void
    {
        $process = self::openProcessResource();

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage(
                'Stream requires a valid PHP stream resource.'
            );

            new Stream($process);
        } finally {
            if (\is_resource($process)) {
                \proc_close($process);
            }
        }
    }

    /**
     * @return resource
     */
    private static function openProcessResource()
    {
        $process = \proc_open(
            PHP_BINARY . ' -r ' . \escapeshellarg(''),
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );

        foreach ($pipes as $pipe) {
            if (\is_resource($pipe)) {
                \fclose($pipe);
            }
        }

        if (!\is_resource($process)) {
            self::fail('Unable to create test process resource.');
        }

        return $process;
    }
}
