<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Auth\{
    AnonymousIdentityResolver,
    AuthSchemeInterface,
    IdentityResolverInterface,
    IdentityResolversInterface,
    NullSigner,
    SignerInterface
};
use Smithy\Core\Context\Context;
use Smithy\Core\Shape\OperationInterface;

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

    public function getIdentityProperties(Context $context): array
    {
        return $this->identityProperties;
    }

    public function getIdentityResolver(
        IdentityResolversInterface $resolvers
    ): ?IdentityResolverInterface
    {
        return $this->identityResolver
            ?? AnonymousIdentityResolver::instance();
    }

    public function getSignerProperties(
        Context $context,
        ?OperationInterface $operation = null
    ): array
    {
        return $this->signerProperties;
    }

    public function getSigner(): SignerInterface
    {
        return $this->signer ?? NullSigner::instance();
    }
}
