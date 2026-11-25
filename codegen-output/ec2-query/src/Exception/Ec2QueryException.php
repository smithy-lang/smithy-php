<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query\Exception;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all Ec2Query service errors.
 *
 * Catch this to handle any error from the Ec2Query service,
 * whether modeled or unmodeled.
 */
class Ec2QueryException extends AwsProtocolException
{
    /**
     * Create an unmodeled Ec2Query error from AWS error metadata.
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
