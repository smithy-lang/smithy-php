<?php

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
