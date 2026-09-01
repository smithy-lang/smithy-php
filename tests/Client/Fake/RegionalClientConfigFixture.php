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
use Smithy\Client\Retry\RetryStrategyInterface;
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
        HttpClientInterface $httpClient,
        EndpointResolverInterface $endpointResolver,
        InterceptorChain $interceptors = new InterceptorChain(),
        array $supportedAuthSchemes = [],
        ?AuthSchemeResolverInterface $authSchemeResolver = null,
        ?RetryStrategyInterface $retryStrategy = null,
        string $retryScope = '',
        array $context = [],
        array $authSchemePreference = [],
        public readonly string $region = ''
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
            $authSchemePreference
        );
    }

    /** @inheritDoc */
    protected function copyWith(array $overrides): static
    {
        return new static(
            protocol: \array_key_exists('protocol', $overrides)
                ? $overrides['protocol']
                : $this->protocol,
            httpClient: \array_key_exists('httpClient', $overrides)
                ? $overrides['httpClient']
                : $this->httpClient,
            endpointResolver: \array_key_exists('endpointResolver', $overrides)
                ? $overrides['endpointResolver']
                : $this->endpointResolver,
            interceptors: \array_key_exists('interceptors', $overrides)
                ? $overrides['interceptors']
                : $this->interceptorChain,
            supportedAuthSchemes: \array_key_exists(
                'supportedAuthSchemes',
                $overrides
            )
                ? $overrides['supportedAuthSchemes']
                : $this->supportedAuthSchemes,
            authSchemeResolver: \array_key_exists(
                'authSchemeResolver',
                $overrides
            )
                ? $overrides['authSchemeResolver']
                : $this->authSchemeResolver,
            retryStrategy: \array_key_exists('retryStrategy', $overrides)
                ? $overrides['retryStrategy']
                : $this->retryStrategy,
            retryScope: \array_key_exists('retryScope', $overrides)
                ? $overrides['retryScope']
                : $this->retryScope,
            context: \array_key_exists('context', $overrides)
                ? $overrides['context']
                : $this->context,
            authSchemePreference: \array_key_exists(
                'authSchemePreference',
                $overrides
            )
                ? $overrides['authSchemePreference']
                : $this->authSchemePreference,
            region: \array_key_exists('region', $overrides)
                ? $overrides['region']
                : $this->region
        );
    }
}
