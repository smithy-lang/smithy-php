<?php

declare(strict_types=1);

namespace Smithy\Core\Serde;

/**
 * Shared codec settings.
 *
 * Carries service-level metadata that multiple wire formats may need,
 * along with the codec's fallback timestamp format when no explicit
 * `@timestampFormat` trait is present on the schema path being
 * serialized or deserialized, or when the protocol ignores that trait.
 */
abstract class AbstractCodecSettings
{
    /**
     * @param string|null $serviceNamespace Service namespace for codec consumers.
     * @param TimestampFormat $defaultTimestampFormat Fallback timestamp format.
     * @param bool $honorTimestampFormatTrait Whether to honor `timestampFormat`.
     */
    public function __construct(
        public readonly ?string $serviceNamespace = null,
        public readonly TimestampFormat $defaultTimestampFormat
            = TimestampFormat::DateTime,
        public readonly bool $honorTimestampFormatTrait = true
    ) {}
}
