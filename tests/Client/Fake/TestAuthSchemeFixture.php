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
    public function __construct(
        private readonly string $id,
        private readonly ?IdentityResolverInterface $identityResolver = null,
        private readonly ?SignerInterface $signer = null,
        private readonly array $identityProperties = [],
        private readonly array $signerProperties = []
    ) {}

    public function getSchemeId(): string
    {
        return $this->id;
    }

    public function getIdentityProperties(ClientConfig $config): array
    {
        return $this->identityProperties;
    }

    public function getIdentityResolver(
        ClientConfig $config
    ): IdentityResolverInterface
    {
        return $this->identityResolver
            ?? AnonymousIdentityResolver::instance();
    }

    public function getSignerProperties(ClientConfig $config): array
    {
        return $this->signerProperties;
    }

    public function getSigner(): SignerInterface
    {
        return $this->signer ?? NullSigner::instance();
    }
}
