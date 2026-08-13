<?php

declare(strict_types=1);

namespace Smithy\Cbor;

use Smithy\Core\Serde\AbstractCodecSettings;
use Smithy\Core\Serde\TimestampFormat;

/**
 * Configure CBOR codec behavior.
 */
final class CborCodecSettings extends AbstractCodecSettings
{
    /**
     * @param string|null $serviceNamespace Service namespace for codec consumers.
     * @param TimestampFormat $defaultTimestampFormat Fallback timestamp format.
     */
    public function __construct(
        ?string $serviceNamespace = null,
        TimestampFormat $defaultTimestampFormat
            = TimestampFormat::EpochSeconds
    ) {
        parent::__construct($serviceNamespace, $defaultTimestampFormat);
    }
}
