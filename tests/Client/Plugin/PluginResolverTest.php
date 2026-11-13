<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\StaticEndpointResolver;
use Smithy\Client\Plugin\{
    HttpChecksumPlugin,
    PluginCollection,
    PluginInterface,
    PluginPhase,
    PluginResolver,
    PluginState,
    RequestCompressionPlugin,
    ResolvedPlugins
};
use Smithy\Core\Endpoint\Endpoint;
use Smithy\Tests\Client\Fake\FakeProtocol;
use Smithy\Tests\Client\Plugin\Utils\{
    RecordingPluginFixture,
    RegisteringPluginFixture,
    SecondaryRecordingPluginFixture
};
use Smithy\Tests\Utils\Http\MockHttpClient;

#[CoversClass(PluginResolver::class)]
#[CoversClass(ResolvedPlugins::class)]
#[CoversClass(PluginState::class)]
final class PluginResolverTest extends TestCase
{
    public function testSortsByPhaseThenInsertionOrder(): void
    {
        $calls = new \ArrayObject();
        $applyFirst = new class(
            'apply-first',
            $calls,
            PluginPhase::Apply
        ) extends RecordingPluginFixture {};
        $first = new class(
            'first',
            $calls,
            PluginPhase::First
        ) extends RecordingPluginFixture {};
        $applySecond = new class(
            'apply-second',
            $calls,
            PluginPhase::Apply
        ) extends RecordingPluginFixture {};
        $defaults = new class(
            'defaults',
            $calls,
            PluginPhase::Defaults
        ) extends RecordingPluginFixture {};

        $resolved = (new PluginResolver())->resolve(
            new PluginCollection(
                $applyFirst,
                $first,
                $applySecond,
                $defaults
            ),
            new PluginState()
        );
        $resolved->apply($this->config());

        self::assertSame(
            ['first', 'defaults', 'apply-first', 'apply-second'],
            $calls->getArrayCopy()
        );
    }

    public function testOrdersCompressionBeforeChecksum(): void
    {
        $resolved = (new PluginResolver())->resolve(
            new PluginCollection(
                new HttpChecksumPlugin(),
                new RequestCompressionPlugin()
            ),
            new PluginState()
        );

        self::assertSame(
            [
                RequestCompressionPlugin::class,
                HttpChecksumPlugin::class
            ],
            \array_map(
                static fn(PluginInterface $plugin): string =>
                    $plugin::class,
                $resolved->getPlugins()
            )
        );
    }

    public function testChildrenResolveInTheirOwnPhases(): void
    {
        $calls = new \ArrayObject();
        $child = new class(
            'child',
            $calls,
            PluginPhase::First
        ) extends RecordingPluginFixture {};
        $parent = new class(
            'parent',
            $calls,
            PluginPhase::Apply,
            [$child]
        ) extends RecordingPluginFixture {};

        (new PluginResolver())
            ->resolve(
                new PluginCollection($parent),
                new PluginState()
            )
            ->apply($this->config());

        self::assertSame(
            ['child', 'parent'],
            $calls->getArrayCopy()
        );
    }

    public function testRejectedParentPreventsChildEvaluation(): void
    {
        $calls = new \ArrayObject();
        $predicateCalls = new \ArrayObject();
        $child = new class('child', $calls)
            extends RecordingPluginFixture {};
        $parent = new class('parent', $calls, children: [$child])
            extends RecordingPluginFixture {};
        $state = new PluginState(
            predicate: static function (
                PluginInterface $plugin
            ) use ($parent, $predicateCalls): bool {
                $predicateCalls[] = $plugin::class;

                return $plugin !== $parent;
            }
        );

        (new PluginResolver())
            ->resolve(new PluginCollection($parent), $state)
            ->apply($this->config());

        self::assertSame([], $calls->getArrayCopy());
        self::assertSame(
            [$parent::class],
            $predicateCalls->getArrayCopy()
        );
    }

    public function testChildIsIndependentlyFiltered(): void
    {
        $calls = new \ArrayObject();
        $child = new class('child', $calls)
            extends RecordingPluginFixture {};
        $parent = new class('parent', $calls, children: [$child])
            extends RecordingPluginFixture {};
        $state = new PluginState(
            predicate: static fn(PluginInterface $plugin): bool =>
                $plugin !== $child
        );

        (new PluginResolver())
            ->resolve(new PluginCollection($parent), $state)
            ->apply($this->config());

        self::assertSame(['parent'], $calls->getArrayCopy());
    }

    public function testDeduplicatesChildrenAndTopLevelPluginsByClass(): void
    {
        $calls = new \ArrayObject();
        $child = new RecordingPluginFixture('child', $calls);
        $duplicate = new RecordingPluginFixture('duplicate', $calls);
        $parent = new class('parent', $calls, children: [$child])
            extends RecordingPluginFixture {};

        (new PluginResolver())
            ->resolve(
                new PluginCollection($parent, $duplicate),
                new PluginState()
            )
            ->apply($this->config());

        self::assertSame(
            ['parent', 'child'],
            $calls->getArrayCopy()
        );
    }

    public function testAppliedPluginDoesNotRunAgainOnDerivedConfig(): void
    {
        $calls = new \ArrayObject();
        $plugin = new RecordingPluginFixture('plugin', $calls);
        $plugins = new PluginCollection($plugin);

        $configured = $plugins->apply($this->config());
        $derived = $configured->withProtocol(new FakeProtocol());
        $plugins->apply($derived);

        self::assertSame(['plugin'], $calls->getArrayCopy());
    }

    public function testCallScopedPluginAppliesRegisteredPlugin(): void
    {
        $calls = new \ArrayObject();
        $child = new RecordingPluginFixture('child', $calls);
        $parent = new RegisteringPluginFixture('parent', $calls, $child);
        $sibling = new SecondaryRecordingPluginFixture('sibling', $calls);

        (new PluginCollection($parent, $sibling))->apply($this->config());

        self::assertSame(
            ['parent', 'sibling', 'child'],
            $calls->getArrayCopy()
        );
    }

    public function testRejectedPluginCanApplyWhenPredicateChanges(): void
    {
        $calls = new \ArrayObject();
        $plugin = new RecordingPluginFixture('plugin', $calls);
        $config = $this->config()->withPluginPredicate(
            static fn(PluginInterface $candidate): bool =>
                !$candidate instanceof RecordingPluginFixture
        );

        $configured = (new PluginCollection($plugin))->apply($config);
        self::assertSame([], $calls->getArrayCopy());

        $configured
            ->withPluginPredicate(
                static fn(PluginInterface $candidate): bool => true
            )
            ->resolvePlugins();

        self::assertSame(['plugin'], $calls->getArrayCopy());
    }

    public function testRejectedChildCanApplyWhenPredicateChanges(): void
    {
        $calls = new \ArrayObject();
        $child = new class('child', $calls)
            extends RecordingPluginFixture {};
        $parent = new class('parent', $calls, children: [$child])
            extends RecordingPluginFixture {};
        $config = $this->config()->withPluginPredicate(
            static fn(PluginInterface $candidate): bool =>
                $candidate !== $child
        );

        $configured = (new PluginCollection($parent))->apply($config);
        self::assertSame(['parent'], $calls->getArrayCopy());

        $configured
            ->withPluginPredicate(
                static fn(PluginInterface $candidate): bool => true
            )
            ->resolvePlugins();

        self::assertSame(['parent', 'child'], $calls->getArrayCopy());
    }

    public function testChildCyclesTerminateThroughClassDeduplication(): void
    {
        $calls = new \ArrayObject();
        $first = new class('first', $calls)
            extends RecordingPluginFixture {};
        $second = new class('second', $calls)
            extends RecordingPluginFixture {};
        $first->setChildPlugins([$second]);
        $second->setChildPlugins([$first]);

        (new PluginResolver())
            ->resolve(
                new PluginCollection($first),
                new PluginState()
            )
            ->apply($this->config());

        self::assertSame(
            ['first', 'second'],
            $calls->getArrayCopy()
        );
    }

    private function config(): ClientConfig
    {
        return new ClientConfig(
            protocol: new FakeProtocol(),
            transport: new \Smithy\Http\Client\HttpClientTransport(
                new MockHttpClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint('https://example.test')
            ),
            pluginState: new PluginState()
        );
    }
}
