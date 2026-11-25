<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Exception;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all AwsJson1_1 service errors.
 *
 * Catch this to handle any error from the AwsJson1_1 service,
 * whether modeled or unmodeled.
 */
class AwsJson1_1Exception extends AwsProtocolException
{
    /**
     * Create an unmodeled AwsJson1_1 error from AWS error metadata.
     */
    public static function fromAwsError(
        string $message,
        ErrorFault $fault,
        ?\Throwable $previous,
        ?string $awsErrorCode,
        ?string $requestId,
        ?int $httpStatusCode
    ): self
    {
        return new self(
            message: $message,
            fault: $fault,
            previous: $previous,
            awsErrorCode: $awsErrorCode,
            requestId: $requestId,
            httpStatusCode: $httpStatusCode
        );
    }
}
