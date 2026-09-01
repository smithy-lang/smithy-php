<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\{
    CoversClass,
    DataProvider
};
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    AuthSchemeResolver,
    NoAuthScheme,
    ResolvedAuthScheme
};
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    EndpointAuthScheme,
    StaticEndpointResolver
};
use Smithy\Core\Shape\OperationInterface;
use Smithy\Tests\Client\Fake\{
    FakeProtocol,
    TestAuthSchemeFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(AuthSchemeResolver::class)]
#[CoversClass(ResolvedAuthScheme::class)]
final class AuthSchemeResolverTest extends TestCase
{
    private const BEARER = 'smithy.api#httpBearerAuth';
    private const NO_AUTH = NoAuthScheme::ID;
    private const SIGV4 = 'aws.auth#sigv4';

    /**
     * @param list<string> $runtimeSupport
     * @param list<string> $modeledSchemes
     * @param list<string> $endpointSchemes
     * @param list<string> $preference
     */
    #[DataProvider('resolutionCases')]
    public function testResolvesAuthScheme(
        array $runtimeSupport,
        array $modeledSchemes,
        array $endpointSchemes,
        array $preference,
        ?string $expected
    ): void
    {
        $endpointAuth = [];
        foreach ($endpointSchemes as $schemeId) {
            $endpointAuth[] = new EndpointAuthScheme($schemeId);
        }
        $endpoint = new Endpoint(
            'https://example.test',
            authSchemes: $endpointAuth
        );
        $config = $this->config($runtimeSupport, $endpoint);
        $operation = $this->operation($modeledSchemes);

        if ($expected === null) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('auth scheme');
        }

        $resolved = $config->authSchemeResolver->resolveAuthScheme(
            $operation,
            $endpoint,
            $config,
            $preference
        );

        $this->assertSame($expected, $resolved->scheme->getSchemeId());
    }

    /**
     * @return array<string, array{
     *     list<string>,
     *     list<string>,
     *     list<string>,
     *     list<string>,
     *     string|null
     * }>
     */
    public static function resolutionCases(): array
    {
        return [
            'modeled order selects sigv4' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4, self::BEARER],
                [],
                [],
                self::SIGV4
            ],
            'modeled order selects bearer' => [
                [self::SIGV4, self::BEARER],
                [self::BEARER, self::SIGV4],
                [],
                [],
                self::BEARER
            ],
            'unsupported leading scheme is skipped' => [
                [self::SIGV4],
                [self::BEARER, self::SIGV4],
                [],
                [],
                self::SIGV4
            ],
            'no auth remains available' => [
                [self::SIGV4],
                [self::NO_AUTH],
                [],
                [],
                self::NO_AUTH
            ],
            'no supported modeled scheme fails' => [
                [self::SIGV4],
                [self::BEARER],
                [],
                [],
                null
            ],
            'endpoint auth overrides modeled order' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4, self::BEARER],
                [self::BEARER],
                [],
                self::BEARER
            ],
            'endpoint auth may select an unmodeled scheme' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4],
                [self::BEARER],
                [],
                self::BEARER
            ],
            'endpoint auth overrides no auth' => [
                [self::SIGV4, self::BEARER],
                [self::NO_AUTH],
                [self::BEARER],
                [],
                self::BEARER
            ],
            'preference reorders modeled auth' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4, self::BEARER],
                [],
                ['httpBearerAuth'],
                self::BEARER
            ],
            'preference may override endpoint auth' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4, self::BEARER],
                [self::BEARER],
                ['sigv4'],
                self::SIGV4
            ],
            'preference cannot add modeled auth' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4],
                [],
                ['httpBearerAuth'],
                self::SIGV4
            ],
            'unknown preference preserves modeled order' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4, self::BEARER],
                [],
                ['sigv3'],
                self::SIGV4
            ]
        ];
    }

    public function testPreservesEndpointProperties(): void
    {
        $endpoint = new Endpoint(
            'https://example.test',
            authSchemes: [
                new EndpointAuthScheme(
                    self::SIGV4,
                    ['signingName' => 'service']
                )
            ]
        );
        $config = $this->config([self::SIGV4], $endpoint);

        $resolved = $config->authSchemeResolver->resolveAuthScheme(
            $this->operation([self::SIGV4]),
            $endpoint,
            $config
        );

        $this->assertSame(
            ['signingName' => 'service'],
            $resolved->properties
        );
    }

    public function testReportsUnsupportedEndpointSchemes(): void
    {
        $endpoint = new Endpoint(
            'https://example.test',
            authSchemes: [
                new EndpointAuthScheme('smithy.test#unsupported')
            ]
        );
        $config = $this->config([], $endpoint);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Endpoint resolution requested unsupported auth schemes '
            . '[smithy.test#unsupported] for operation TestOperation.'
        );

        $config->authSchemeResolver->resolveAuthScheme(
            $this->operation([self::NO_AUTH]),
            $endpoint,
            $config
        );
    }

    /**
     * @param list<string> $supportedSchemeIds
     */
    private function config(
        array $supportedSchemeIds,
        Endpoint $endpoint
    ): ClientConfig
    {
        $schemes = [];
        foreach ($supportedSchemeIds as $schemeId) {
            $schemes[] = new TestAuthSchemeFixture($schemeId);
        }

        return new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver($endpoint),
            supportedAuthSchemes: $schemes
        );
    }

    /**
     * @param list<string> $effectiveAuthSchemes
     */
    private function operation(array $effectiveAuthSchemes): OperationInterface
    {
        $operation = $this->createStub(OperationInterface::class);
        $operation->method('getName')->willReturn('TestOperation');
        $operation
            ->method('getEffectiveAuthSchemes')
            ->willReturn($effectiveAuthSchemes);

        return $operation;
    }
}
