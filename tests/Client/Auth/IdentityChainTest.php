<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    ChainIdentityProviderInterface,
    ChainSetup,
    IdentityChain,
    IdentityInterface,
    IdentityResolverInterface,
    OrderingConstraint
};
use Smithy\Tests\Client\Auth\Utils\{
    CallLogFixture,
    CountingResolverFixture,
    CountingThrowingResolverFixture,
    SeenValueFixture,
    SequenceResolverFixture,
    StaticResolverFixture,
    TestIdentityFixture,
    TestProviderFixture,
    ThrowingResolverFixture
};

final class IdentityChainTest extends TestCase
{
    public function testProvidersAreAssembledInSlotOrder(): void
    {
        $log = new CallLogFixture();
        $chain = new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'after-a',
                    OrderingConstraint::after('slot-a'),
                    $log,
                    resolver: self::staticResolver()
                ),
                new TestProviderFixture(
                    'standard-a',
                    OrderingConstraint::standard('slot-a'),
                    $log,
                    resolver: self::staticResolver()
                ),
                new TestProviderFixture(
                    'before-b',
                    OrderingConstraint::before('slot-b'),
                    $log,
                    resolver: self::staticResolver()
                ),
                new TestProviderFixture(
                    'standard-b',
                    OrderingConstraint::standard('slot-b'),
                    $log,
                    resolver: self::staticResolver()
                )
            ],
            slotOrder: ['slot-a', 'slot-b']
        );

        $this->assertSame(
            ['standard-a', 'after-a', 'before-b', 'standard-b'],
            $log->calls
        );
        $this->assertSame(
            ['standard-a', 'after-a', 'before-b', 'standard-b'],
            \array_map(
                static fn ($registration) =>
                    $registration->metadata->providerName,
                $chain->getRegistrations()
            )
        );
    }

    public function testTerminalResolverStopsLaterProviderSetup(): void
    {
        $log = new CallLogFixture();

        new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'first',
                    OrderingConstraint::standard('slot-a'),
                    $log,
                    resolver: self::staticResolver(),
                    terminal: true
                ),
                new TestProviderFixture(
                    'later',
                    OrderingConstraint::standard('slot-b'),
                    $log,
                    resolver: self::staticResolver()
                )
            ],
            slotOrder: ['slot-a', 'slot-b']
        );

        $this->assertSame(['first'], $log->calls);
    }

    public function testPropertyBagIsSharedAcrossProviders(): void
    {
        $seen = new SeenValueFixture();
        $log = new CallLogFixture();

        new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new class($log) implements ChainIdentityProviderInterface {
                    public function __construct(
                        private readonly CallLogFixture $log
                    ) {}

                    public function name(): string
                    {
                        return 'writer';
                    }

                    public function ordering(): OrderingConstraint
                    {
                        return OrderingConstraint::standard('slot-a');
                    }

                    public function featureIds(): array
                    {
                        return [];
                    }

                    public function setup(
                        string $identityType,
                        ChainSetup $setup
                    ): void
                    {
                        $this->log->calls[] = 'writer';
                        $setup->setProperty('region', 'test-region');
                    }
                },
                new class(
                    $log,
                    $seen
                ) implements ChainIdentityProviderInterface {
                    public function __construct(
                        private readonly CallLogFixture $log,
                        private readonly SeenValueFixture $seen
                    ) {}

                    public function name(): string
                    {
                        return 'reader';
                    }

                    public function ordering(): OrderingConstraint
                    {
                        return OrderingConstraint::standard('slot-b');
                    }

                    public function featureIds(): array
                    {
                        return [];
                    }

                    public function setup(
                        string $identityType,
                        ChainSetup $setup
                    ): void
                    {
                        $this->log->calls[] = 'reader';
                        $this->seen->value = $setup->getProperty('region');
                    }
                }
            ],
            slotOrder: ['slot-a', 'slot-b']
        );

        $this->assertSame(['writer', 'reader'], $log->calls);
        $this->assertSame('test-region', $seen->value);
    }

    public function testChainSetupReturnsStoredNullProperty(): void
    {
        $setup = new ChainSetup(['region' => null]);

        $this->assertTrue($setup->hasProperty('region'));
        $this->assertNull($setup->getProperty('region', 'fallback'));
        $this->assertSame('fallback', $setup->getProperty(
            'missing',
            'fallback'
        ));
    }

    public function testResolveIdentityTracksWinningResolverMetadata(): void
    {
        $firstLog = new CallLogFixture();
        $secondLog = new CallLogFixture();
        $chain = new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'first',
                    OrderingConstraint::standard('slot-a'),
                    $firstLog,
                    resolver: new ThrowingResolverFixture('nope')
                ),
                new TestProviderFixture(
                    'second',
                    OrderingConstraint::standard('slot-b'),
                    $secondLog,
                    resolver: self::staticResolver(),
                    featureIds: ['x', 'y']
                )
            ],
            slotOrder: ['slot-a', 'slot-b']
        );

        $identity = $chain->resolveIdentity();

        $this->assertInstanceOf(TestIdentityFixture::class, $identity);
        $this->assertSame(
            'second',
            $chain->getLastResolvedMetadata()?->providerName
        );
        $this->assertSame(
            ['x', 'y'],
            $chain->getLastResolvedMetadata()?->featureIds
        );
    }

    public function testResolveIdentityCachesWinningResolver(): void
    {
        $firstResolver = new CountingThrowingResolverFixture('nope');
        $secondResolver = new CountingResolverFixture(
            new TestIdentityFixture()
        );
        $chain = new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'first',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture(),
                    resolver: $firstResolver
                ),
                new TestProviderFixture(
                    'second',
                    OrderingConstraint::standard('slot-b'),
                    new CallLogFixture(),
                    resolver: $secondResolver
                )
            ],
            slotOrder: ['slot-a', 'slot-b']
        );

        $first = $chain->resolveIdentity();
        $second = $chain->resolveIdentity();

        $this->assertSame($first, $second);
        $this->assertSame(1, $firstResolver->calls);
        $this->assertSame(1, $secondResolver->calls);
        $this->assertSame(
            'second',
            $chain->getLastResolvedMetadata()?->providerName
        );
    }

    public function testResolveIdentityCachesByProperties(): void
    {
        $first = new TestIdentityFixture();
        $second = new TestIdentityFixture();
        $resolver = new SequenceResolverFixture([$first, $second]);
        $chain = new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'cache',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture(),
                    resolver: $resolver
                )
            ],
            slotOrder: ['slot-a']
        );

        $this->assertSame($first, $chain->resolveIdentity([
            'scope' => 'one'
        ]));
        $this->assertSame($first, $chain->resolveIdentity([
            'scope' => 'one'
        ]));
        $this->assertSame($second, $chain->resolveIdentity([
            'scope' => 'two'
        ]));
        $this->assertSame($second, $chain->resolveIdentity([
            'scope' => 'two'
        ]));
        $this->assertSame(2, $resolver->calls);
    }

    public function testResolveIdentityAggregatesFailures(): void
    {
        $chain = new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'one',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture(),
                    resolver: new ThrowingResolverFixture('missing env')
                ),
                new TestProviderFixture(
                    'two',
                    OrderingConstraint::standard('slot-b'),
                    new CallLogFixture(),
                    resolver: new ThrowingResolverFixture('expired token')
                )
            ],
            slotOrder: ['slot-a', 'slot-b']
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unable to resolve identity from any provider in the chain. '
                . 'Tried: one: missing env; two: expired token'
        );

        $chain->resolveIdentity();
    }

    public function testInvalidateClearsLastMetadataAndRunsInvalidators(): void
    {
        $invalidated = 0;
        $chain = new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'cache',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture(),
                    resolver: self::staticResolver(),
                    invalidator: static function () use (&$invalidated): void {
                        $invalidated++;
                    }
                )
            ],
            slotOrder: ['slot-a']
        );

        $chain->resolveIdentity();
        $this->assertSame(
            'cache',
            $chain->getLastResolvedMetadata()?->providerName
        );

        $chain->invalidate();

        $this->assertNull($chain->getLastResolvedMetadata());
        $this->assertSame(1, $invalidated);
    }

    public function testInvalidatorMustBeCallable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Identity resolver invalidator must be callable.'
        );

        new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'cache',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture(),
                    resolver: self::staticResolver(),
                    invalidator: 'not-callable'
                )
            ],
            slotOrder: ['slot-a']
        );
    }

    public function testInvalidateForcesChainToResolveAgain(): void
    {
        $resolver = new CountingResolverFixture(new TestIdentityFixture());
        $chain = new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'cache',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture(),
                    resolver: $resolver
                )
            ],
            slotOrder: ['slot-a']
        );

        $chain->resolveIdentity();
        $chain->invalidate();
        $chain->resolveIdentity();

        $this->assertSame(2, $resolver->calls);
    }

    public function testDuplicateProviderNamesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Duplicate provider name registered: dup.'
        );

        new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'dup',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture()
                ),
                new TestProviderFixture(
                    'dup',
                    OrderingConstraint::standard('slot-b'),
                    new CallLogFixture()
                )
            ],
            slotOrder: ['slot-a', 'slot-b']
        );
    }

    public function testDuplicateStandardSlotClaimsAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Multiple providers claim standard slot slot-a.'
        );

        new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'one',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture()
                ),
                new TestProviderFixture(
                    'two',
                    OrderingConstraint::standard('slot-a'),
                    new CallLogFixture()
                )
            ],
            slotOrder: ['slot-a']
        );
    }

    public function testUnknownSlotReferencesAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Provider mystery references unknown slot slot-z.'
        );

        new IdentityChain(
            TestIdentityFixture::class,
            providers: [
                new TestProviderFixture(
                    'mystery',
                    OrderingConstraint::before('slot-z'),
                    new CallLogFixture()
                )
            ],
            slotOrder: ['slot-a']
        );
    }

    private static function staticResolver(): StaticResolverFixture
    {
        return new StaticResolverFixture(new TestIdentityFixture());
    }
}
