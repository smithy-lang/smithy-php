<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Plugin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Plugin\{
    AbstractPlugin,
    AutoPluginInterface,
    ComposerAutoPluginRegistry
};
use Smithy\Tests\Client\Plugin\Utils\{
    AutoPluginFixture,
    ComposerAutoPluginRegistryFixture,
    SecondaryAutoPluginFixture
};

#[CoversClass(ComposerAutoPluginRegistry::class)]
final class ComposerAutoPluginRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        ComposerAutoPluginRegistryFixture::reset();
        AutoPluginFixture::reset();
        SecondaryAutoPluginFixture::reset();
    }

    protected function tearDown(): void
    {
        ComposerAutoPluginRegistryFixture::reset();
    }

    public function testLoadsRegisteredPluginsOnceInRegistrationOrder(): void
    {
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );
        ComposerAutoPluginRegistry::register(
            SecondaryAutoPluginFixture::class
        );

        $first = ComposerAutoPluginRegistry::getPlugins();
        $second = ComposerAutoPluginRegistry::getPlugins();

        self::assertSame($first, $second);
        self::assertInstanceOf(AutoPluginFixture::class, $first[0]);
        self::assertInstanceOf(
            SecondaryAutoPluginFixture::class,
            $first[1]
        );
        self::assertSame(1, AutoPluginFixture::$constructionCount);
        self::assertSame(
            1,
            SecondaryAutoPluginFixture::$constructionCount
        );
    }

    public function testDuplicateRegistrationHasNoEffect(): void
    {
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );

        self::assertCount(
            1,
            ComposerAutoPluginRegistry::getPlugins()
        );
    }

    public function testRejectsMissingPluginClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Composer auto plugin class 'MissingPlugin' does not exist."
        );

        /** @phpstan-ignore argument.type */
        ComposerAutoPluginRegistry::register('MissingPlugin');
    }

    public function testRejectsPluginWithoutMarkerInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'must implement ' . AutoPluginInterface::class
        );

        /** @phpstan-ignore argument.type */
        ComposerAutoPluginRegistry::register(\stdClass::class);
    }

    public function testRejectsLateRegistration(): void
    {
        ComposerAutoPluginRegistry::getPlugins();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'after automatic plugins have been loaded'
        );

        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );
    }

    public function testReportsPluginsThatCannotBeConstructed(): void
    {
        $plugin = new class('required') extends AbstractPlugin implements
            AutoPluginInterface
        {
            public function __construct(
                private readonly string $required
            ) {}

            public function configure(
                \Smithy\Client\AbstractClientConfig $config
            ): \Smithy\Client\AbstractClientConfig
            {
                return $config;
            }
        };
        $class = $plugin::class;
        ComposerAutoPluginRegistry::register(
            AutoPluginFixture::class
        );
        ComposerAutoPluginRegistry::register($class);

        $firstError = null;
        try {
            ComposerAutoPluginRegistry::getPlugins();
        } catch (\RuntimeException $e) {
            $firstError = $e;
        }

        self::assertNotNull($firstError);
        self::assertStringContainsString(
            'must have a public constructor with no required arguments',
            $firstError->getMessage()
        );
        self::assertSame(1, AutoPluginFixture::$constructionCount);

        try {
            ComposerAutoPluginRegistry::getPlugins();
            self::fail('Expected cached plugin loading failure.');
        } catch (\RuntimeException $e) {
            self::assertSame($firstError, $e);
        }

        self::assertSame(1, AutoPluginFixture::$constructionCount);
    }
}
