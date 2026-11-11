<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson\Exception;

use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all RestJson service errors.
 *
 * Catch this to handle any error from the RestJson service,
 * whether modeled or unmodeled.
 */
class RestJsonException extends AwsProtocolException {}
