<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Benchmarks\Serde\Support;

/**
 * Compares serialized request bodies with fixture bodies independent of
 * member order and insignificant formatting.
 */
final class BenchmarkWireComparator
{
    /**
     * @param string $mediaType Fixture `bodyMediaType` value, or an empty
     *     string when the fixture has no body.
     */
    public static function equivalent(
        string $mediaType,
        string $expected,
        string $actual
    ): bool
    {
        // Raw payloads and absent bodies have no structure to normalize.
        if ($expected === '' || $mediaType === 'application/octet-stream') {
            return $expected === $actual;
        }

        return BenchmarkValueComparator::equivalent(
            self::normalize($mediaType, $expected),
            self::normalize($mediaType, $actual)
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function normalize(string $mediaType, string $body): array
    {
        return match ($mediaType) {
            'application/json' => self::normalizeJson($body),
            'application/xml', 'text/xml' => self::normalizeXml($body),
            'application/x-www-form-urlencoded' => self::normalizeForm(
                $body
            ),
            default => throw new \InvalidArgumentException(
                "Unsupported benchmark body media type: {$mediaType}"
            )
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function normalizeJson(string $body): array
    {
        $decoded = \json_decode(
            $body,
            true,
            512,
            JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR
        );

        return \is_array($decoded)
            ? self::sortKeys($decoded)
            : [$decoded];
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    private static function sortKeys(array $value): array
    {
        foreach ($value as $key => $child) {
            if (\is_array($child)) {
                $value[$key] = self::sortKeys($child);
            }
        }
        if (!\array_is_list($value)) {
            \ksort($value);
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeForm(string $body): array
    {
        $pairs = [];
        foreach (\explode('&', $body) as $pair) {
            [$name, $value] = \explode('=', $pair, 2) + [1 => ''];
            $pairs[\urldecode($name)] = \urldecode($value);
        }
        \ksort($pairs);

        return $pairs;
    }

    /**
     * Flatten an XML document into path => text leaves. Repeated sibling
     * names are indexed by position.
     *
     * @return array<string, string>
     */
    private static function normalizeXml(string $body): array
    {
        $document = new \DOMDocument();
        if (!$document->loadXML($body, LIBXML_NONET)
            || $document->documentElement === null
        ) {
            throw new \InvalidArgumentException('Invalid XML body.');
        }

        $leaves = [];
        self::flattenXml(
            $document->documentElement,
            $document->documentElement->nodeName,
            $leaves
        );
        \ksort($leaves);

        return $leaves;
    }

    /**
     * @param array<string, string> $leaves
     */
    private static function flattenXml(
        \DOMElement $element,
        string $path,
        array &$leaves
    ): void
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child;
            }
        }
        if ($children === []) {
            $leaves[$path] = $element->textContent;

            return;
        }

        $counts = [];
        foreach ($children as $child) {
            $counts[$child->nodeName] = ($counts[$child->nodeName] ?? 0) + 1;
        }
        $seen = [];
        foreach ($children as $child) {
            $name = $child->nodeName;
            $index = $seen[$name] = ($seen[$name] ?? -1) + 1;
            self::flattenXml(
                $child,
                $path . '/' . $name . ($counts[$name] > 1 ? "[{$index}]" : ''),
                $leaves
            );
        }
    }
}
