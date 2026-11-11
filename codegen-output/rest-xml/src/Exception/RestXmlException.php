<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Exception;

use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all RestXml service errors.
 *
 * Catch this to handle any error from the RestXml service,
 * whether modeled or unmodeled.
 */
class RestXmlException extends AwsProtocolException {}
