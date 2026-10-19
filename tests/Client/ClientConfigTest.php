<?php

declare(strict_types=1);

namespace Smithy\Tests\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AuthSchemeResolver,
    AuthSchemeInterface,
    NoAuthScheme,
    StaticAuthSchemeResolver
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Client\Interceptor\IdempotencyTokenInterceptor;
use Smithy\Client\Retry\{
    NoRetryStrategy,
    RetryStrategyInterface
};
use Smithy\Http\Client\HttpClientTransport;
use Smithy\Tests\Client\Fake\{
    FakeOperation,
    FakeProtocol,
    RecordingInterceptor,
    RegionalClientConfigFixture,
    TestAuthSchemeFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Copy-on-write semantics for {@see ClientConfig::withXxx()}.
 *
 * Each method must produce a new config with only the named field
 * replaced, leaving the original untouched. Subclass fields (verified
 * separately) survive every base-class `withXxx()` because `copyWith()`
 * dispatches through `new static(...)`.
 */
#[CoversClass(ClientConfig::class)]
final class ClientConfigTest extends TestCase
{
    private function baseConfig(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new HttpClientTransport(
                new MockHttpClient()
            ),
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
        $this->assertSame($orig->transport, $new->transport);
        $this->assertSame($orig->endpointResolver, $new->endpointResolver);
    }

    public function testWithTransportReplacesOnlyTransport(): void
    {
        $orig = $this->baseConfig();
        $transport = new HttpClientTransport(new MockHttpClient());
        $new = $orig->withTransport($transport);

        $this->assertSame($transport, $new->transport);
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
        $this->assertSame($orig->transport, $new->transport);
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

    public function testDefaultsToAuthSchemeResolver(): void
    {
        $config = $this->baseConfig();
        $endpoint = new Endpoint('https://a.test');

        $this->assertInstanceOf(
            AuthSchemeResolver::class,
            $config->authSchemeResolver
        );
        $this->assertSame(
            NoAuthScheme::instance(),
            $config->authSchemeResolver->resolveAuthScheme(
                FakeOperation::instance(),
                $config
            )->scheme
        );
    }

    public function testIndexesSupportedAuthSchemesById(): void
    {
        $scheme = new TestAuthSchemeFixture('smithy.test#auth');
        $config = $this->baseConfig()->withAddedAuthScheme($scheme);

        $this->assertSame(
            $scheme,
            $config->getSupportedAuthScheme('smithy.test#auth')
        );
        $this->assertNull(
            $config->getSupportedAuthScheme('smithy.test#unsupported')
        );
    }

    public function testWithInterceptorAppends(): void
    {
        $orig = $this->baseConfig();
        $a = new RecordingInterceptor('A');
        $b = new RecordingInterceptor('B');

        $new = $orig->withInterceptor($a)->withInterceptor($b);

        $this->assertTrue($orig->interceptorChain->isEmpty());
        $this->assertFalse($new->interceptorChain->isEmpty());
        $this->assertTrue(
            $new->interceptorChain->contains(RecordingInterceptor::class)
        );
        $this->assertNotSame(
            $orig->interceptorChain,
            $new->interceptorChain
        );
    }

    public function testWithPrependedInterceptorPreservesOriginal(): void
    {
        $existing = new IdempotencyTokenInterceptor();
        $prepended = new RecordingInterceptor();
        $orig = $this->baseConfig()->withInterceptor($existing);

        $new = $orig->withPrependedInterceptor($prepended);

        $this->assertFalse(
            $orig->interceptorChain->contains(RecordingInterceptor::class)
        );
        $this->assertTrue(
            $new->interceptorChain->contains(RecordingInterceptor::class)
        );
        $this->assertTrue(
            $new->interceptorChain->contains(
                IdempotencyTokenInterceptor::class
            )
        );
    }

    public function testWithInterceptorsReplaces(): void
    {
        $orig = $this->baseConfig()->withInterceptor(
            new RecordingInterceptor('A')
        );
        $b = new IdempotencyTokenInterceptor();

        $new = $orig->withInterceptors($b);

        $this->assertFalse(
            $new->interceptorChain->contains(RecordingInterceptor::class)
        );
        $this->assertTrue(
            $new->interceptorChain->contains(
                IdempotencyTokenInterceptor::class
            )
        );
    }

    public function testWithAddedAuthSchemeAppendsPreservingNoAuth(): void
    {
        $orig = $this->baseConfig();
        $scheme = new TestAuthSchemeFixture('example#custom');

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
        $scheme = new TestAuthSchemeFixture('example#custom');

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

    public function testWithContextStoresValueUnderKey(): void
    {
        $orig = $this->baseConfig();

        $new = $orig->withContext('trace-id', 'abc-123');

        $this->assertSame('abc-123', $new->context['trace-id']);
        $this->assertArrayNotHasKey('trace-id', $orig->context);
    }

    public function testWithContextPreservesOtherKeys(): void
    {
        $orig = $this->baseConfig()->withContext('one', 1);

        $new = $orig->withContext('two', 2);

        $this->assertSame(['one' => 1, 'two' => 2], $new->context);
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
        $orig = new RegionalClientConfigFixture(
            protocol: new FakeProtocol(),
            transport: new HttpClientTransport(new MockHttpClient()),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://a.test')
            ),
            region: 'test-region'
        );

        $transport = new HttpClientTransport(new MockHttpClient());
        $new = $orig->withAuthSchemePreference(['sigv4'])
            ->withRetryScope('scope')
            ->withInterceptor(new RecordingInterceptor())
            ->withContext('foo', 'bar')
            ->withTransport($transport);

        // Every inherited withXxx() call preserves the subclass field.
        $this->assertSame('test-region', $new->region);
        $this->assertSame(['sigv4'], $new->authSchemePreference);
        $this->assertSame($transport, $new->transport);
    }
}
