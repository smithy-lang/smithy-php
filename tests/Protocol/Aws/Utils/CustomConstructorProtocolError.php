<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws\Utils;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Test fixture: a protocol error whose constructor takes modeled members
 * instead of the `AwsProtocolException` parameters.
 */
final class CustomConstructorProtocolError extends AwsProtocolException
{
    public function __construct(public readonly ?string $type = null)
    {
        parent::__construct('modeled', ErrorFault::Client);
    }
}
