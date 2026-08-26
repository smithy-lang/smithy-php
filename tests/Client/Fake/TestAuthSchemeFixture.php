<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Client\Auth\{
    AnonymousIdentityResolver,
    AuthSchemeInterface,
    IdentityResolverInterface,
    NullSigner,
    SignerInterface
};
use Smithy\Client\ClientConfig;

/**
 * Auth scheme fixture with a configurable scheme ID.
 */
final class TestAuthSchemeFixture implements AuthSchemeInterface
{
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
}
