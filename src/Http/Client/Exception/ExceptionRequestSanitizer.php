<?php

declare(strict_types=1);

namespace Smithy\Http\Client\Exception;

use Psr\Http\Message\RequestInterface;

/**
 * Remove authentication headers from requests retained by exceptions.
 *
 * @internal
 */
final class ExceptionRequestSanitizer
{
    private const SENSITIVE_HEADERS = [
        'Authorization',
        'Proxy-Authorization',
    ];

    /**
     * Return a request that is safe to expose through an exception.
     */
    public static function sanitize(
        RequestInterface $request
    ): RequestInterface
    {
        foreach (self::SENSITIVE_HEADERS as $header) {
            if ($request->hasHeader($header)) {
                $request = $request->withoutHeader($header);
            }
        }

        return $request;
    }
}
