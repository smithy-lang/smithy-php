<?php

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
            "<?php\n\ndeclare(strict_types=1);",
            $file->content
        );
        // Extends the base ClientConfig from Smithy\Client.
        $this->assertStringContainsString(
            'extends ClientConfig',
            $file->content
        );
        $this->assertMatchesRegularExpression(
            '/final class \w+ClientConfig extends ClientConfig/',
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
