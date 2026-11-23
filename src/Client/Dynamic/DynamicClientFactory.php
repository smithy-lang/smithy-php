<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Client\Dynamic;

use Smithy\Auth\AuthSchemeInterface;
use Smithy\Client\Auth\AuthSchemeResolverInterface;
use Smithy\Client\Endpoint\{
    Bytecode\BytecodeEndpointResolver,
    Bytecode\Compiler\BddCompiler,
    Bytecode\RulesFunctionInterface,
    EndpointContextKeys,
    EndpointResolverInterface
};
use Smithy\Client\Interceptor\InterceptorChain;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\Protocol\ProtocolResolver;
use Smithy\Client\Retry\RetryStrategyInterface;
use Smithy\Core\Context\Context;
use Smithy\Core\Transport\TransportInterface;
use Smithy\Core\Model\SmithyModel;
use Smithy\Http\Client\HttpClientInterface;
use Smithy\Protocol\ProtocolInterface;

/**
 * Resolve runtime dependencies and construct a dynamic client.
 *
 * @internal Called by {@see DynamicClient::create()}.
 */
final class DynamicClientFactory
{
    private const ENDPOINT_BDD = 'smithy.rules#endpointBdd';

    private function __construct() {}

    /**
     * Create a dynamic client from a model and runtime dependencies.
     *
     * @template T of DynamicClient
     *
     * @param SmithyModel|string $model Runtime model or JSON AST path.
     * @param array<string, ProtocolInterface> $protocols
     * @param list<string> $protocolPreference
     * @param list<AuthSchemeInterface> $supportedAuthSchemes
     * @param list<string>|null $authSchemePreference
     * @param array<string, RulesFunctionInterface> $endpointFunctions
     * @param array<string, string> $endpointAuthSchemeIds
     * @param array<string, mixed> $endpointParameters
     * @param class-string<T> $clientClass
     *
     * @return T
     *
     * @throws \InvalidArgumentException When client configuration is invalid.
     * @throws \RuntimeException When no modeled protocol implementation is available.
     */
    public static function create(
        SmithyModel|string $model,
        DynamicClientRuntime $runtime,
        ?string $serviceId = null,
        ?string $protocolId = null,
        ?ProtocolInterface $protocol = null,
        array $protocols = [],
        ?HttpClientInterface $httpClient = null,
        ?TransportInterface $transport = null,
        ?EndpointResolverInterface $endpointResolver = null,
        ?string $endpoint = null,
        array $endpointFunctions = [],
        array $endpointAuthSchemeIds = [],
        array $endpointParameters = [],
        array $protocolPreference = [],
        InterceptorChain $interceptors = new InterceptorChain(),
        array $supportedAuthSchemes = [],
        ?AuthSchemeResolverInterface $authSchemeResolver = null,
        ?array $authSchemePreference = null,
        ?RetryStrategyInterface $retryStrategy = null,
        string $retryScope = '',
        Context $context = new Context(),
        PluginCollection $plugins = new PluginCollection(),
        string $clientClass = DynamicClient::class
    ): DynamicClient
    {
        $model = \is_string($model)
            ? SmithyModel::fromFile($model)
            : $model;
        $serviceId = self::resolveServiceId($model, $serviceId);
        $serviceTraits = $model->getTraits($serviceId);
        $runtime = $runtime->merge(new DynamicClientRuntime(
            protocols: $protocols,
            authSchemes: $supportedAuthSchemes,
            endpointFunctions: $endpointFunctions,
            endpointAuthSchemeIds: $endpointAuthSchemeIds,
            protocolPreference: $protocolPreference
        ));
        // Dynamic operations have unique identities, so protocol caches must
        // not outlive the client.
        $protocolResolver = ProtocolResolver::createDefaults()->merge(
            $runtime->getProtocolResolver()
        );
        $supportedAuthSchemes = $runtime->getAuthSchemes();
        $endpointFunctions = $runtime->getEndpointFunctions();
        $endpointAuthSchemeIds = $runtime->getEndpointAuthSchemeIds();

        if ($protocol !== null
            && $protocolId !== null
            && $protocol->getId() !== $protocolId
        ) {
            throw new \InvalidArgumentException(
                "Protocol implementation {$protocol->getId()} does not match "
                . "configured protocol {$protocolId}."
            );
        }
        if ($endpointResolver === null) {
            $endpointBdd = $serviceTraits[self::ENDPOINT_BDD] ?? null;
            if ($endpointBdd !== null && !\is_array($endpointBdd)) {
                throw new \InvalidArgumentException(
                    'The modeled smithy.rules#endpointBdd trait must be '
                    . 'an object.'
                );
            }

            $usesModeledEndpoint = \is_array($endpointBdd)
                && (
                    $endpoint === null
                    || isset($endpointBdd['parameters']['Endpoint'])
                );
            if ($usesModeledEndpoint) {
                if ($endpoint !== null && $endpoint !== '') {
                    $endpointParameters['Endpoint'] = $endpoint;
                }
                $endpointResolver = new BytecodeEndpointResolver(
                    (new BddCompiler($endpointFunctions))->compile(
                        $endpointBdd
                    ),
                    endpointAuthSchemeIds: $endpointAuthSchemeIds
                );
                // The modeled rules consume the URL as their input.
                $endpoint = null;
            }
        }

        $context = self::withEndpointParameters(
            $context,
            $endpointParameters
        );
        $config = $clientClass::createClientConfig(
            model: $model,
            serviceId: $serviceId,
            protocolId: $protocolId,
            protocol: $protocol,
            protocolResolver: $protocolResolver,
            httpClient: $httpClient,
            transport: $transport,
            endpointResolver: $endpointResolver,
            interceptors: $interceptors,
            supportedAuthSchemes: $supportedAuthSchemes,
            authSchemeResolver: $authSchemeResolver,
            authSchemePreference: $authSchemePreference,
            retryStrategy: $retryStrategy,
            retryScope: $retryScope,
            context: $context,
            endpoint: $endpoint
        );

        return new $clientClass(
            $model,
            $config,
            $serviceId,
            $protocolId,
            $plugins,
            $runtime->getTraitRegistry(),
            $runtime->getErrorDiscriminatorTraits()
        );
    }

    /**
     * Store client endpoint parameters in the config context.
     *
     * @param array<string, mixed> $parameters
     *
     * @throws \InvalidArgumentException When a parameter name is not a
     *                                   string.
     */
    private static function withEndpointParameters(
        Context $context,
        array $parameters
    ): Context
    {
        foreach ($parameters as $name => $_) {
            if (!\is_string($name)) {
                throw new \InvalidArgumentException(
                    'Endpoint parameter names must be strings.'
                );
            }
        }

        if ($parameters === []) {
            return $context;
        }

        $key = EndpointContextKeys::parameters();

        return $context->with($key, [
            ...$context->get($key) ?? [],
            ...$parameters
        ]);
    }

    /**
     * Resolve an explicit or unique modeled service.
     */
    private static function resolveServiceId(
        SmithyModel $model,
        ?string $serviceId
    ): string
    {
        if ($serviceId !== null) {
            $service = $model->expectShape($serviceId);
            if (($service['type'] ?? null) !== 'service') {
                throw new \InvalidArgumentException(
                    "Shape {$serviceId} is not a service."
                );
            }

            return $serviceId;
        }

        $serviceIds = $model->getServiceIds();
        if ($serviceIds === []) {
            throw new \InvalidArgumentException(
                'Smithy model contains no service shapes.'
            );
        }
        if (\count($serviceIds) > 1) {
            throw new \InvalidArgumentException(
                'Smithy model contains multiple service shapes: '
                . \implode(', ', $serviceIds)
            );
        }

        return $serviceIds[0];
    }

}
