<?php

declare(strict_types=1);

namespace Smithy\Http\Connection;

/**
 * TLS context captured from a connected connection.
 *
 * Records what was actually negotiated during the TLS handshake,
 * as opposed to TLSConfiguration which expresses preferences.
 */
final class TlsContext
{
    /**
     * Create TLS context.
     */
    public function __construct(
        /** The TLS protocol version negotiated (e.g. "TLSv1.3"). */
        public readonly string $protocol,

        /** The cipher suite negotiated (e.g. "TLS_AES_256_GCM_SHA384"). */
        public readonly string $cipherSuite,

        /** The ALPN protocol negotiated (e.g. "h2", "http/1.1"), or null. */
        public readonly ?string $alpnProtocol = null
    ) {}

    /**
     * Capture TLS context from a cURL handle after connection.
     *
     * @return self|null Null if the connection is not TLS.
     */
    public static function fromCurlHandle(\CurlHandle $handle): ?self
    {
        $scheme = \curl_getinfo($handle, CURLINFO_SCHEME);

        if ($scheme !== 'HTTPS') {
            return null;
        }

        return new self(
            protocol: \curl_getinfo($handle, CURLINFO_SSL_VERIFYRESULT) === 0
                ? (string) (
                    \curl_getinfo($handle, CURLINFO_TLS_SESSION) ?: 'unknown'
                )
                : 'unknown',
            cipherSuite: 'unknown',
            alpnProtocol: null
        );
    }
}
