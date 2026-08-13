<?php

declare(strict_types=1);

namespace Smithy\Core\Serde;

/**
 * Shared codec settings.
 *
 * Carries service-level metadata that multiple wire formats may need,
 * along with the codec's fallback timestamp format when no explicit
 * `@timestampFormat` trait is present on the schema path being
 * serialized or deserialized.
 */
abstract class AbstractCodecSettings
{
    public function __construct(
        public readonly ?string $serviceNamespace = null,
        public readonly TimestampFormat $defaultTimestampFormat
            = TimestampFormat::DateTime
    ) {}
}
