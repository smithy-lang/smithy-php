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
    HttpChecksumPlugin,
    ModeledBehaviorPlugin,
    RequestCompressionPlugin
};

#[CoversClass(ModeledBehaviorPlugin::class)]
final class ModeledBehaviorPluginTest extends TestCase
{
    public function testContributesModeledBehaviorPlugins(): void
    {
        self::assertSame(
            ModeledBehaviorPlugin::instance(),
            ModeledBehaviorPlugin::instance()
        );
        self::assertEquals(
            [
                new RequestCompressionPlugin(),
                new HttpChecksumPlugin()
            ],
            ModeledBehaviorPlugin::instance()->getChildPlugins()
        );
    }
}
