<?php

declare(strict_types=1);

namespace Smithy\Protocol;

use Psr\Http\Message\StreamInterface;
use Smithy\Core\Serde\AbstractCodec;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeDeserializerInterface;
use Smithy\Core\Serde\TimestampFormat;
use Smithy\Core\Shape\Document\AbstractDocument;

/**
 * Deserialize HTTP-bound response data into positional shape members.
 *
 * @internal Runtime deserializer for HTTP binding protocols.
 */
final class HttpBindingDeserializer implements ShapeDeserializerInterface
{
    /** @var array<string, array<int, mixed>> */
    private readonly array $responseHeaders;

    /**
     * Headers keyed by lowercase name.
     *
     * @var array<string, array{string, list<string>}>
     */
    private readonly array $headers;

    private readonly ?StreamInterface $body;

    private int $bodySize = -1;

    private readonly int $statusCode;

    public function __construct(
        private readonly AbstractCodec $codec,
        private readonly HttpStructBindingExtension $bindingInfo,
        array $headers,
        ?StreamInterface $body,
        int $statusCode = 0
    )
    {
        $this->headers = $headers;
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->responseHeaders = $bindingInfo->responseHeaders;
        if (isset($headers['content-length'][1][0])) {
            $this->bodySize = (int) $headers['content-length'][1][0];
        }
    }

    /**
     * Deserialize an HTTP-bound output shape.
     *
     * @return void
     */
    public function deserialize(Schema $schema, array &$members): void
    {
        $bindingInfo = $this->bindingInfo;
        $responseHeaders = $this->responseHeaders;
        $headers = $this->headers;
        $scalarTimestampFormats = $bindingInfo->scalarTimestampFormats;

        if (\count($headers) <= \count($responseHeaders)) {
            foreach ($headers as $lowerName => $entry) {
                $binding = $responseHeaders[$lowerName] ?? null;
                if ($binding === null) {
                    continue;
                }

                $idx = $binding[1];
                if ($binding[0] === 0) {
                    switch ($binding[2]) {
                        case 10:  // ShapeType::String_
                            $members[$idx] = isset($binding[3])
                                ? \base64_decode($entry[1][0], true)
                                : $entry[1][0];
                            break;
                        case 17:  // ShapeType::Enum
                            $members[$idx] = $entry[1][0];
                            break;
                        case 4:  // ShapeType::Integer
                        case 5:  // ShapeType::Long
                        case 3: // ShapeType::Short
                        case 2: // ShapeType::Byte
                            $members[$idx] = (int) $entry[1][0];
                            break;
                        case 18:  // ShapeType::IntEnum
                            $members[$idx] = (int) $entry[1][0];
                            break;
                        case 1: // ShapeType::Boolean
                            $members[$idx] = $entry[1][0] === 'true';
                            break;
                        case 6: // ShapeType::Float_
                        case 7: // ShapeType::Double
                            $members[$idx] = $this->parseHeaderFloat(
                                $entry[1][0]
                            );
                            break;
                        case 12: // ShapeType::Timestamp
                            $members[$idx] = $scalarTimestampFormats[$idx]
                                ->parse($entry[1][0]);
                            break;
                        default:
                            $members[$idx] = $entry[1][0];
                            break;
                    }
                } else {
                    switch ($binding[3]) {
                        case 12: // ShapeType::Timestamp
                            $members[$idx] = $this->parseTimestampList(
                                $entry[1],
                                $idx
                            );
                            break;
                        case 4:  // ShapeType::Integer
                        case 5:  // ShapeType::Long
                        case 3: // ShapeType::Short
                        case 2: // ShapeType::Byte
                            $members[$idx] = $this->parseIntegerList($entry[1]);
                            break;
                        case 1: // ShapeType::Boolean
                            $members[$idx] = $this->parseBooleanList($entry[1]);
                            break;
                        case 6: // ShapeType::Float_
                        case 7: // ShapeType::Double
                            $members[$idx] = $this->parseFloatList($entry[1]);
                            break;
                        case 17:  // ShapeType::Enum
                        case 18:  // ShapeType::IntEnum
                            $members[$idx] = $this->parseEnumList(
                                $entry[1],
                                $binding[4],
                                $binding[3] === 18
                            );
                            break;
                        default:
                            $members[$idx] = $this->parseStringList($entry[1]);
                            break;
                    }
                }
            }
        } else {
            foreach ($responseHeaders as $lowerName => $binding) {
                $entry = $headers[$lowerName] ?? null;
                if ($entry === null) {
                    continue;
                }
                $idx = $binding[1];
                if ($binding[0] === 0) {
                    switch ($binding[2]) {
                        case 10:  // ShapeType::String_
                            $members[$idx] = isset($binding[3])
                                ? \base64_decode($entry[1][0], true)
                                : $entry[1][0];
                            break;
                        case 17:  // ShapeType::Enum
                            $members[$idx] = $entry[1][0];
                            break;
                        case 4:  // ShapeType::Integer
                        case 5:  // ShapeType::Long
                        case 3: // ShapeType::Short
                        case 2: // ShapeType::Byte
                            $members[$idx] = (int) $entry[1][0];
                            break;
                        case 18:  // ShapeType::IntEnum
                            $members[$idx] = (int) $entry[1][0];
                            break;
                        case 1: // ShapeType::Boolean
                            $members[$idx] = $entry[1][0] === 'true';
                            break;
                        case 6: // ShapeType::Float_
                        case 7: // ShapeType::Double
                            $members[$idx] = $this->parseHeaderFloat(
                                $entry[1][0]
                            );
                            break;
                        case 12: // ShapeType::Timestamp
                            $members[$idx] = $scalarTimestampFormats[$idx]
                                ->parse($entry[1][0]);
                            break;
                        default:
                            $members[$idx] = $entry[1][0];
                            break;
                    }
                } else {
                    switch ($binding[3]) {
                        case 12: // ShapeType::Timestamp
                            $members[$idx] = $this->parseTimestampList(
                                $entry[1],
                                $idx
                            );
                            break;
                        case 4:  // ShapeType::Integer
                        case 5:  // ShapeType::Long
                        case 3: // ShapeType::Short
                        case 2: // ShapeType::Byte
                            $members[$idx] = $this->parseIntegerList($entry[1]);
                            break;
                        case 1: // ShapeType::Boolean
                            $members[$idx] = $this->parseBooleanList($entry[1]);
                            break;
                        case 6: // ShapeType::Float_
                        case 7: // ShapeType::Double
                            $members[$idx] = $this->parseFloatList($entry[1]);
                            break;
                        case 17:  // ShapeType::Enum
                        case 18:  // ShapeType::IntEnum
                            $members[$idx] = $this->parseEnumList(
                                $entry[1],
                                $binding[4],
                                $binding[3] === 18
                            );
                            break;
                        default:
                            $members[$idx] = $this->parseStringList($entry[1]);
                            break;
                    }
                }
            }
        }

        if ($bindingInfo->prefixHeaderDeserMembers !== []) {
            foreach (
                $bindingInfo->prefixHeaderDeserMembers as $idx => $prefix
            ) {
                $members[$idx] = $this->parsePrefixHeaders(
                    $prefix[0],
                    $prefix[1]
                );
            }
        }

        if ($bindingInfo->responseCodeMemberIndex !== null) {
            $members[$bindingInfo->responseCodeMemberIndex] = $this->statusCode;
        }

        if ($bindingInfo->payloadMemberIndex !== null) {
            $idx = $bindingInfo->payloadMemberIndex;
            $payloadSchema = $bindingInfo->payloadSchema;
            switch ($payloadSchema->type) {
                case 11: // ShapeType::Blob
                    $members[$idx] = $this->body;
                    break;
                case 10: // ShapeType::String_
                case 17: // ShapeType::Enum
                    $bodySize = $this->bodySize;
                    if ($bodySize < 0) {
                        $bodySize = (int) ($this->body?->getSize() ?? 0);
                        $this->bodySize = $bodySize;
                    }
                    $members[$idx] = $bodySize ? (string) $this->body : '';
                    break;
                case 19: // ShapeType::Document
                    $bodySize = $this->bodySize;
                    if ($bodySize < 0) {
                        $bodySize = (int) ($this->body?->getSize() ?? 0);
                        $this->bodySize = $bodySize;
                    }
                    if (!$bodySize) {
                        $members[$idx] = null;
                    } else {
                        $decoded = \json_decode((string) $this->body, true);
                        $members[$idx] = AbstractDocument::fromValue($decoded);
                    }
                    break;
                case 16: // ShapeType::Union
                    $payloadMembers = $this->deserializePayload($payloadSchema);
                    $members[$idx] = \is_array($payloadMembers)
                        ? ($payloadMembers[0] ?? null)
                        : $payloadMembers;
                    break;
                default:
                    $payloadMembers = $this->deserializePayload($payloadSchema);
                    $targetClass = $payloadSchema->targetClass;
                    $members[$idx] = $payloadMembers && $targetClass
                        ? $targetClass::fromMembers($payloadMembers)
                        : $payloadMembers;
                    break;

            }
        } else {
            $bodySize = $this->bodySize;
            if ($bodySize < 0) {
                $bodySize = (int) ($this->body?->getSize() ?? 0);
                $this->bodySize = $bodySize;
            }
            if (!$bodySize) {
                return;
            }

            $this->codec->createDeserializer((string) $this->body)
                ->deserialize($bindingInfo->bodySchema ?? $schema, $members);
        }
    }

    /**
     * Deserialize a structure/union payload into a positional members array.
     */
    private function deserializePayload(Schema $schema): mixed
    {
        $bodySize = $this->bodySize;
        if ($bodySize < 0) {
            $bodySize = (int) ($this->body?->getSize() ?? 0);
            $this->bodySize = $bodySize;
        }
        if (!$bodySize) {
            return null;
        }

        $memberCount = $schema->getMemberCount();
        $payloadMembers = $memberCount > 0
            ? \array_fill(0, $memberCount, null)
            : [];
        $this->codec->createDeserializer((string) $this->body)
            ->deserialize($schema, $payloadMembers);

        return $payloadMembers;
    }

    /**
     * Parse a list-valued header into an array of strings.
     *
     * @param list<string> $values Raw header values
     *
     * @return list<string>
     */
    private function parseStringList(array $values): array
    {
        if (\count($values) > 1) {
            return \array_map('trim', $values);
        }
        $raw = $values[0];
        // RFC 7230 quoted-string parsing is only needed for values containing
        // commas or quotes.
        if (!\str_contains($raw, ',') && !\str_contains($raw, '"')) {
            return [\trim($raw)];
        }

        return self::splitQuotedHeaderList($raw);
    }

    /**
     * Split a single header value using RFC 7230 quoted-string syntax.
     *
     * Items containing `,` or `"` arrive wrapped in double quotes with `\`
     * and `"` backslash-escaped. Only commas outside quoted regions split
     * list items.
     *
     * @return list<string>
     */
    private static function splitQuotedHeaderList(string $raw): array
    {
        $items = [];
        $len = \strlen($raw);
        $i = 0;
        while ($i < $len) {
            // Skip leading whitespace before each item.
            while ($i < $len && ($raw[$i] === ' ' || $raw[$i] === "\t")) {
                $i++;
            }
            if ($i >= $len) {
                break;
            }
            if ($raw[$i] === '"') {
                // Quoted items honor `\X` as an escape for the next char.
                $i++;
                $buf = '';
                while ($i < $len) {
                    $ch = $raw[$i];
                    if ($ch === '\\' && $i + 1 < $len) {
                        $buf .= $raw[$i + 1];
                        $i += 2;
                        continue;
                    }
                    if ($ch === '"') {
                        $i++;
                        break;
                    }
                    $buf .= $ch;
                    $i++;
                }
                $items[] = $buf;
                while ($i < $len && ($raw[$i] === ' ' || $raw[$i] === "\t")) {
                    $i++;
                }
                if ($i < $len && $raw[$i] === ',') {
                    $i++;
                }
                continue;
            }
            $start = $i;
            while ($i < $len && $raw[$i] !== ',') {
                $i++;
            }
            $items[] = \rtrim(\substr($raw, $start, $i - $start));
            if ($i < $len && $raw[$i] === ',') {
                $i++;
            }
        }
        return $items;
    }

    /**
     * Parse a list-valued timestamp header.
     *
     * HttpDate embeds a comma, so multi-item HttpDate lists use the fixed
     * IMF-fixdate width instead of splitting on comma.
     *
     * @param list<string> $values Raw header values
     * @param int $memberIndex Member index in the response shape
     *
     * @return list<float>
     */
    private function parseTimestampList(array $values, int $memberIndex): array
    {
        $kind = $this->bindingInfo->listTimestampKinds[$memberIndex] ?? 2;
        $joined = \implode(', ', $values);

        if ($kind === 2) {
            $items = [];
            $len = \strlen($joined);
            for ($o = 0; $o < $len; $o += 31) {
                $items[] = \substr($joined, $o, 29);
            }
        } else {
            $items = \explode(',', $joined);
        }

        $result = [];
        $format = match ($kind) {
            1 => TimestampFormat::DateTime,
            2 => TimestampFormat::HttpDate,
            default => TimestampFormat::EpochSeconds
        };
        foreach ($items as $item) {
            $result[] = $format->parse(\trim($item));
        }

        return $result;
    }

    /**
     * Parse a float/double header value with NaN/Infinity support.
     */
    private function parseHeaderFloat(string $value): float
    {
        return match ($value) {
            'NaN' => NAN,
            'Infinity' => INF,
            '-Infinity' => -INF,
            default => (float) $value,
        };
    }

    /**
     * Parse a list-valued integer header.
     *
     * @param list<string> $values Raw header values
     *
     * @return list<int>
     */
    private function parseIntegerList(array $values): array
    {
        $items = \count($values) > 1 ? $values : \explode(',', $values[0]);
        $result = [];
        foreach ($items as $v) {
            $result[] = (int) \trim($v);
        }

        return $result;
    }

    /**
     * Parse a list-valued boolean header.
     *
     * @param list<string> $values Raw header values
     *
     * @return list<bool>
     */
    private function parseBooleanList(array $values): array
    {
        $items = \count($values) > 1 ? $values : \explode(',', $values[0]);
        $result = [];
        foreach ($items as $v) {
            $result[] = \trim($v) === 'true';
        }

        return $result;
    }

    /**
     * Parse a list-valued float header with NaN/Infinity support.
     *
     * @param list<string> $values Raw header values
     *
     * @return list<float>
     */
    private function parseFloatList(array $values): array
    {
        $items = \count($values) > 1 ? $values : \explode(',', $values[0]);
        $result = [];
        foreach ($items as $v) {
            $result[] = $this->parseHeaderFloat(\trim($v));
        }

        return $result;
    }

    /**
     * Parse a list-valued enum or intEnum header into typed instances.
     *
     * @param list<string> $values Raw header values.
     * @param class-string $class FQCN of the target enum class.
     * @param bool $isIntEnum True for IntEnum (parse items as int first)
     *
     * @return list<object>
     */
    private function parseEnumList(array $values, string $class, bool $isIntEnum): array
    {
        $items = \count($values) > 1 ? $values : \explode(',', $values[0]);
        $result = [];
        if ($isIntEnum) {
            foreach ($items as $v) {
                $result[] = $class::fromValue((int) \trim($v));
            }
        } else {
            foreach ($items as $v) {
                $result[] = $class::fromValue(\trim($v));
            }
        }

        return $result;
    }

    /**
     * Collect prefix headers into a map.
     *
     * @param string $lowerPrefix Lowercase prefix to match
     * @param int $prefixLen Length of the prefix for substr offset
     *
     * @return array<string, string>
     */
    private function parsePrefixHeaders(string $lowerPrefix, int $prefixLen): array
    {
        $map = [];
        foreach ($this->headers as $lower => $entry) {
            if (\str_starts_with($lower, $lowerPrefix)) {
                $map[\substr($entry[0], $prefixLen)] = $entry[1][0];
            }
        }

        return $map;
    }
}
