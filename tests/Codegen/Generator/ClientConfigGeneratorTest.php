<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\ClientConfigGenerator;
use Smithy\Codegen\Model\SmithyModel;

final class ClientConfigGeneratorTest extends TestCase
{
    public function testGeneratesClientConfigClassForKnownServiceShape(): void
    {
        // Non-AWS service (no ruleset trait) -> generator emits an empty
        // `<ShortName>ClientConfig` subclass of `Smithy\Client\ClientConfig`.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ClientConfigGenerator($model, $settings);

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertStringStartsWith(
            "<?php\n\n"
            . "/*\n"
            . " * Copyright Amazon.com, Inc. or its affiliates."
            . " All Rights Reserved.\n"
            . " * SPDX-License-Identifier: Apache-2.0\n"
            . " */\n\n"
            . "declare(strict_types=1);",
            $file->content
        );
        // Extends the configured base ClientConfig from Smithy\Client.
        $this->assertStringContainsString(
            'use Smithy\\Client\\ClientConfig;',
            $file->content
        );
        $this->assertMatchesRegularExpression(
            '/final class \w+ClientConfig '
                . 'extends ClientConfig/',
            $file->content
        );
        $this->assertStringEndsWith('ClientConfig.php', $file->path);
        // Sits directly under the configured namespace with flat organization.
        $this->assertStringContainsString(
            'namespace Smithy\\Example;',
            $file->content
        );
    }

    public function testImportsBaseClientConfigClass(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ClientConfigGenerator($model, $settings);

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertStringContainsString(
            'use Smithy\\Client\\ClientConfig;',
            $file->content
        );
        $this->assertStringContainsString(
            'final class ExampleClientConfig '
                . 'extends ClientConfig {}',
            $file->content
        );
    }

    public function testExtendsConfiguredClientConfigClass(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            clientConfigClass: 'Vendor\\Runtime\\VendorClientConfig'
        );
        $generator = new ClientConfigGenerator($model, $settings);

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertStringContainsString(
            'use Vendor\\Runtime\\VendorClientConfig;',
            $file->content
        );
        $this->assertStringContainsString(
            'final class ExampleClientConfig '
                . 'extends VendorClientConfig {}',
            $file->content
        );
    }

    public function testEmitsCopyWithForAbstractClientConfigBase(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            clientConfigClass: 'Smithy\\Client\\AbstractClientConfig'
        );
        $generator = new ClientConfigGenerator($model, $settings);

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertStringContainsString(
            'use Smithy\\Client\\AbstractClientConfig;',
            $file->content
        );
        $this->assertStringContainsString(
            "final class ExampleClientConfig extends AbstractClientConfig\n{",
            $file->content
        );
        $this->assertStringContainsString(
            "    protected function copyWith(array \$overrides): static\n"
                . "    {\n",
            $file->content
        );
        $this->assertStringContainsString(
            "logger: \$overrides['logger'] ?? \$this->logger\n",
            $file->content
        );
    }

    public function testGenerateRaisesForUnknownServiceShapeId(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new ClientConfigGenerator($model, $settings);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Service not found/');

        $generator->generate('smithy.example#Nonexistent');
    }
}
