<?php

declare(strict_types=1);

namespace Smithy\Tests\Cbor\Utils;

use Smithy\Core\Serde\Schema;

/**
 * Provide a target class for schema-extension tests.
 */
final class CborSchemaFixture
{
    public static function schema(): Schema
    {
        throw new \LogicException('Not called in extension tests.');
    }
}
