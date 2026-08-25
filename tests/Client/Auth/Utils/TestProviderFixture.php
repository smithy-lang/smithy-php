<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\{
    ChainIdentityProviderInterface,
    ChainSetup,
    IdentityResolverInterface,
    OrderingConstraint
};
final class TestProviderFixture implements ChainIdentityProviderInterface
{
    /** @var list<string> */
    private array $featureIds;

    public function __construct(
        private readonly string $name,
        private readonly OrderingConstraint $ordering,
        private readonly CallLogFixture $log,
        private readonly ?IdentityResolverInterface $resolver = null,
        array $featureIds = [],
        private readonly bool $terminal = false,
        private readonly mixed $invalidator = null
    )
    {
        $this->featureIds = \array_values($featureIds);
    }

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

    public function setup(string $identityType, ChainSetup $setup): void
    {
        $this->log->calls[] = $this->name;
        if ($identityType !== TestIdentityFixture::class
            || $this->resolver === null
        ) {
            return;
        }

        if ($this->terminal) {
            $setup->addTerminalResolver($this->resolver, $this->invalidator);
            return;
        }

        $setup->addResolver($this->resolver, $this->invalidator);
    }
}
