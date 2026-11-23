<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Endpoint;

use Smithy\Client\Endpoint\Bytecode\EndpointResolutionTraceSinkInterface;
use Smithy\Core\Context\ContextKey;

/**
 * Client config context keys read by endpoint resolvers.
 */
final class EndpointContextKeys
{
    /** @var ContextKey<array<string, mixed>>|null */
    private static ?ContextKey $parameters = null;

    /** @var ContextKey<EndpointResolutionTraceSinkInterface>|null */
    private static ?ContextKey $traceSink = null;

    private function __construct() {}

    /**
     * Key for client-wide endpoint parameters, keyed by parameter name.
     *
     * @return ContextKey<array<string, mixed>>
     */
    public static function parameters(): ContextKey
    {
        return self::$parameters ??= new ContextKey(
            'smithy.endpointParameters'
        );
    }

    /**
     * Key for the sink that receives endpoint resolution traces.
     *
     * @return ContextKey<EndpointResolutionTraceSinkInterface>
     */
    public static function traceSink(): ContextKey
    {
        return self::$traceSink ??= new ContextKey(
            'smithy.endpointResolutionTraceSink'
        );
    }
}
