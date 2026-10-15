<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\ServiceGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\ServiceProjection;
use Smithy\Core\Model\SmithyModel as RuntimeSmithyModel;
use Smithy\Core\Serde\{
    SchemaConverter,
    SchemaTraitRegistry
};
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Codegen\Generator\Utils\GetThingOperation;

final class ServiceGeneratorTest extends TestCase
{
    public function testGeneratesServiceMetadataClass(): void
    {
        $model = $this->model();
        $generator = new ServiceGenerator(
            $model,
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        $file = $generator->generate(
            'smithy.example#ExampleService',
            $this->projection($model)
        );

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
            'use Smithy\\Core\\Serde\\Schema;',
            $file->content
        );
        $this->assertStringContainsString(
            'use Smithy\\Core\\Shape\\ShapeType;',
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
        $this->assertStringContainsString(
            'public static function schema(): Schema',
            $file->content
        );
        $this->assertStringContainsString(
            "'smithy.protocols#rpcv2Cbor' => true",
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

        $file = $generator->generate(
            'smithy.example#EmptyService',
            $this->projection($model)
        );

        $this->assertStringContainsString('return [];', $file->content);
    }

    public function testGeneratedServiceClassUsesTraitBehavior(): void
    {
        $namespace = 'Smithy\\Tests\\Codegen\\Generator\\Utils';
        $model = $this->model();
        $generator = new ServiceGenerator(
            $model,
            new CodegenSettings(
                namespace: $namespace,
                outputDir: '/tmp/out',
                organization: 'flat',
                schemaTraitIndices: [
                    'vendor.example#metadata' => 30,
                    'vendor.example#marker' => 31,
                    'vendor.example#nullable' => 32
                ]
            )
        );

        $file = $generator->generate(
            'smithy.example#ExampleService',
            $this->projection($model)
        );
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
        $this->assertSame($service->getSchema(), $class::schema());
        $this->assertSame(
            ['enabled' => true],
            $service->getSchema()->getTrait(30)
        );
        $this->assertTrue($service->getSchema()->getTrait(31));
        $this->assertSame(
            ['enabled' => true],
            $service->getSchema()->getTraitById(
                'vendor.example#metadata'
            )
        );
        $this->assertTrue(
            $service->getSchema()->getTraitById('vendor.example#marker')
        );
        $this->assertTrue(
            $service->getSchema()->getTraitById(
                'smithy.protocols#rpcv2Cbor'
            )
        );
        $this->assertArrayHasKey(32, $service->getSchema()->getTraits());
        $this->assertNull($service->getSchema()->getTraits()[32]);
        $this->assertArrayHasKey(
            'vendor.example#nullable',
            $service->getSchema()->getTraitsById()
        );
        $this->assertNull(
            $service->getSchema()->getTraitsById()[
                'vendor.example#nullable'
            ]
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

    public function testGeneratedServiceSchemaMatchesRuntimeSchema(): void
    {
        $namespace = 'Smithy\\Tests\\Codegen\\Generator\\Parity';
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: '/tmp/out',
            organization: 'flat',
            schemaTraitIndices: [
                'vendor.example#metadata' => 30,
                'vendor.example#marker' => 31,
                'vendor.example#nullable' => 32
            ]
        );
        $model = $this->model();
        $file = (new ServiceGenerator($model, $settings))->generate(
            'smithy.example#ExampleService',
            $this->projection($model)
        );
        $class = $namespace . '\\ParityService';
        if (!\class_exists($class, false)) {
            eval($this->evalSource($file->content));
        }
        $generated = $class::schema();

        $traitRegistry = new SchemaTraitRegistry();
        $traitRegistry->register('vendor.example#metadata', 30);
        $traitRegistry->register(
            'vendor.example#marker',
            31,
            static fn(mixed $value): bool => $value === []
        );
        $traitRegistry->register('vendor.example#nullable', 32);
        $runtime = (new SchemaConverter(
            RuntimeSmithyModel::fromJson($this->modelJson()),
            $traitRegistry
        ))->getSchema('smithy.example#ExampleService');

        $this->assertSame($runtime->id, $generated->id);
        $this->assertSame($runtime->type, $generated->type);
        $this->assertSame($runtime->getTrait(30), $generated->getTrait(30));
        $this->assertSame($runtime->getTrait(31), $generated->getTrait(31));
        $this->assertSame(
            $runtime->getTraitById('vendor.example#metadata'),
            $generated->getTraitById('vendor.example#metadata')
        );
        $this->assertSame(
            $runtime->getTraitById('vendor.example#marker'),
            $generated->getTraitById('vendor.example#marker')
        );
        $this->assertArrayHasKey(32, $runtime->getTraits());
        $this->assertArrayHasKey(32, $generated->getTraits());
        $this->assertNull($runtime->getTraits()[32]);
        $this->assertNull($generated->getTraits()[32]);
        $this->assertArrayHasKey(
            'vendor.example#nullable',
            $runtime->getTraitsById()
        );
        $this->assertArrayHasKey(
            'vendor.example#nullable',
            $generated->getTraitsById()
        );
        $this->assertNull(
            $runtime->getTraitsById()['vendor.example#nullable']
        );
        $this->assertNull(
            $generated->getTraitsById()['vendor.example#nullable']
        );
    }

    public function testGenerateRaisesForUnknownServiceShapeId(): void
    {
        $model = $this->model();
        $generator = new ServiceGenerator(
            $model,
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Shape not found: smithy.example#Missing'
        );

        $generator->generate(
            'smithy.example#Missing',
            $this->projection($model)
        );
    }

    public function testGenerateRaisesForNonServiceShape(): void
    {
        $model = $this->model();
        $generator = new ServiceGenerator(
            $model,
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Shape smithy.example#GetThing is not a service'
        );

        $generator->generate(
            'smithy.example#GetThing',
            $this->projection($model)
        );
    }

    private function model(): SmithyModel
    {
        return SmithyModel::fromJson($this->modelJson());
    }

    private function projection(SmithyModel $model): ServiceProjection
    {
        return ServiceProjection::fromModel($model, null);
    }

    private function modelJson(): string
    {
        return \json_encode([
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
                            . 'with     tags.',
                        'smithy.protocols#rpcv2Cbor' => [],
                        'vendor.example#metadata' => [
                            'enabled' => true
                        ],
                        'vendor.example#marker' => [],
                        'vendor.example#nullable' => null
                    ]
                ],
                'smithy.example#GetThing' => [
                    'type' => 'operation'
                ]
            ]
        ], JSON_THROW_ON_ERROR);
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
