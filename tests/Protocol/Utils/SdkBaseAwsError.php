<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Exception\ApiException;
use Smithy\Core\Exception\ErrorFault;
use Smithy\Protocol\Aws\{
    AwsErrorInterface,
    AwsErrorRetryTrait
};

/**
 * Test fixture: an SDK-style base exception outside `AwsProtocolException`.
 *
 * @phpstan-consistent-constructor
 */
class SdkBaseAwsError extends ApiException implements AwsErrorInterface
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
