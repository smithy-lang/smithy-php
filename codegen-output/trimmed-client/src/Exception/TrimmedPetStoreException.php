<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore\Exception;

use Smithy\Core\Exception\ApiException;

/**
 * Base exception for all TrimmedPetStore service errors.
 *
 * Catch this to handle any error from the TrimmedPetStore service,
 * whether modeled or unmodeled.
 */
class TrimmedPetStoreException extends ApiException {}
