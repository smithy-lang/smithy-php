<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Endpoint;

use Smithy\Core\Endpoint\Endpoint;

/**
 * Turn the `endpointResolver` and `endpoint` client options into one
 * endpoint resolver.
 *
 * @internal Shared by client configuration and the dynamic client factory.
 */
final class EndpointOption
{
    private function __construct() {}

    /**
     * Return the configured resolver, or a {@see StaticEndpointResolver}
     * for a non-empty endpoint URL.
     *
     * Returns `null` when neither option is set.
     *
     * @throws \InvalidArgumentException When both options are set.
     */
    public static function resolve(
        ?EndpointResolverInterface $endpointResolver,
        ?string $endpoint
    ): ?EndpointResolverInterface
    {
        if ($endpointResolver !== null && $endpoint !== null) {
            throw new \InvalidArgumentException(
                'Provide either an endpoint resolver or a static endpoint, '
                . 'not both.'
            );
        }
        if ($endpoint === null || $endpoint === '') {
            return $endpointResolver;
        }

        return new StaticEndpointResolver(new Endpoint($endpoint));
    }
}
