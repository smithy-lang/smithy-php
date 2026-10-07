<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\GeneratedFile;
use Smithy\Codegen\Generator\UnionGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;

final class UnionGeneratorTest extends TestCase
{
    public function testGenerateReturnsBaseClassPlusOneFilePerVariant(): void
    {
        // Payload has three variants: stringValue, intValue, event -> 1 base +
        // 3 subclasses.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new UnionGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $files = $generator->generate('smithy.example#Payload');

        $paths = array_map(static fn(GeneratedFile $f) => $f->path, $files);
        // Base class + one file per modeled variant. An `Unknown` catch-all
        // variant may also be emitted for forward-compatibility.
        $this->assertContains(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'Payload.php',
            $paths
        );
        $this->assertContains(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'PayloadStringValue.php',
            $paths
        );
        $this->assertContains(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'PayloadIntValue.php',
            $paths
        );
        $this->assertContains(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'PayloadEvent.php',
            $paths
        );
    }

    public function testBaseClassIsAbstractReadonlyImplementingUnionInterface(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new UnionGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $files = $generator->generate('smithy.example#Payload');
        $base = null;
        foreach ($files as $f) {
            if (str_ends_with(
                $f->path,
                DIRECTORY_SEPARATOR . 'Payload.php'
            )) {
                $base = $f;
                break;
            }
        }
        $this->assertNotNull($base);

        $this->assertStringContainsString(
            'abstract readonly class Payload implements UnionInterface',
            $base->content
        );
        $this->assertStringContainsString(
            "public const SHAPE_ID = 'smithy.example#Payload';",
            $base->content
        );
        // Contract methods are abstract on the base.
        $this->assertStringContainsString(
            'abstract public function tag(): string;',
            $base->content
        );
        $this->assertStringContainsString(
            'abstract public function value(): mixed;',
            $base->content
        );
    }

    public function testVariantSubclassExtendsBaseAndImplementsTagAndValue(): void
    {
        // The stringValue variant wraps a PHP string.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new UnionGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $files = $generator->generate('smithy.example#Payload');
        $variant = null;
        foreach ($files as $f) {
            if (str_ends_with(
                $f->path,
                DIRECTORY_SEPARATOR . 'PayloadStringValue.php'
            )) {
                $variant = $f;
                break;
            }
        }
        $this->assertNotNull($variant);

        $this->assertStringContainsString(
            'final readonly class PayloadStringValue extends Payload',
            $variant->content
        );
        $this->assertStringContainsString(
            'public function __construct(public string $value)',
            $variant->content
        );
        $this->assertStringContainsString(
            "return 'stringValue';",
            $variant->content
        );
        $this->assertStringContainsString(
            'public function value(): string',
            $variant->content
        );
    }

    public function testGeneratedFilesUseCategorySubNamespace(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new UnionGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $files = $generator->generate('smithy.example#Payload');

        foreach ($files as $f) {
            $this->assertStringContainsString(
                'namespace Smithy\\Example\\Shape;',
                $f->content
            );
            $this->assertStringContainsString(
                DIRECTORY_SEPARATOR . 'Shape' . DIRECTORY_SEPARATOR,
                $f->path
            );
        }
    }

    public function testFactoryAndVariantNamesAvoidGeneratedCollisions(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Choice' => [
                    'type' => 'union',
                    'members' => [
                        'schema' => [
                            'target' => 'smithy.api#String'
                        ],
                        'unknown' => [
                            'target' => 'smithy.api#String'
                        ]
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new UnionGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $files = $generator->generate('smithy.example#Choice');
        $base = $files[0]->content;
        $paths = \array_map(
            static fn(GeneratedFile $file): string => $file->path,
            $files
        );

        $this->assertStringContainsString(
            'public static function schemaValue(string $value): self',
            $base
        );
        $this->assertStringContainsString(
            'public static function unknownValue(string $value): self',
            $base
        );
        $this->assertContains(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'ChoiceSchemaValue.php',
            $paths
        );
        $this->assertContains(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'ChoiceUnknownValue.php',
            $paths
        );
        $this->assertSame(\count($paths), \count(\array_unique($paths)));
    }

    public function testSensitiveUnionUsesRedactionTrait(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#Secret": {
            "type": "string",
            "traits": {"smithy.api#sensitive": {}}
        },
        "smithy.example#Choice": {
            "type": "union",
            "members": {
                "secret": {"target": "smithy.example#Secret"},
                "visible": {"target": "smithy.api#String"}
            }
        }
    }
}
JSON);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new UnionGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate(
            'smithy.example#Choice'
        )[0]->content;

        $this->assertStringContainsString(
            'use Smithy\\Core\\Shape\\SensitiveStringableTrait;',
            $content
        );
        $this->assertStringContainsString(
            'use SensitiveStringableTrait;',
            $content
        );
    }
}
