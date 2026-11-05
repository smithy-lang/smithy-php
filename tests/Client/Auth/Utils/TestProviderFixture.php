<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    ChainIdentityProviderInterface,
    ChainSetup,
    IdentityResolverInterface,
    OrderingConstraint
};
/**
 * @implements ChainIdentityProviderInterface<TestIdentityFixture>
 */
final class TestProviderFixture implements ChainIdentityProviderInterface
{
    /**
     * @param IdentityResolverInterface<TestIdentityFixture>|null $resolver
     * @param list<string> $featureIds
     */
    public function __construct(
        private readonly string $name,
        private readonly OrderingConstraint $ordering,
        private readonly CallLogFixture $log,
        private readonly ?IdentityResolverInterface $resolver = null,
        private readonly array $featureIds = [],
        private readonly bool $terminal = false
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function ordering(): OrderingConstraint
    {
        return $this->ordering;
    }

    public function featureIds(): array
    {
        return $this->featureIds;
    }

    /**
     * @param ChainSetup<TestIdentityFixture> $setup
     */
    public function setup(ChainSetup $setup): void
    {
        $this->log->calls[] = $this->name;
        if ($this->resolver === null) {
            return;
        }

        if ($this->terminal) {
            $setup->addTerminalResolver($this->resolver);
            return;
        }

        $setup->addResolver($this->resolver);
    }
}
