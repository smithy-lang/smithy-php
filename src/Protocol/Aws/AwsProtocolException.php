<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Protocol\Aws;

use Smithy\Core\Exception\ApiException;
use Smithy\Core\Exception\ErrorFault;

/**
 * Base exception for errors returned by AWS protocols.
 */
class AwsProtocolException extends ApiException
{
    public function __construct(
        string $message = '',
        ErrorFault $fault = ErrorFault::Other,
        ?\Throwable $previous = null,
        public readonly ?string $awsErrorCode = null,
        public readonly ?string $requestId = null,
        public readonly ?int $httpStatusCode = null
    )
    {
        parent::__construct($message, $fault, $previous);
    }
}
