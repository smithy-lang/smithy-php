<?php

declare(strict_types=1);

namespace Smithy\FormUrlEncoded;

use Smithy\Core\Serde\AbstractCodecSettings;
use Smithy\Core\Serde\TimestampFormat;

/**
 * Configure form-urlencoded codec behavior.
 */
final class FormUrlEncodedCodecSettings extends AbstractCodecSettings
{
    public function __construct(
        ?string $serviceNamespace = null,
        TimestampFormat $defaultTimestampFormat
            = TimestampFormat::DateTime,
        public readonly bool $useAlternateWireNames = false,
        public readonly bool $alwaysFlattenLists = false,
        public readonly bool $supportsMaps = true
    ) {
        parent::__construct($serviceNamespace, $defaultTimestampFormat);
    }
}
