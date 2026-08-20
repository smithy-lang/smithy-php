<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\StreamInterface;
use Smithy\Http\Message\Response;
use Smithy\Http\Message\ResponseInterface;
use Smithy\Http\Message\ResponseTrailers;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testImplementsResponseContracts(): void
    {
        $response = new Response();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(PsrResponseInterface::class, $response);
    }

    public function testCreatesFromNormalizedHeadersWithoutNormalization(): void
    {
        $headers = [
            'x-test' => ['X-Test', [" \tvalue\t "]]
        ];
        $response = Response::fromNormalized(headers: $headers);

        $this->assertSame($headers, $response->getRawHeaders());
    }

    public function testNormalHeaderOperationsPreserveOriginalResponse(): void
    {
        $response = new Response(
            headers: [
                'X-Test' => " \told\t ",
                'X-Remove' => 'remove',
            ],
        );

        $updated = $response
            ->withHeader('X-Test', " \tfirst\t ")
            ->withAddedHeader('X-Test', ['second'])
            ->withoutHeader('X-Remove')
            ->withProtocolVersion('2');

        $this->assertNotSame($response, $updated);
        $this->assertSame(['old'], $response->getHeader('x-test'));
        $this->assertTrue($response->hasHeader('x-remove'));
        $this->assertSame('1.1', $response->getProtocolVersion());

        $this->assertSame(['first', 'second'], $updated->getHeader('X-Test'));
        $this->assertSame('first, second', $updated->getHeaderLine('x-test'));
        $this->assertFalse($updated->hasHeader('x-remove'));
        $this->assertSame('2', $updated->getProtocolVersion());
    }

    public function testNormalizedHeadersCloneAndReplaceMatchingEntries(): void
    {
        $response = new Response(headers: [
            'X-First' => '1',
            'X-Replace' => 'old',
        ]);

        $updated = $response
            ->withNormalizedHeader(
                'x-single',
                'X-Single',
                [" \tvalue\t "],
            )
            ->withNormalizedHeaders([
                'x-second' => ['X-Second', ['2']],
                'x-replace' => ['X-REPLACE', ['new']],
            ]);

        $this->assertNotSame($response, $updated);
        $this->assertSame(['old'], $response->getHeader('x-replace'));
        $this->assertFalse($response->hasHeader('x-single'));
        $this->assertFalse($response->hasHeader('x-second'));

        $this->assertSame(['1'], $updated->getHeader('x-first'));
        $this->assertSame(['2'], $updated->getHeader('x-second'));
        $this->assertSame(['new'], $updated->getHeader('x-replace'));
        $this->assertSame(
            ['X-Single', [" \tvalue\t "]],
            $updated->getRawHeaders()['x-single'],
        );
        $this->assertSame(
            'X-REPLACE',
            $updated->getRawHeaders()['x-replace'][0],
        );
    }

    public function testNormalizedHeadersReturnSameInstanceForEmptyInput(): void
    {
        $response = new Response();

        $this->assertSame($response, $response->withNormalizedHeaders([]));
    }

    public function testNormalizedHeadersDoNotValidateInput(): void
    {
        $response = new Response();

        $updated = $response->withNormalizedHeaders([
            'wrong-key' => ['Bad Header', ["value\r\n"]],
        ]);

        $this->assertSame(
            ['Bad Header', ["value\r\n"]],
            $updated->getRawHeaders()['wrong-key'],
        );
    }

    public function testAcceptsHttp2ProtocolVersion(): void
    {
        $response = new Response(protocolVersion: '2');

        $this->assertSame('2', $response->getProtocolVersion());
    }

    public function testStatusMutationPreservesOriginalResponse(): void
    {
        $response = new Response(200);

        $updated = $response->withStatus(404);

        $this->assertNotSame($response, $updated);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame(404, $updated->getStatusCode());
        $this->assertSame('Not Found', $updated->getReasonPhrase());
    }

    public function testUsesCustomAndUnknownReasonPhrases(): void
    {
        $custom = new Response(299, reason: 'Custom');
        $unknown = new Response(299);

        $this->assertSame('Custom', $custom->getReasonPhrase());
        $this->assertSame('', $unknown->getReasonPhrase());
    }

    public function testPreservesProvidedTrailers(): void
    {
        $trailers = ResponseTrailers::completed([
            'X-Checksum' => 'checksum',
        ]);
        $response = new Response(trailers: $trailers);

        $this->assertSame($trailers, $response->getTrailers());
    }

    public function testDiscardBodyClosesBufferedStream(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->once())->method('close');
        $body->method('isSeekable')->willReturn(false);
        $body->method('getSize')->willReturn(null);
        $response = new Response(body: $body);

        $response->discardBody();
    }

    public function testRejectsInvalidStatusDuringConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Response(99);
    }

    public function testRejectsInvalidStatusDuringMutation(): void
    {
        $response = new Response();

        $this->expectException(\InvalidArgumentException::class);

        $response->withStatus(600);
    }

    public function testRejectsInvalidHeaderNameDuringConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Response(headers: ['Bad Header' => 'value']);
    }

    public function testRejectsInvalidHeaderValueDuringMutation(): void
    {
        $response = new Response();

        $this->expectException(\InvalidArgumentException::class);

        $response->withHeader(
            'X-Test',
            "value\r\nX-Injected: true",
        );
    }

    public function testRejectsNonStringAddedHeaderValue(): void
    {
        $response = new Response();

        $this->expectException(\InvalidArgumentException::class);

        $response->withAddedHeader('X-Test', 1);
    }

    public function testRegularConstructorRejectsNormalizedHeaders(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Response(headers: [
            'x-test' => [
                'X-Test',
                ["value\r\nX-Injected: true"],
            ],
        ]);
    }

    public function testRejectsInvalidProtocolVersionDuringMutation(): void
    {
        $response = new Response();

        $this->expectException(\InvalidArgumentException::class);

        $response->withProtocolVersion('HTTP/1.1');
    }

    public function testRejectsInvalidReasonPhrase(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Response(reason: "OK\r\nX-Injected: true");
    }
}
