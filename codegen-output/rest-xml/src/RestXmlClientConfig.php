<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml;

use Smithy\Client\AbstractClientConfig;

/**
 * Configuration for {@see RestXmlClient}.
 */
final class RestXmlClientConfig extends AbstractClientConfig
{
    /**
     * Shallow copy the config, overriding fields named in `$overrides`.
     *
     * Explicit `null` values keep the current value.
     *
     * @param array<string, mixed> $overrides
     */
    protected function copyWith(array $overrides): static
    {
        $protocol = \array_key_exists('protocol', $overrides)
            ? $overrides['protocol']
            : $this->protocol;
        $protocolId = \array_key_exists('protocolId', $overrides)
            ? $overrides['protocolId']
            : $this->protocolId;

        return new static(
            transport: $overrides['transport'] ?? $this->transport,
            endpointResolver: $overrides['endpointResolver']
                ?? $this->endpointResolver,
            protocol: $protocol,
            protocolResolver: $overrides['protocolResolver']
                ?? $this->protocolResolver,
            protocolId: $protocolId,
            interceptors: $overrides['interceptors']
                ?? $this->interceptorChain,
            supportedAuthSchemes: $overrides['supportedAuthSchemes']
                ?? $this->supportedAuthSchemes,
            authSchemeResolver: $overrides['authSchemeResolver']
                ?? $this->authSchemeResolver,
            retryStrategy: $overrides['retryStrategy']
                ?? $this->retryStrategy,
            retryScope: $overrides['retryScope'] ?? $this->retryScope,
            context: $overrides['context'] ?? $this->context,
            authSchemePreference: $overrides['authSchemePreference']
                ?? $this->authSchemePreference,
            service: $overrides['service'] ?? $this->service,
            pluginState: $overrides['pluginState']
                ?? $this->getPluginState(),
            explicitFields: $this->explicitFieldsFor($overrides),
            identityResolvers: $overrides['identityResolvers']
                ?? $this->identityResolvers
        );
    }
}
