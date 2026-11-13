<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query\Exception;

use Smithy\Protocol\Aws\AwsProtocolException;

/**
 * Base exception for all Ec2Query service errors.
 *
 * Catch this to handle any error from the Ec2Query service,
 * whether modeled or unmodeled.
 */
class Ec2QueryException extends AwsProtocolException {}
