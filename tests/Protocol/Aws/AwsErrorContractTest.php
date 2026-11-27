<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Core\Retry\RetrySafety;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\{
    HttpTransportResponse,
    Response
};
use Smithy\Protocol\Aws\{
    AwsJson1_0Protocol,
    AwsJson1_1Protocol,
    AwsProtocolException,
    AwsQueryProtocol,
    Ec2QueryProtocol,
    RestJsonErrorDeserializer,
    RestJsonProtocol,
    RestXmlProtocol
};
use Smithy\Protocol\ProtocolInterface;
use Smithy\ProtocolTests\AwsJson1_0;
use Smithy\ProtocolTests\AwsJson1_1;
use Smithy\ProtocolTests\AwsQuery;
use Smithy\ProtocolTests\Ec2Query;
use Smithy\ProtocolTests\RestJson;
use Smithy\ProtocolTests\RestXml;
use Smithy\Tests\Protocol\Utils\SdkBaseAwsError;

/**
 * Error contract shared by the six AWS protocols.
 *
 * Every protocol returns the same error code, request ID, HTTP status,
 * message and previous exception for the same logical error response.
 */
final class AwsErrorContractTest extends TestCase
{
    private const REQUEST_ID = 'req-1';

    /**
     * @return iterable<string, array{
     *     ProtocolInterface,
     *     OperationInterface,
     *     string,
     *     array<string, class-string>,
     *     \Closure(string, ?string): array{array<string, string>, string},
     *     string
     * }>
     */
    public static function protocolProvider(): iterable
    {
        $json = static fn(string $prefix): \Closure =>
            static fn(string $code, ?string $message): array => [
                [],
                \json_encode(
                    ['__type' => $prefix . $code]
                        + ($message !== null ? ['message' => $message] : [])
                )
            ];
        $restJson = static fn(string $code, ?string $message): array => [
            ['x-amzn-errortype' => $code],
            \json_encode($message !== null ? ['message' => $message] : [])
        ];
        $query = static fn(string $code, ?string $message): array => [
            [],
            '<ErrorResponse><Error><Type>Sender</Type>'
                . "<Code>{$code}</Code>"
                . ($message !== null ? "<Message>{$message}</Message>" : '')
                . '</Error><RequestId>' . self::REQUEST_ID
                . '</RequestId></ErrorResponse>'
        ];
        $ec2 = static fn(string $code, ?string $message): array => [
            [],
            "<Response><Errors><Error><Code>{$code}</Code>"
                . ($message !== null ? "<Message>{$message}</Message>" : '')
                . '</Error></Errors><RequestID>' . self::REQUEST_ID
                . '</RequestID></Response>'
        ];

        yield 'awsJson1_0' => [
            new AwsJson1_0Protocol(),
            AwsJson1_0\Operation\GreetingWithErrorsOperation::instance(),
            AwsJson1_0\Exception\AwsJson1_0Exception::class,
            [
                'InvalidGreeting' =>
                    AwsJson1_0\Exception\InvalidGreetingException::class,
                'ComplexError' =>
                    AwsJson1_0\Exception\ComplexErrorException::class
            ],
            $json('aws.protocoltests.json10#'),
            '{"__type":'
        ];
        yield 'awsJson1_1' => [
            new AwsJson1_1Protocol(),
            AwsJson1_1\Operation\GreetingWithErrorsOperation::instance(),
            AwsJson1_1\Exception\AwsJson1_1Exception::class,
            [
                'InvalidGreeting' =>
                    AwsJson1_1\Exception\InvalidGreetingException::class,
                'ComplexError' =>
                    AwsJson1_1\Exception\ComplexErrorException::class
            ],
            $json(''),
            '{"__type":'
        ];
        yield 'restJson1' => [
            new RestJsonProtocol(),
            RestJson\Operation\GreetingWithErrorsOperation::instance(),
            RestJson\Exception\RestJsonException::class,
            [
                'InvalidGreeting' =>
                    RestJson\Exception\InvalidGreetingException::class,
                'ComplexError' =>
                    RestJson\Exception\ComplexErrorException::class
            ],
            $restJson,
            '{"message":'
        ];
        yield 'restXml' => [
            new RestXmlProtocol(),
            RestXml\Operation\GreetingWithErrorsOperation::instance(),
            RestXml\Exception\RestXmlException::class,
            [
                'InvalidGreeting' =>
                    RestXml\Exception\InvalidGreetingException::class,
                'ComplexError' =>
                    RestXml\Exception\ComplexErrorException::class
            ],
            $query,
            '<ErrorResponse><Error>'
        ];
        yield 'awsQuery' => [
            new AwsQueryProtocol('2020-01-08'),
            AwsQuery\Operation\GreetingWithErrorsOperation::instance(),
            AwsQuery\Exception\AwsQueryException::class,
            [
                'InvalidGreeting' =>
                    AwsQuery\Exception\InvalidGreetingException::class,
                'ComplexError' =>
                    AwsQuery\Exception\ComplexErrorException::class
            ],
            $query,
            '<ErrorResponse><Error>'
        ];
        yield 'ec2Query' => [
            new Ec2QueryProtocol('2020-01-08'),
            Ec2Query\Operation\GreetingWithErrorsOperation::instance(),
            Ec2Query\Exception\Ec2QueryException::class,
            [
                'InvalidGreeting' =>
                    Ec2Query\Exception\InvalidGreetingException::class,
                'ComplexError' =>
                    Ec2Query\Exception\ComplexErrorException::class
            ],
            $ec2,
            '<Response><Errors>'
        ];
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testModeledErrorWithMessage(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        $error = $this->deserialize(
            $protocol,
            $operation,
            400,
            $body('InvalidGreeting', 'Hi')
        );

        $this->assertInstanceOf($modeled['InvalidGreeting'], $error);
        $this->assertContract(
            $error,
            'InvalidGreeting',
            self::REQUEST_ID,
            400,
            'Hi'
        );
        $this->assertNull($error->getPrevious());
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testModeledErrorWithoutMessageMember(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        $error = $this->deserialize(
            $protocol,
            $operation,
            400,
            $body('ComplexError', 'Envelope message')
        );

        $this->assertInstanceOf($modeled['ComplexError'], $error);
        $this->assertContract(
            $error,
            'ComplexError',
            self::REQUEST_ID,
            400,
            'Envelope message'
        );
        $this->assertNull($error->getPrevious());
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testModeledErrorWithoutAnyMessage(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        $error = $this->deserialize(
            $protocol,
            $operation,
            400,
            $body('ComplexError', null)
        );

        $this->assertInstanceOf($modeled['ComplexError'], $error);
        $this->assertContract(
            $error,
            'ComplexError',
            self::REQUEST_ID,
            400,
            'HTTP 400: ComplexError'
        );
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testUnmodeledErrorCode(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        $error = $this->deserialize(
            $protocol,
            $operation,
            400,
            $body('NoSuchThing', 'Nope')
        );

        $this->assertSame($serviceException, $error::class);
        $this->assertContract(
            $error,
            'NoSuchThing',
            self::REQUEST_ID,
            400,
            'Nope'
        );
        $this->assertNull($error->getPrevious());
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testMalformedBody(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body,
        string $malformedBody
    ): void
    {
        $error = $this->deserialize(
            $protocol,
            $operation,
            500,
            [[], $malformedBody]
        );

        $this->assertSame($serviceException, $error::class);
        $this->assertInstanceOf(AwsProtocolException::class, $error);
        $this->assertNull($error->getAwsErrorCode());
        $this->assertSame(self::REQUEST_ID, $error->getAwsRequestId());
        $this->assertSame(500, $error->getHttpStatusCode());
        $this->assertNotSame('', $error->getMessage());
        $this->assertInstanceOf(\Throwable::class, $error->getPrevious());
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testRetryAfterOnModeledError(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        [$headers, $payload] = $body('InvalidGreeting', 'Hi');
        $error = $this->deserialize(
            $protocol,
            $operation,
            429,
            [$headers + ['x-amz-retry-after' => '2000'], $payload]
        );

        $this->assertInstanceOf($modeled['InvalidGreeting'], $error);
        $this->assertContract(
            $error,
            'InvalidGreeting',
            self::REQUEST_ID,
            429,
            'Hi'
        );
        $this->assertSame(2.0, $error->getRetryAfter());
        $this->assertTrue($error->isThrottle());
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testRetryAfterOnUnmodeledError(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        [$headers, $payload] = $body('SlowDown', 'Slow down');
        $error = $this->deserialize(
            $protocol,
            $operation,
            503,
            [$headers + ['x-amz-retry-after' => '3000'], $payload]
        );

        $this->assertSame($serviceException, $error::class);
        $this->assertContract(
            $error,
            'SlowDown',
            self::REQUEST_ID,
            503,
            'Slow down'
        );
        $this->assertSame(3.0, $error->getRetryAfter());
        $this->assertSame(RetrySafety::Yes, $error->getRetrySafety());
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testHttpRetryAfterGivesNoHint(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        [$headers, $payload] = $body('SlowDown', 'Slow down');
        $error = $this->deserialize(
            $protocol,
            $operation,
            503,
            [$headers + ['retry-after' => '3'], $payload]
        );

        $this->assertSame($serviceException, $error::class);
        $this->assertNull($error->getRetryAfter());
    }

    /**
     * @param array<string, class-string> $modeled
     */
    #[DataProvider('protocolProvider')]
    public function testAmzRetryAfterIsUsedWithHttpRetryAfter(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        string $serviceException,
        array $modeled,
        \Closure $body
    ): void
    {
        [$headers, $payload] = $body('SlowDown', 'Slow down');
        $error = $this->deserialize(
            $protocol,
            $operation,
            503,
            [
                $headers + [
                    'retry-after' => '9',
                    'x-amz-retry-after' => '1500'
                ],
                $payload
            ]
        );

        $this->assertSame($serviceException, $error::class);
        $this->assertSame(1.5, $error->getRetryAfter());
    }

    public function testUnmodeledErrorUsesSdkBaseException(): void
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getServiceExceptionClass')
            ->willReturn(SdkBaseAwsError::class);

        $error = (new RestJsonErrorDeserializer(new JsonCodec()))
            ->deserialize($operation, new Response(
                503,
                [
                    'x-amzn-errortype' => 'SlowDown',
                    'x-amzn-requestid' => self::REQUEST_ID,
                    'x-amz-retry-after' => '3000'
                ],
                '{"message":"Slow down"}'
            ));

        $this->assertInstanceOf(SdkBaseAwsError::class, $error);
        $this->assertSame('Slow down', $error->getMessage());
        $this->assertSame('SlowDown', $error->getAwsErrorCode());
        $this->assertSame(self::REQUEST_ID, $error->getAwsRequestId());
        $this->assertSame(503, $error->getHttpStatusCode());
        $this->assertSame(3.0, $error->getRetryAfter());
        $this->assertSame(RetrySafety::Yes, $error->getRetrySafety());
    }

    public function testModeledErrorClassThatIsNotThrowableFallsBack(): void
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getServiceExceptionClass')
            ->willReturn(SdkBaseAwsError::class);
        $operation->method('resolveErrorSchema')->willReturn(new Schema(
            id: 'smithy.test#NotAnErrorRef',
            type: ShapeType::Structure,
            target: new Schema(
                id: 'smithy.test#NotAnError',
                type: ShapeType::Structure,
                targetClass: \stdClass::class
            )
        ));

        $error = (new RestJsonErrorDeserializer(new JsonCodec()))
            ->deserialize($operation, new Response(
                400,
                ['x-amzn-errortype' => 'NotAnError'],
                '{"message":"Nope"}'
            ));

        $this->assertInstanceOf(SdkBaseAwsError::class, $error);
        $this->assertSame('NotAnError', $error->getAwsErrorCode());
        $previous = $error->getPrevious();
        $this->assertInstanceOf(\UnexpectedValueException::class, $previous);
        $this->assertSame(
            'Modeled error class stdClass is not a Throwable.',
            $previous->getMessage()
        );
    }

    /**
     * @param array{array<string, string>, string} $response
     */
    private function deserialize(
        ProtocolInterface $protocol,
        OperationInterface $operation,
        int $status,
        array $response
    ): \Throwable
    {
        [$headers, $body] = $response;
        try {
            $protocol->deserializeResponse(
                $operation,
                new HttpTransportResponse(new Response(
                    $status,
                    $headers + ['x-amzn-requestid' => self::REQUEST_ID],
                    $body
                ))
            );
        } catch (\Throwable $e) {
            return $e;
        }

        $this->fail('Expected an error.');
    }

    private function assertContract(
        \Throwable $error,
        ?string $code,
        ?string $requestId,
        int $status,
        string $message
    ): void
    {
        $this->assertInstanceOf(AwsProtocolException::class, $error);
        $this->assertSame($code, $error->getAwsErrorCode());
        $this->assertSame($requestId, $error->getAwsRequestId());
        $this->assertSame($status, $error->getHttpStatusCode());
        $this->assertSame($message, $error->getMessage());
    }
}
