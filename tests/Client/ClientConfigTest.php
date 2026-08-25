<?php

declare(strict_types=1);

namespace Smithy\Tests\Client;

use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AnonymousIdentityResolver,
    AuthSchemeInterface,
    AuthSchemeResolverInterface,
    IdentityResolverInterface,
    ModeledAuthSchemeResolver,
    NoAuthScheme,
    NullSigner,
    SignerInterface,
    StaticAuthSchemeResolver
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Retry\{
    NoRetryStrategy,
    RetryStrategyInterface
};
use Smithy\Core\Shape\OperationInterface;
use Smithy\Tests\Utils\Http\MockHttpClient;
use Smithy\Tests\Client\Fake\{
    FakeOperation,
    FakeProtocol,
    RecordingInterceptor
};
/**
 * Copy-on-write semantics for {@see ClientConfig::withXxx()}.
 *
 * Each method must produce a new config with only the named field
 * replaced, leaving the original untouched. Subclass fields (verified
 * separately) survive every base-class `withXxx()` because `copyWith()`
 * dispatches through `new static(...)`.
 */
final class ClientConfigTest extends TestCase
{
    private function baseConfig(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://a.test')
            )
        );
    }

    public function testWithProtocolReplacesOnlyProtocol(): void
    {
        $orig = $this->baseConfig();
        $newProto = new FakeProtocol();
        $new = $orig->withProtocol($newProto);

        $this->assertNotSame($orig, $new);
        $this->assertSame($newProto, $new->protocol);
        $this->assertSame($orig->httpClient, $new->httpClient);
        $this->assertSame($orig->endpointResolver, $new->endpointResolver);
    }

    public function testWithHttpClientReplacesOnlyHttpClient(): void
    {
        $orig = $this->baseConfig();
        $newHttp = new MockHttpClient();
        $new = $orig->withHttpClient($newHttp);

        $this->assertSame($newHttp, $new->httpClient);
        $this->assertSame($orig->protocol, $new->protocol);
    }

    public function testWithEndpointResolverReplacesOnlyEndpointResolver(): void
    {
        $orig = $this->baseConfig();
        $newResolver = new StaticEndpointResolver(
            new Endpoint('https://b.test')
        );
        $new = $orig->withEndpointResolver($newResolver);

        $this->assertSame($newResolver, $new->endpointResolver);
        $this->assertSame($orig->httpClient, $new->httpClient);
    }

    public function testWithRetryStrategyReplacesOnlyRetryStrategy(): void
    {
        $orig = $this->baseConfig();
        $newRetry = $this->createStub(RetryStrategyInterface::class);
        $new = $orig->withRetryStrategy($newRetry);

        $this->assertSame($newRetry, $new->retryStrategy);
        $this->assertInstanceOf(NoRetryStrategy::class, $orig->retryStrategy);
    }

    public function testWithRetryScopeReplacesOnlyRetryScope(): void
    {
        $orig = $this->baseConfig();
        $new = $orig->withRetryScope('client-scope');

        $this->assertSame('client-scope', $new->retryScope);
        $this->assertSame('', $orig->retryScope);
    }

    public function testWithAuthSchemeResolverReplacesOnlyResolver(): void
    {
        $orig = $this->baseConfig();
        $newResolver = new StaticAuthSchemeResolver(['smithy.api#noAuth']);
        $new = $orig->withAuthSchemeResolver($newResolver);

        $this->assertSame($newResolver, $new->authSchemeResolver);
        $this->assertNotSame(
            $orig->authSchemeResolver,
            $new->authSchemeResolver
        );
    }

    public function testWithAuthSchemePreferenceReplacesPreference(): void
    {
        $orig = $this->baseConfig();
        $new = $orig->withAuthSchemePreference([
            'httpBearerAuth',
            'sigv4'
        ]);

        $this->assertSame([], $orig->authSchemePreference);
        $this->assertSame(
            ['httpBearerAuth', 'sigv4'],
            $new->authSchemePreference
        );
    }

    public function testAuthSchemePreferenceRemovesDuplicates(): void
    {
        $config = $this->baseConfig()->withAuthSchemePreference([
            'sigv4',
            'sigv4',
            'httpBearerAuth'
        ]);

        $this->assertSame(
            ['sigv4', 'httpBearerAuth'],
            $config->authSchemePreference
        );
    }

    public function testAuthSchemePreferenceRejectsInvalidNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty strings');

        $this->baseConfig()->withAuthSchemePreference(['sigv4', '']);
    }

    public function testDefaultsToModeledAuthSchemeResolver(): void
    {
        $config = $this->baseConfig();

        $this->assertSame(
            ModeledAuthSchemeResolver::instance(),
            $config->authSchemeResolver
        );
        $this->assertSame(
            [NoAuthScheme::ID],
            $config->authSchemeResolver->resolveAuthScheme(
                FakeOperation::instance(),
                $config
            )
        );
    }

    public function testWithInterceptorAppends(): void
    {
        $orig = $this->baseConfig();
        $a = new RecordingInterceptor('A');
        $b = new RecordingInterceptor('B');

        $new = $orig->withInterceptor($a)->withInterceptor($b);

        $this->assertCount(0, $orig->interceptors);
        $this->assertCount(2, $new->interceptors);
        $this->assertSame($a, $new->interceptors[0]);
        $this->assertSame($b, $new->interceptors[1]);
    }

    public function testWithInterceptorsReplaces(): void
    {
        $orig = $this->baseConfig()->withInterceptor(
            new RecordingInterceptor('A')
        );
        $b = new RecordingInterceptor('B');

        $new = $orig->withInterceptors([$b]);

        $this->assertCount(1, $new->interceptors);
        $this->assertSame($b, $new->interceptors[0]);
    }

    public function testWithAddedAuthSchemeAppendsPreservingNoAuth(): void
    {
        $orig = $this->baseConfig();
        $scheme = $this->stubAuthScheme('example#custom');

        $new = $orig->withAddedAuthScheme($scheme);

        // Original has only NoAuthScheme (auto-added).
        $this->assertCount(1, $orig->supportedAuthSchemes);
        $this->assertSame(
            NoAuthScheme::ID,
            $orig->supportedAuthSchemes[0]->getSchemeId()
        );

        // New has NoAuth + the appended custom scheme.
        $ids = \array_map(
            fn(AuthSchemeInterface $s) => $s->getSchemeId(),
            $new->supportedAuthSchemes
        );
        $this->assertContains(NoAuthScheme::ID, $ids);
        $this->assertContains('example#custom', $ids);
    }

    public function testWithSupportedAuthSchemesPreservesNoAuth(): void
    {
        $orig = $this->baseConfig();
        $scheme = $this->stubAuthScheme('example#custom');

        $new = $orig->withSupportedAuthSchemes([$scheme]);

        // Constructor re-adds NoAuthScheme even when replacing wholesale.
        $ids = \array_map(
            fn(AuthSchemeInterface $s) => $s->getSchemeId(),
            $new->supportedAuthSchemes
        );
        $this->assertContains(NoAuthScheme::ID, $ids);
        $this->assertContains('example#custom', $ids);
        $this->assertCount(2, $new->supportedAuthSchemes);
    }

    public function testWithAdditionalStoresValueUnderKey(): void
    {
        $orig = $this->baseConfig();

        $new = $orig->withAdditional('trace-id', 'abc-123');

        $this->assertSame('abc-123', $new->additional['trace-id']);
        $this->assertArrayNotHasKey('trace-id', $orig->additional);
    }

    public function testWithAdditionalPreservesOtherKeys(): void
    {
        $orig = $this->baseConfig()->withAdditional('one', 1);

        $new = $orig->withAdditional('two', 2);

        $this->assertSame(['one' => 1, 'two' => 2], $new->additional);
    }

    /**
     * Verify subclass fields survive base-class `withXxx()` calls.
     *
     * This is the reason `copyWith()` exists: the base class uses
     * `new static(...)` and the subclass overrides `copyWith()` once so
     * every inherited `withXxx()` preserves the subclass's own fields.
     */
    public function testSubclassFieldsSurviveBaseClassWithXxx(): void
    {
        $orig = new class(
            protocol: new FakeProtocol(),
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://a.test')
            ),
            region: 'test-region'
        ) extends ClientConfig {
            public function __construct(
                \Smithy\Protocol\ProtocolInterface $protocol,
                \Smithy\Http\Client\HttpClientInterface $httpClient,
                \Smithy\Client\Endpoint\EndpointResolverInterface
                    $endpointResolver,
                array $interceptors = [],
                array $supportedAuthSchemes = [],
                ?AuthSchemeResolverInterface $authSchemeResolver = null,
                ?RetryStrategyInterface $retryStrategy = null,
                string $retryScope = '',
                array $additional = [],
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
                    $additional
                );
            }

            protected function copyWith(array $overrides): static
            {
                $protocol = \array_key_exists('protocol', $overrides)
                    ? $overrides['protocol']
                    : $this->protocol;
                $httpClient = \array_key_exists('httpClient', $overrides)
                    ? $overrides['httpClient']
                    : $this->httpClient;
                $endpointResolver = \array_key_exists(
                    'endpointResolver',
                    $overrides
                )
                    ? $overrides['endpointResolver']
                    : $this->endpointResolver;
                $interceptors = \array_key_exists('interceptors', $overrides)
                    ? $overrides['interceptors']
                    : $this->interceptors;
                $supportedAuthSchemes = \array_key_exists(
                    'supportedAuthSchemes',
                    $overrides
                )
                    ? $overrides['supportedAuthSchemes']
                    : $this->supportedAuthSchemes;
                $authSchemeResolver = \array_key_exists(
                    'authSchemeResolver',
                    $overrides
                )
                    ? $overrides['authSchemeResolver']
                    : $this->authSchemeResolver;
                $retryStrategy = \array_key_exists(
                    'retryStrategy',
                    $overrides
                )
                    ? $overrides['retryStrategy']
                    : $this->retryStrategy;
                $retryScope = \array_key_exists('retryScope', $overrides)
                    ? $overrides['retryScope']
                    : $this->retryScope;
                $additional = \array_key_exists('additional', $overrides)
                    ? $overrides['additional']
                    : $this->additional;
                $region = \array_key_exists('region', $overrides)
                    ? $overrides['region']
                    : $this->region;

                return new static(
                    protocol: $protocol,
                    httpClient: $httpClient,
                    endpointResolver: $endpointResolver,
                    interceptors: $interceptors,
                    supportedAuthSchemes: $supportedAuthSchemes,
                    authSchemeResolver: $authSchemeResolver,
                    retryStrategy: $retryStrategy,
                    retryScope: $retryScope,
                    additional: $additional,
                    region: $region
                );
            }
        };

        $new = $orig->withRetryScope('scope')
            ->withInterceptor(new RecordingInterceptor())
            ->withAdditional('foo', 'bar');

        // Every inherited withXxx() call preserves the subclass field.
        $this->assertSame('test-region', $new->region);
    }

    private function stubAuthScheme(string $id): AuthSchemeInterface
    {
        return new class($id) implements AuthSchemeInterface {
            public function __construct(private readonly string $id) {}

            public function getSchemeId(): string
            {
                return $this->id;
            }

            public function getIdentityResolver(
                ClientConfig $config
            ): IdentityResolverInterface
            {
                return AnonymousIdentityResolver::instance();
            }

            public function getSigner(): SignerInterface
            {
                return NullSigner::instance();
            }
        };
    }
}
