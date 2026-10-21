<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\GeneratedFile;
use Smithy\Codegen\Generator\SchemasGenerator;
use Smithy\Codegen\Generator\StructureGenerator;
use Smithy\Codegen\Generator\UnionGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Core\Serde\Schema;

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
        "smithy.example#JsonSerializable": {
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
            'smithy.example#JsonSerializable'
        )[0]->content;

        $this->assertStringContainsString(
            'use JsonSerializable as PhpJsonSerializable;',
            $content
        );
        $this->assertStringContainsString(
            'use Smithy\\Core\\Shape\\SensitiveStringableTrait;',
            $content
        );
        $this->assertStringContainsString(
            'use SensitiveStringableTrait;',
            $content
        );
        $this->assertStringContainsString(
            'PhpJsonSerializable',
            $content
        );
    }

    public function testGeneratedUnionRedactsSerialization(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#SensitiveChoice": {
            "type": "union",
            "traits": {"smithy.api#sensitive": {}},
            "members": {
                "jsonSerialize": {"target": "smithy.api#String"}
            }
        },
        "example#NonSensitiveChoice": {
            "type": "union",
            "members": {
                "jsonSerialize": {"target": "smithy.api#String"}
            }
        }
    }
}
JSON);
        $outputDir = \sys_get_temp_dir()
            . '/smithy-sensitive-union-'
            . \bin2hex(\random_bytes(4));
        $namespace = 'Smithy\\Tests\\Generated\\SensitiveUnion'
            . \bin2hex(\random_bytes(4));
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: $outputDir,
            organization: 'flat'
        );
        $symbols = new PhpSymbolProvider($model, $settings);
        $schemaFile = (new SchemasGenerator(
            $model,
            $symbols,
            $settings
        ))->generate([
            'example#SensitiveChoice' => true,
            'example#NonSensitiveChoice' => true
        ]);
        $unionFiles = (new UnionGenerator(
            $model,
            $symbols,
            $settings
        ))->generate('example#SensitiveChoice');
        $nonSensitiveUnionFiles = (new UnionGenerator(
            $model,
            $symbols,
            $settings
        ))->generate('example#NonSensitiveChoice');
        $files = [
            $schemaFile,
            ...$unionFiles,
            ...$nonSensitiveUnionFiles
        ];

        try {
            $this->assertStringContainsString(
                'public static function jsonSerializeValue(',
                $unionFiles[0]->content
            );
            $this->assertStringContainsString(
                'public static function jsonSerializeValue(',
                $nonSensitiveUnionFiles[0]->content
            );
            foreach ($files as $file) {
                $file->writeToDisk();
            }
            require $schemaFile->path;
            foreach ([...$unionFiles, ...$nonSensitiveUnionFiles] as $file) {
                require $file->path;
            }

            $class = $namespace . '\\SensitiveChoice';
            $value = $class::jsonSerializeValue('union-secret');
            $this->assertTrue(
                $value->getSchema()->hasTrait(Schema::T_SENSITIVE)
            );
            $this->assertInstanceOf(\JsonSerializable::class, $value);

            $output = \print_r($value, true)
                . $this->dumpValue($value)
                . (string) $value
                . \json_encode($value, JSON_THROW_ON_ERROR)
                . \serialize($value);
            $this->assertStringContainsString('***REDACTED***', $output);
            $this->assertStringNotContainsString('union-secret', $output);

            $unknownClass = $namespace . '\\NonSensitiveChoice';
            $unknown = $unknownClass::unknown('future', 'unknown-secret');
            $this->assertFalse(
                $unknown->getSchema()->hasTrait(Schema::T_SENSITIVE)
            );
            $this->assertInstanceOf(\JsonSerializable::class, $unknown);

            $unknownOutput = \print_r($unknown, true)
                . $this->dumpValue($unknown)
                . (string) $unknown
                . \json_encode($unknown, JSON_THROW_ON_ERROR)
                . \serialize($unknown);
            $this->assertStringContainsString(
                '***REDACTED***',
                $unknownOutput
            );
            $this->assertStringNotContainsString(
                'unknown-secret',
                $unknownOutput
            );
        } finally {
            foreach ($files as $file) {
                @\unlink($file->path);
            }
            @\rmdir($outputDir);
        }
    }

    public function testSensitiveUnionReservesModeledJsonSerializableType(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#Operation": {
            "type": "operation",
            "input": {"target": "example#SensitiveChoice"}
        },
        "example#SensitiveChoice": {
            "type": "union",
            "traits": {"smithy.api#sensitive": {}},
            "members": {
                "payload": {"target": "example#JsonSerializable"}
            }
        },
        "example#JsonSerializable": {
            "type": "structure",
            "members": {}
        }
    }
}
JSON);

        foreach (['flat', 'categorized'] as $organization) {
            $outputDir = \sys_get_temp_dir()
                . '/smithy-sensitive-union-import-'
                . \bin2hex(\random_bytes(4));
            $namespace = 'Smithy\\Tests\\Generated\\SensitiveUnionImport'
                . \bin2hex(\random_bytes(4));
            $settings = new CodegenSettings(
                namespace: $namespace,
                outputDir: $outputDir,
                organization: $organization
            );
            $symbols = new PhpSymbolProvider($model, $settings);
            $schemaFile = (new SchemasGenerator(
                $model,
                $symbols,
                $settings
            ))->generate([
                'example#SensitiveChoice' => true,
                'example#JsonSerializable' => true
            ]);
            $structureFile = (new StructureGenerator(
                $model,
                $symbols,
                $settings
            ))->generate('example#JsonSerializable');
            $unionFiles = (new UnionGenerator(
                $model,
                $symbols,
                $settings
            ))->generate('example#SensitiveChoice');
            $files = [$schemaFile, $structureFile, ...$unionFiles];

            try {
                foreach ($files as $file) {
                    $file->writeToDisk();
                }
                require $schemaFile->path;
                require $structureFile->path;
                foreach ($unionFiles as $file) {
                    require $file->path;
                }

                $memberClass = $symbols->toClassName(
                    'example#JsonSerializable'
                );
                $unionClass = $symbols->toClassName(
                    'example#SensitiveChoice'
                );
                $member = new $memberClass();
                $value = $unionClass::payload($member);

                $this->assertSame($member, $value->value());
                $this->assertInstanceOf(\JsonSerializable::class, $value);
            } finally {
                foreach ($files as $file) {
                    @\unlink($file->path);
                }
                $directories = \array_unique(\array_map(
                    static fn(GeneratedFile $file): string =>
                        \dirname($file->path),
                    $files
                ));
                \usort(
                    $directories,
                    static fn(string $left, string $right): int =>
                        \strlen($right) <=> \strlen($left)
                );
                foreach ($directories as $directory) {
                    @\rmdir($directory);
                }
                @\rmdir($outputDir);
            }
        }
    }

    public function testSensitiveUnionReservesVariantClassNames(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#Json": {
            "type": "union",
            "traits": {"smithy.api#sensitive": {}},
            "members": {
                "serializable": {"target": "smithy.api#String"}
            }
        }
    }
}
JSON);
        $outputDir = \sys_get_temp_dir()
            . '/smithy-sensitive-union-variant-'
            . \bin2hex(\random_bytes(4));
        $namespace = 'Smithy\\Tests\\Generated\\SensitiveUnionVariant'
            . \bin2hex(\random_bytes(4));
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: $outputDir,
            organization: 'flat'
        );
        $symbols = new PhpSymbolProvider($model, $settings);
        $files = (new UnionGenerator(
            $model,
            $symbols,
            $settings
        ))->generate('example#Json');

        try {
            foreach ($files as $file) {
                $file->writeToDisk();
                require $file->path;
            }

            $class = $symbols->toClassName('example#Json');
            $value = $class::serializable('secret');

            $this->assertSame('secret', $value->value());
            $this->assertInstanceOf(\JsonSerializable::class, $value);
        } finally {
            foreach ($files as $file) {
                @\unlink($file->path);
            }
            @\rmdir($outputDir);
        }
    }

    private function dumpValue(mixed $value): string
    {
        \ob_start();
        \var_dump($value);

        return (string) \ob_get_clean();
    }
}
