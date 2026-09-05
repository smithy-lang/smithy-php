<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\StructureGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Codegen\PriorCodegenEvaluator;

final class StructureGeneratorTest extends TestCase
{
    public function testGeneratesFinalClassImplementingSerializableStructInterface(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Address');

        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'Address.php',
            $file->path
        );
        // Class declaration + interface.
        $this->assertStringContainsString(
            'final class Address implements SerializableStructInterface',
            $file->content
        );
        // Shape traits pulled in by the generator.
        $this->assertStringContainsString(
            'use FromMembersTrait;',
            $file->content
        );
        // SHAPE_ID constant.
        $this->assertStringContainsString(
            "public const SHAPE_ID = 'smithy.example#Address';",
            $file->content
        );
    }

    public function testGeneratesPrivateWritablePaginationMembers(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__
                . '/../../Client/Pagination/fixtures/pagination-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(
            'smithy.pagination#ListStringsInput'
        );

        $this->assertStringContainsString(
            'withPaginationToken(string|array|null $token): self',
            $file->content
        );
        $this->assertStringContainsString(
            'private ?string $nextToken;',
            $file->content
        );
        $this->assertStringContainsString(
            'private ?int $pageSize;',
            $file->content
        );
        $this->assertStringNotContainsString(
            'public readonly ?string $nextToken',
            $file->content
        );
        $this->assertStringContainsString(
            '$copy->nextToken = $token;',
            $file->content
        );
        $this->assertStringContainsString(
            '$copy->pageSize = $pageSize;',
            $file->content
        );
    }

    public function testConstructorArgsRenderMembersAsNullableWithDefaults(): void
    {
        // Address has a single optional string member `city`.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Address');

        $this->assertStringContainsString(
            'public readonly ?string $city = null',
            $file->content
        );
    }

    public function testRequiredMembersRenderAsReadonlyPropertiesInOrder(): void
    {
        // Human has three @required members (name, age, address) plus two
        // optional
        // (children, parents). All render as nullable readonly with `= null`.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Human');

        $this->assertStringContainsString(
            'public readonly ?string $name = null,',
            $file->content
        );
        $this->assertStringContainsString(
            'public readonly ?int $age = null,',
            $file->content
        );
        $this->assertStringContainsString(
            'public readonly ?Address $address = null,',
            $file->content
        );
    }

    public function testConstructorUsesPreservedSchemaMemberOrder(): void
    {
        $outputDir = \sys_get_temp_dir()
            . \DIRECTORY_SEPARATOR
            . 'smithy-php-structure-order-'
            . \bin2hex(\random_bytes(4));
        \mkdir($outputDir, 0755, true);
        $schemasPath = $outputDir . \DIRECTORY_SEPARATOR . 'Schemas.php';
        \file_put_contents($schemasPath, <<<'PHP'
<?php

final class Schemas
{
    public static function settings(): mixed
    {
        return new Schema(
            id: 'smithy.example#Settings',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'description',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.api#Integer',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'name',
                    memberIndex: 2
                )
            ]
        );
    }
}
PHP);

        try {
            $model = SmithyModel::fromFile(
                __DIR__ . '/../../fixtures/defaults-model.json'
            );
            $settings = new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: $outputDir,
                organization: 'flat'
            );
            $priorCodegen = PriorCodegenEvaluator::inspect($outputDir);
            $this->assertNotNull($priorCodegen);
            $generator = new StructureGenerator(
                $model,
                new PhpSymbolProvider($model, $settings),
                $settings,
                $priorCodegen
            );

            $content = $generator->generate(
                'smithy.example#Settings'
            )->content;
            $description = \strpos(
                $content,
                'public readonly ?string $description = null,'
            );
            $count = \strpos(
                $content,
                'public readonly int $count = 0,'
            );
            $name = \strpos(
                $content,
                'public readonly ?string $name = null,'
            );

            $this->assertIsInt($description);
            $this->assertIsInt($count);
            $this->assertIsInt($name);
            $this->assertLessThan($count, $description);
            $this->assertLessThan($name, $count);
        } finally {
            @\unlink($schemasPath);
            @\rmdir($outputDir);
        }
    }

    public function testStructureUsesCategorySubNamespace(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Address');

        $this->assertStringContainsString(
            'namespace Smithy\\Example\\Shape;',
            $file->content
        );
        $this->assertSame(
            '/tmp/out'
                . DIRECTORY_SEPARATOR
                . 'Shape'
                . DIRECTORY_SEPARATOR
                . 'Address.php',
            $file->path
        );
    }

    public function testMixinConsumingShapeResolvesMixinMembers(): void
    {
        // QueryEvent applies the Event mixin, which contributes itemId +
        // timestamp.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#QueryEvent');

        // Both mixin-provided members surface as constructor properties.
        $this->assertStringContainsString(
            'public readonly ?string $itemId = null,',
            $file->content
        );
        $this->assertStringContainsString('$timestamp = null', $file->content);
    }

    public function testGeneratedStructureExposesSchemaAndSerializeMethods(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Address');

        $this->assertStringContainsString(
            'public static function schema(): Schema',
            $file->content
        );
        $this->assertStringContainsString(
            'public function serialize(ShapeSerializerInterface $serializer, int $idx): void',
            $file->content
        );
    }

    public function testEmitsConfiguredShapeConstants(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            shapeConstants: [
                'smithy.example#Address' => [
                    'WIRE_NAME' => 'address'
                ]
            ]
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Address');

        $this->assertStringContainsString(
            "public const WIRE_NAME = 'address';",
            $file->content
        );
    }
}
