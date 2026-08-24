<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\TypeRegistryGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;

final class TypeRegistryGeneratorTest extends TestCase
{
    public function testGeneratesTypeRegistryForKnownServiceShape(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new TypeRegistryGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(
            'smithy.example#DefaultsService',
            ['smithy.example#Settings' => true]
        );

        // File name follows the pattern `<ShortServiceName>TypeRegistry.php`.
        $this->assertStringContainsString('TypeRegistry.php', $file->path);
        // Class declaration references the service.
        $this->assertStringContainsString('TypeRegistry', $file->content);
        // Preamble is present.
        $this->assertStringStartsWith(
            "<?php\n\ndeclare(strict_types=1);",
            $file->content
        );
    }

    public function testRaisesWhenServiceShapeIdMissing(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new TypeRegistryGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Service not found/');

        $generator->generate('smithy.example#Nonexistent', []);
    }

    public function testRaisesWhenShapeIsNotAService(): void
    {
        // Point at a structure ID - the generator must reject non-service
        // shapes.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new TypeRegistryGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is not a service/');

        $generator->generate('smithy.example#Settings', []);
    }
}
