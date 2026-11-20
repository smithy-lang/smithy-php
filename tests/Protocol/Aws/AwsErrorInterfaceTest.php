<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws;

use PHPUnit\Framework\TestCase;
use Smithy\Codec\Json\JsonCodec;
use Smithy\Core\Exception\ApiException;
use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Retry\RetrySafety;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Http\Message\Response;
use Smithy\Protocol\Aws\{
    AwsErrorInterface,
    AwsErrorRetryTrait,
    RestJsonErrorDeserializer
};

/**
 * Error deserializers populate any exception class that implements
 * `AwsErrorInterface`, not only `AwsProtocolException`.
 */
final class AwsErrorInterfaceTest extends TestCase
{
    public function testDeserializerPopulatesInterfaceImplementer(): void
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getServiceExceptionClass')
            ->willReturn(CustomAwsError::class);
        $response = new Response(
            503,
            [
                'x-amzn-errortype' => 'SlowDown',
                'x-amzn-requestid' => 'req-1',
                'retry-after' => '3'
            ],
            '{"message":"Slow down"}'
        );

        $error = (new RestJsonErrorDeserializer(new JsonCodec()))
            ->deserialize($operation, $response);

        $this->assertInstanceOf(CustomAwsError::class, $error);
        $this->assertSame('Slow down', $error->getMessage());
        $this->assertSame('SlowDown', $error->getAwsErrorCode());
        $this->assertSame('req-1', $error->getAwsRequestId());
        $this->assertSame(503, $error->getHttpStatusCode());
        $this->assertSame(3.0, $error->getRetryAfter());
        $this->assertSame(RetrySafety::Yes, $error->getRetrySafety());
    }
}

/**
 * Test fixture: an SDK-style base exception outside `AwsProtocolException`.
 *
 * @phpstan-consistent-constructor
 */
class CustomAwsError extends ApiException implements AwsErrorInterface
{
    use AwsErrorRetryTrait;

    public function __construct(
        string $message = '',
        ErrorFault $fault = ErrorFault::Other,
        ?\Throwable $previous = null,
        private ?string $awsErrorCode = null,
        private ?string $requestId = null,
        private ?int $httpStatusCode = null
    ) {
        parent::__construct($message, $fault, $previous);
    }

    public function getAwsErrorCode(): ?string
    {
        return $this->awsErrorCode;
    }

    public function getAwsRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public static function fromAwsError(
        string $message,
        ErrorFault $fault,
        ?\Throwable $previous,
        ?string $awsErrorCode,
        ?string $requestId,
        ?int $httpStatusCode
    ): static {
        return new static($message, $fault, $previous, $awsErrorCode, $requestId, $httpStatusCode);
    }

    public function setAwsErrorMetadata(
        string $message,
        ?string $awsErrorCode,
        ?string $requestId,
        ?int $httpStatusCode
    ): void {
        if ($this->message === '') {
            $this->message = $message;
        }
        $this->awsErrorCode = $awsErrorCode;
        $this->requestId = $requestId;
        $this->httpStatusCode = $httpStatusCode;
    }
}
