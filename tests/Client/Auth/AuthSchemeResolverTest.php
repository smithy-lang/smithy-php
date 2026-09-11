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
     * @param list<string> $preference
     */
    #[DataProvider('resolutionCases')]
    public function testResolvesAuthScheme(
        array $runtimeSupport,
        array $modeledSchemes,
        array $preference,
        ?string $expected
    ): void
    {
        $config = $this->config($runtimeSupport);
        $operation = $this->operation($modeledSchemes);

        if ($expected === null) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('auth scheme');
        }

        $resolved = $config->authSchemeResolver->resolveAuthScheme(
            $operation,
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
                self::SIGV4
            ],
            'modeled order selects bearer' => [
                [self::SIGV4, self::BEARER],
                [self::BEARER, self::SIGV4],
                [],
                self::BEARER
            ],
            'unsupported leading scheme is skipped' => [
                [self::SIGV4],
                [self::BEARER, self::SIGV4],
                [],
                self::SIGV4
            ],
            'no auth remains available' => [
                [self::SIGV4],
                [self::NO_AUTH],
                [],
                self::NO_AUTH
            ],
            'no supported modeled scheme fails' => [
                [self::SIGV4],
                [self::BEARER],
                [],
                null
            ],
            'preference reorders modeled auth' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4, self::BEARER],
                ['httpBearerAuth'],
                self::BEARER
            ],
            'preference cannot add modeled auth' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4],
                ['httpBearerAuth'],
                self::SIGV4
            ],
            'unknown preference preserves modeled order' => [
                [self::SIGV4, self::BEARER],
                [self::SIGV4, self::BEARER],
                ['sigv3'],
                self::SIGV4
            ]
        ];
    }

    /**
     * @param list<string> $supportedSchemeIds
     */
    private function config(array $supportedSchemeIds): ClientConfig
    {
        $schemes = [];
        foreach ($supportedSchemeIds as $schemeId) {
            $schemes[] = new TestAuthSchemeFixture($schemeId);
        }

        return new ClientConfig(
            protocol: new FakeProtocol(),
            httpClient: new MockHttpClient(),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
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
