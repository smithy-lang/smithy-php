<?php

declare(strict_types=1);

namespace Smithy\Xml;

use Smithy\Core\Serde\AbstractCodecSettings;
use Smithy\Core\Serde\TimestampFormat;

/**
 * Configure XML codec behavior.
 */
final class XmlCodecSettings extends AbstractCodecSettings
{
    /**
     * @param list<string> $wrapperElements Top-level wrapper element
     *     names to skip during deserialization.
     */
    public function __construct(
        ?string $serviceNamespace = null,
        TimestampFormat $defaultTimestampFormat
            = TimestampFormat::DateTime,
        public readonly array $wrapperElements = [],
        public readonly ?string $defaultXmlNamespace = null
    ) {
        parent::__construct($serviceNamespace, $defaultTimestampFormat);
    }
}
