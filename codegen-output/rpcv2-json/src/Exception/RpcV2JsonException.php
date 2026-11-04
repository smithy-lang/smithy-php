<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json\Exception;

use Smithy\Core\Exception\ApiException;

/**
 * Base exception for all RpcV2Json service errors.
 *
 * Catch this to handle any error from the RpcV2Json service,
 * whether modeled or unmodeled.
 */
class RpcV2JsonException extends ApiException {}
