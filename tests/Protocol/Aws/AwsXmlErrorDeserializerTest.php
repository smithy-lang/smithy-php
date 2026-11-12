<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Smithy\Async\RevoltEventLoop;
use Smithy\Codec\Xml\XmlCodec;
use Smithy\Http\Message\{
    Response,
    StreamingResponseStream
};
use Smithy\Protocol\Aws\{
    AwsProtocolException,
    AwsXmlErrorDeserializer
};
use Smithy\ProtocolTests\RestXml\Operation\GreetingWithErrorsOperation;

final class AwsXmlErrorDeserializerTest extends TestCase
{
    #[DataProvider('requestIdHeaders')]
    public function testEmptyBodyPreservesHeadersWithoutReadingStream(
        string $header
    ): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->expects(self::never())->method('__toString');
        $body->expects(self::never())->method('getSize');
        $body->expects(self::never())->method('getContents');

        $error = (new AwsXmlErrorDeserializer(new XmlCodec()))->deserialize(
            GreetingWithErrorsOperation::instance(),
            new Response(
                400,
                ['Content-Length' => '0', $header => 'header-request-id'],
                $body
            )
        );

        self::assertInstanceOf(AwsProtocolException::class, $error);
        self::assertSame('header-request-id', $error->getAwsRequestId());
        self::assertSame('Unknown', $error->getAwsErrorCode());
        self::assertSame(400, $error->getHttpStatusCode());
        self::assertNull($error->getPrevious());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function requestIdHeaders(): iterable
    {
        yield 'amz' => ['x-amz-request-id'];
        yield 'amzn' => ['x-amzn-requestid'];
    }

    #[DataProvider('unmodeledEnvelopes')]
    public function testReadsNamespaceQualifiedMetadata(
        string $body
    ): void
    {
        $error = (new AwsXmlErrorDeserializer(new XmlCodec()))->deserialize(
            GreetingWithErrorsOperation::instance(),
            new Response(
                400,
                ['x-amz-request-id' => 'header-request-id'],
                $body
            )
        );

        self::assertInstanceOf(AwsProtocolException::class, $error);
        self::assertSame('UnmodeledError', $error->getAwsErrorCode());
        self::assertSame('Bad input', $error->getMessage());
        self::assertSame('body-request-id', $error->getAwsRequestId());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unmodeledEnvelopes(): iterable
    {
        yield 'prefixed envelope' => [<<<'XML'
<a:ErrorResponse xmlns:a="urn:test">
    <a:Error>
        <a:Code>UnmodeledError</a:Code>
        <a:Message>Bad input</a:Message>
    </a:Error>
    <a:RequestId>body-request-id</a:RequestId>
</a:ErrorResponse>
XML];
        yield 'prefixed errors wrapper' => [<<<'XML'
<a:Response xmlns:a="urn:test">
    <a:Errors>
        <a:Error>
            <a:Code>UnmodeledError</a:Code>
            <a:Message>Bad input</a:Message>
            <a:RequestId>body-request-id</a:RequestId>
        </a:Error>
    </a:Errors>
</a:Response>
XML];
        yield 'prefixed root error' => [<<<'XML'
<a:Error xmlns:a="urn:test">
    <a:Code>UnmodeledError</a:Code>
    <a:Message>Bad input</a:Message>
    <a:RequestID>body-request-id</a:RequestID>
</a:Error>
XML];
        yield 'default namespace' => [<<<'XML'
<ErrorResponse xmlns="urn:test">
    <Error>
        <Code>UnmodeledError</Code>
        <Message>Bad input</Message>
    </Error>
    <RequestId>body-request-id</RequestId>
</ErrorResponse>
XML];
        yield 'ordinary envelope' => [
            '<ErrorResponse><Error><Code>UnmodeledError</Code>'
            . '<Message>Bad input</Message></Error>'
            . '<RequestId>body-request-id</RequestId></ErrorResponse>'
        ];
        yield 'ordinary errors wrapper' => [
            '<Response><Errors><Error><Code>UnmodeledError</Code>'
            . '<Message>Bad input</Message>'
            . '<RequestID>body-request-id</RequestID>'
            . '</Error></Errors></Response>'
        ];
        yield 'ordinary root error' => [
            '<?xml version="1.0"?><Error><Code>UnmodeledError</Code>'
            . '<Message>Bad input</Message>'
            . '<RequestId>body-request-id</RequestId></Error>'
        ];
        yield 'prefix text in message' => [
            '<Error><Code>UnmodeledError</Code>'
            . '<Message>Bad input</Message>'
            . '<Detail><![CDATA[xmlns:a="urn:test"]]></Detail>'
            . '<RequestID>body-request-id</RequestID></Error>'
        ];
        $xml = '<?xml version="1.0" encoding="UTF-16"?>'
            . '<a:Error xmlns:a="urn:test"><a:Code>UnmodeledError</a:Code>'
            . '<a:Message>Bad input</a:Message>'
            . '<a:RequestId>body-request-id</a:RequestId></a:Error>';
        yield 'UTF-16 prefixed root' => [
            "\xff\xfe" . \implode("\0", \str_split($xml)) . "\0"
        ];
    }

    public function testPreservesStreamReadFailure(): void
    {
        $failure = new \RuntimeException('Response read failed.');
        $body = new StreamingResponseStream(
            new RevoltEventLoop(),
            1024,
            static function (): void {},
            static function (): void {}
        );
        $body->fail($failure);

        $error = (new AwsXmlErrorDeserializer(new XmlCodec()))->deserialize(
            GreetingWithErrorsOperation::instance(),
            new Response(500, [], $body)
        );

        self::assertInstanceOf(AwsProtocolException::class, $error);
        self::assertSame($failure, $error->getPrevious());
        self::assertSame('Response read failed.', $error->getMessage());
    }
}
