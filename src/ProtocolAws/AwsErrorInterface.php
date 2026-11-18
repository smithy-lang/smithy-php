<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Protocol\Aws;

use Smithy\Core\Exception\ErrorFault;

/**
 * Contract for exceptions that carry AWS error metadata.
 *
 * Error deserializers build any exception class implementing this interface
 * with the AWS error code, request ID and HTTP status of the response, and
 * attach a `Retry-After` hint when the response has one.
 */
interface AwsErrorInterface
{
    public static function fromAwsError(
        string $message,
        ErrorFault $fault,
        ?\Throwable $previous,
        ?string $awsErrorCode,
        ?string $requestId,
        ?int $httpStatusCode
    ): static;

    public function setRetryAfter(?float $seconds): void;
}
