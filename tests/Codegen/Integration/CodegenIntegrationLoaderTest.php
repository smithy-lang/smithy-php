<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Integration\CodegenIntegrationLoader;
use Smithy\Tests\Codegen\Fixtures\CliCodegenIntegration;

#[CoversClass(CodegenIntegrationLoader::class)]
final class CodegenIntegrationLoaderTest extends TestCase
{
    public function testLoadsAndConfiguresIntegration(): void
    {
        $integrations = (new CodegenIntegrationLoader())->load([
            [
                'class' => CliCodegenIntegration::class,
                'settings' => ['fileName' => 'Configured.php']
            ]
        ]);

        $this->assertCount(1, $integrations);
        $this->assertInstanceOf(
            CliCodegenIntegration::class,
            $integrations[0]
        );
    }

    public function testRejectsMissingIntegrationClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Codegen integration class does not exist: MissingIntegration'
        );

        (new CodegenIntegrationLoader())->load([
            ['class' => 'MissingIntegration']
        ]);
    }

    public function testRejectsNonIntegrationClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        (new CodegenIntegrationLoader())->load([
            ['class' => \stdClass::class]
        ]);
    }
}
