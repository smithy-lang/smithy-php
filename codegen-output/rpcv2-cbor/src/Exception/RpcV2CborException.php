<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor\Exception;

use Smithy\Core\Exception\ApiException;

/**
 * Base exception for all RpcV2Cbor service errors.
 *
 * Catch this to handle any error from the RpcV2Cbor service,
 * whether modeled or unmodeled.
 */
class RpcV2CborException extends ApiException {}
