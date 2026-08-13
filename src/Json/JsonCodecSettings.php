<?php

declare(strict_types=1);

namespace Smithy\Json;

use Smithy\Core\Serde\AbstractCodecSettings;
use Smithy\Core\Serde\TimestampFormat;

/**
 * Configure JSON codec behavior.
 */
final class JsonCodecSettings extends AbstractCodecSettings
{
    public function __construct(
        ?string $serviceNamespace = null,
        TimestampFormat $defaultTimestampFormat
            = TimestampFormat::EpochSeconds,
        public readonly bool $honorJsonName = true
    ) {
        parent::__construct($serviceNamespace, $defaultTimestampFormat);
    }
}
