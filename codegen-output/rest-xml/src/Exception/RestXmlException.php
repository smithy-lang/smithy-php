<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Exception;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all RestXml service errors.
 *
 * Catch this to handle any error from the RestXml service,
 * whether modeled or unmodeled.
 */
class RestXmlException extends AwsProtocolException
{
    /**
     * Create an unmodeled RestXml error from AWS error metadata.
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
