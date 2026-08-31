<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\ServiceGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Codegen\Generator\Utils\GetThingOperation;

final class ServiceGeneratorTest extends TestCase
{
    public function testGeneratesServiceMetadataClass(): void
    {
        $generator = new ServiceGenerator(
            $this->model(),
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        $file = $generator->generate('smithy.example#ExampleService');

        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'ExampleService.php',
            $file->path
        );
        $this->assertStringStartsWith(
            "<?php\n\ndeclare(strict_types=1);",
            $file->content
        );
        $this->assertStringContainsString(
            'namespace Smithy\\Example;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Core\\Shape\\GeneratedServiceTrait;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Example\\Operation\\GetThingOperation;',
            $file->content
        );
        $this->assertStringContainsString(
            'final class ExampleService implements ServiceInterface',
            $file->content
        );
        $this->assertStringContainsString(
            'Example documentation with tags.',
            $file->content
        );
        $this->assertStringContainsString(
            "return 'Example';",
            $file->content
        );
        $this->assertStringContainsString(
            "return 'smithy.example#ExampleService';",
            $file->content
        );
        $this->assertStringContainsString(
            "return '2026-08-31';",
            $file->content
        );
        $this->assertStringContainsString(
            'GetThingOperation::class',
            $file->content
        );
    }

    public function testGeneratesServiceWithNoOperations(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#EmptyService' => [
                    'type' => 'service'
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $generator = new ServiceGenerator(
            $model,
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out',
                organization: 'flat'
            )
        );

        $file = $generator->generate('smithy.example#EmptyService');

        $this->assertStringContainsString('return [];', $file->content);
    }

    public function testGeneratedServiceClassUsesTraitBehavior(): void
    {
        $namespace = 'Smithy\\Tests\\Codegen\\Generator\\Utils';
        $generator = new ServiceGenerator(
            $this->model(),
            new CodegenSettings(
                namespace: $namespace,
                outputDir: '/tmp/out',
                organization: 'flat'
            )
        );

        $file = $generator->generate('smithy.example#ExampleService');
        $class = $namespace . '\\UtilsService';
        if (!\class_exists($class, false)) {
            eval($this->evalSource($file->content));
        }

        $service = $class::instance();

        $this->assertSame($service, $class::instance());
        $this->assertSame('Utils', $service->getName());
        $this->assertSame('smithy.example#ExampleService', $service->getId());
        $this->assertSame('2026-08-31', $service->getVersion());
        $this->assertSame(
            ShapeType::Service->value,
            $service->getSchema()->type
        );
        $this->assertSame(
            [GetThingOperation::instance()],
            $service->getOperations()
        );
        $this->assertSame(
            GetThingOperation::instance(),
            $service->getOperation('GetThing')
        );
        $this->assertNull($service->getOperation('Missing'));
    }

    public function testGenerateRaisesForUnknownServiceShapeId(): void
    {
        $generator = new ServiceGenerator(
            $this->model(),
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Shape not found: smithy.example#Missing'
        );

        $generator->generate('smithy.example#Missing');
    }

    public function testGenerateRaisesForNonServiceShape(): void
    {
        $generator = new ServiceGenerator(
            $this->model(),
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Shape smithy.example#GetThing is not a service'
        );

        $generator->generate('smithy.example#GetThing');
    }

    private function model(): SmithyModel
    {
        return SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#ExampleService' => [
                    'type' => 'service',
                    'version' => '2026-08-31',
                    'operations' => [
                        ['target' => 'smithy.example#GetThing']
                    ],
                    'traits' => [
                        'smithy.api#documentation' =>
                            '<p>Example documentation</p>' . "\n"
                            . 'with     tags.'
                    ]
                ],
                'smithy.example#GetThing' => [
                    'type' => 'operation'
                ]
            ]
        ], JSON_THROW_ON_ERROR));
    }

    private function evalSource(string $source): string
    {
        return (string) \preg_replace(
            '/^<\?php\s+declare\(strict_types=1\);\s*/',
            '',
            $source
        );
    }
}
