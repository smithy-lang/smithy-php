<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery\Exception;

use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all AwsQuery service errors.
 *
 * Catch this to handle any error from the AwsQuery service,
 * whether modeled or unmodeled.
 */
class AwsQueryException extends AwsProtocolException {}
