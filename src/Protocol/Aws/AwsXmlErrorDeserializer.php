<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Protocol\Aws;

use Psr\Http\Message\ResponseInterface;

/**
 * Deserialize AWS XML error envelopes.
 */
class AwsXmlErrorDeserializer extends AbstractAwsErrorDeserializer
{
    /**
     * @return array{
     *     discriminator: ?string,
     *     message: string,
     *     body: string,
     *     requestId: ?string
     * }
     */
    protected function parseError(ResponseInterface $response): array
    {
        $requestId = $response->getHeaderLine('x-amz-request-id');
        if ($requestId === '') {
            $requestId = $response->getHeaderLine('x-amzn-requestid');
        }

        $body = $response->getHeaderLine('Content-Length') === '0'
            ? ''
            : $response->getBody()->getContents();
        if ($body === '') {
            return [
                'discriminator' => 'Unknown',
                'message' => '',
                'body' => '',
                'requestId' => $requestId !== '' ? $requestId : null
            ];
        }

        $useInternalErrors = \libxml_use_internal_errors(true);
        try {
            $xml = new \SimpleXMLElement(
                $body,
                LIBXML_NOBLANKS | LIBXML_NONET | LIBXML_COMPACT
            );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to parse XML error response: ' . $e->getMessage(),
                0,
                $e
            );
        } finally {
            \libxml_clear_errors();
            \libxml_use_internal_errors($useInternalErrors);
        }

        $errors = $xml->xpath(
            './*[local-name()="Error"]'
            . ' | ./*[local-name()="Errors"]/*[local-name()="Error"]'
        );
        $error = $errors[0] ?? $xml;
        $bodyRequestId = self::childText($xml, 'RequestId')
            ?? self::childText($xml, 'RequestID')
            ?? self::childText($error, 'RequestId')
            ?? self::childText($error, 'RequestID');
        if ($bodyRequestId !== null && $bodyRequestId !== '') {
            $requestId = $bodyRequestId;
        }

        return [
            'discriminator' => self::childText($error, 'Code') ?? 'Unknown',
            'message' => self::childText($error, 'Message') ?? '',
            'body' => $error === $xml ? $body : self::errorBody($error, $body),
            'requestId' => $requestId !== '' ? $requestId : null
        ];
    }

    private static function childText(
        \SimpleXMLElement $element,
        string $name
    ): ?string
    {
        $children = $element->xpath('./*[local-name()="' . $name . '"]');

        return isset($children[0]) ? (string) $children[0] : null;
    }

    /**
     * Preserve inherited namespace declarations on the selected error root.
     */
    private static function errorBody(
        \SimpleXMLElement $error,
        string $originalBody
    ): string
    {
        $body = $error->asXML();
        if ($body === false) {
            return $originalBody;
        }

        $declared = $error->getDocNamespaces(false, false);
        $declarations = '';
        foreach ($error->getNamespaces(true) as $prefix => $namespace) {
            if (\array_key_exists($prefix, $declared)) {
                continue;
            }
            $name = $prefix === '' ? 'xmlns' : 'xmlns:' . $prefix;
            $value = \htmlspecialchars(
                $namespace,
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );
            $declarations .= ' ' . $name . '="' . $value . '"';
        }

        if ($declarations === '') {
            return $body;
        }

        $nameEnd = 1 + \strcspn($body, " \t\r\n/>", 1);

        return \substr($body, 0, $nameEnd)
            . $declarations
            . \substr($body, $nameEnd);
    }
}
