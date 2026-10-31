<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Auth\{
    ChainSetup,
    OrderingConstraint
};
use Smithy\Tests\Client\Auth\Utils\{
    CallLogFixture,
    StaticResolverFixture,
    TestIdentityFixture,
    TestProviderFixture
};

#[CoversClass(ChainSetup::class)]
final class ChainSetupTest extends TestCase
{
    public function testStoresNullProperties(): void
    {
        /** @var ChainSetup<TestIdentityFixture> $setup */
        $setup = new ChainSetup(['region' => null]);

        self::assertTrue($setup->hasProperty('region'));
        self::assertNull($setup->getProperty('region', 'fallback'));
        self::assertSame(
            'fallback',
            $setup->getProperty('missing', 'fallback')
        );

        $setup->setProperty('region', 'us-east-1');

        self::assertSame(
            ['region' => 'us-east-1'],
            $setup->properties()
        );
    }

    public function testRegistersProviderMetadataAndDetectedSlots(): void
    {
        /** @var ChainSetup<TestIdentityFixture> $setup */
        $setup = new ChainSetup();
        $provider = new TestProviderFixture(
            'provider',
            OrderingConstraint::standard('slot-a'),
            new CallLogFixture(),
            featureIds: ['provider-feature']
        );

        $setup->setCurrentProvider($provider);
        $setup->addResolver(
            new StaticResolverFixture(new TestIdentityFixture()),
            featureIds: ['resolver-feature']
        );
        $setup->markDetected('slot-a');
        $setup->markDetected('slot-a');
        $setup->markDetected('');

        $registrations = $setup->registrations();
        self::assertCount(1, $registrations);
        self::assertSame(
            'provider',
            $registrations[0]['metadata']->providerName
        );
        self::assertSame(
            ['resolver-feature'],
            $registrations[0]['metadata']->featureIds
        );
        self::assertSame(['slot-a'], $setup->detectedSlots());
    }

    public function testTerminalResolverPreventsFurtherRegistration(): void
    {
        /** @var ChainSetup<TestIdentityFixture> $setup */
        $setup = new ChainSetup();
        $setup->setCurrentProvider(new TestProviderFixture(
            'provider',
            OrderingConstraint::standard('slot-a'),
            new CallLogFixture()
        ));
        $resolver = new StaticResolverFixture(new TestIdentityFixture());
        $setup->addTerminalResolver($resolver);

        self::assertTrue($setup->isTerminal());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('terminal resolver');

        $setup->addResolver($resolver);
    }
}
