<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Client\Auth\{
    AuthSchemeInterface,
    AuthSchemeResolverInterface
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\EndpointResolverInterface;
use Smithy\Client\Interceptor\InterceptorChain;
use Smithy\Client\Plugin\PluginState;
use Smithy\Client\Retry\RetryStrategyInterface;
use Smithy\Client\Transport\TransportInterface;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Http\Client\HttpClientInterface;
use Smithy\Protocol\ProtocolInterface;

/**
 * Client config fixture with one subclass-owned field.
 */
final class RegionalClientConfigFixture extends ClientConfig
{
    /**
     * @param list<AuthSchemeInterface> $supportedAuthSchemes
     * @param list<string> $authSchemePreference
     * @param array<string, mixed> $context
     */
    public function __construct(
        ProtocolInterface $protocol,
        ?HttpClientInterface $httpClient,
        EndpointResolverInterface $endpointResolver,
        InterceptorChain $interceptors = new InterceptorChain(),
        array $supportedAuthSchemes = [],
        ?AuthSchemeResolverInterface $authSchemeResolver = null,
        ?RetryStrategyInterface $retryStrategy = null,
        string $retryScope = '',
        array $context = [],
        array $authSchemePreference = [],
        public readonly string $region = '',
        ?ServiceInterface $service = null,
        ?PluginState $pluginState = null,
        ?TransportInterface $transport = null
    )
    {
        parent::__construct(
            $protocol,
            $httpClient,
            $endpointResolver,
            $interceptors,
            $supportedAuthSchemes,
            $authSchemeResolver,
            $retryStrategy,
            $retryScope,
            $context,
            $authSchemePreference,
            $service,
            $pluginState,
            $transport
        );
    }

    /** @inheritDoc */
    protected function copyWith(array $overrides): static
    {
        if (\array_key_exists('transport', $overrides)) {
            $transport = $overrides['transport'];
            $httpClient = null;
        } elseif (\array_key_exists('httpClient', $overrides)) {
            $transport = null;
            $httpClient = $overrides['httpClient'];
        } else {
            $transport = $this->transport;
            $httpClient = null;
        }

        return new static(
            protocol: $overrides['protocol'] ?? $this->protocol,
            httpClient: $httpClient,
            endpointResolver: $overrides['endpointResolver']
                ?? $this->endpointResolver,
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
            region: $overrides['region'] ?? $this->region,
            service: $overrides['service'] ?? $this->service,
            pluginState: $overrides['pluginState']
                ?? $this->getPluginState(),
            transport: $transport
        );
    }
}
