<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1\Exception;

use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all AwsJson1_1 service errors.
 *
 * Catch this to handle any error from the AwsJson1_1 service,
 * whether modeled or unmodeled.
 */
class AwsJson1_1Exception extends AwsProtocolException {}
