<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Auth\Aws;

use Smithy\Auth\AuthSchemeInterface;
use Smithy\Auth\IdentityResolverInterface;
use Smithy\Auth\IdentityResolversInterface;
use Smithy\Auth\SignerInterface;
use Smithy\Core\Context\{
    Context,
    ContextKey
};
use Smithy\Core\Shape\OperationInterface;

/**
 * Smithy auth scheme wrapping AWS SigV4 (`aws.auth#sigv4`).
 *
 * Constructed by the per-service client. The scheme knows the service's
 * default signing name and region and hands the pipeline a
 * {@see SigV4Signer} plus the AWS credential resolver registered by
 * the client config.
 */
final class SigV4AuthScheme implements AuthSchemeInterface
{
    public const SCHEME_ID = 'aws.auth#sigv4';

    /**
     * Operation trait requesting an unsigned request payload over HTTPS.
     */
    public const UNSIGNED_PAYLOAD_TRAIT = 'aws.auth#unsignedPayload';

    /** @var ContextKey<array<string, mixed>>|null */
    private static ?ContextKey $identityContextKey = null;

    public function __construct(
        private readonly string $signingName,
        private readonly string $signingRegion,
    ) {}

    /**
     * Client config context key holding the identity properties that the
     * client config builds.
     *
     * @return ContextKey<array<string, mixed>>
     */
    public static function identityContextKey(): ContextKey
    {
        return self::$identityContextKey ??= new ContextKey(
            'aws.identityContext'
        );
    }

    public function getSchemeId(): string
    {
        return self::SCHEME_ID;
    }

    /**
     * @return array<string, mixed>
     */
    public function getIdentityProperties(Context $context): array
    {
        return $context->get(self::identityContextKey()) ?? [];
    }

    public function getIdentityResolver(
        IdentityResolversInterface $resolvers
    ): ?IdentityResolverInterface
    {
        return $resolvers->identityResolver(
            AwsCredentialsIdentityInterface::class
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getSignerProperties(
        Context $context,
        ?OperationInterface $operation = null
    ): array
    {
        $properties = [
            'signingName' => $this->signingName,
            'signingRegion' => $this->signingRegion,
        ];
        if ($operation?->getSchema()->hasTraitById(
            self::UNSIGNED_PAYLOAD_TRAIT
        )) {
            $properties['unsignedPayload'] = true;
        }

        return $properties;
    }

    public function getSigner(): SignerInterface
    {
        return new SigV4Signer(
            $this->signingName,
            $this->signingRegion
        );
    }
}
