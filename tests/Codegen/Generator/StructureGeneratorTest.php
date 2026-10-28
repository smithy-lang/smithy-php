<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\GeneratedFile;
use Smithy\Codegen\Generator\SchemasGenerator;
use Smithy\Codegen\Generator\StructureGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Codegen\PriorCodegenEvaluator;
use Smithy\Core\Serde\Schema;

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

    public function testEffectiveDefaultsUseNullableConstructorSentinels(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/effective-defaults-model.json'
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

        $content = $generator->generate(
            'smithy.example#EffectiveDefaults'
        )->content;

        $this->assertStringContainsString(
            'public readonly string $defaultString;',
            $content
        );
        $this->assertStringContainsString(
            '?string $defaultString = null,',
            $content
        );
        $this->assertStringContainsString(
            "\$this->defaultString = \$defaultString ?? 'hi';",
            $content
        );
        $this->assertStringContainsString(
            '$this->defaultFloat = $defaultFloat ?? 1.0;',
            $content
        );
        $this->assertStringContainsString(
            '$this->defaultTimestamp = $defaultTimestamp ?? 0.0;',
            $content
        );
        $this->assertStringContainsString(
            '$this->defaultBlob = $defaultBlob ?? "\\x61\\x62\\x63";',
            $content
        );
        $this->assertStringContainsString(
            "\$this->defaultBigInteger = \$defaultBigInteger "
                . "?? new BigInteger('123');",
            $content
        );
        $this->assertStringContainsString(
            "\$this->defaultBigDecimal = \$defaultBigDecimal "
                . "?? new BigDecimal('1.25');",
            $content
        );
        $this->assertStringContainsString(
            'Status|string|null $defaultEnum = null,',
            $content
        );
        $this->assertStringContainsString(
            'Status::fromValue($defaultEnum ?? \'ACTIVE\');',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?int $clientOptional = null',
            $content
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
                'public readonly ?int $count = null,'
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

    public function testFullyQualifiesClassesInCollectionPhpDoc(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#ValidationException": {
            "type": "structure",
            "members": {
                "fieldList": {
                    "target": "smithy.example#ValidationExceptionFieldList"
                }
            },
            "traits": {"smithy.api#error": "client"}
        },
        "smithy.example#ValidationExceptionFieldList": {
            "type": "list",
            "member": {
                "target": "smithy.example#ValidationExceptionField"
            }
        },
        "smithy.example#ValidationExceptionField": {
            "type": "structure"
        }
    }
}
JSON);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate(
            'smithy.example#ValidationException'
        )->content;

        $this->assertStringNotContainsString(
            'use Smithy\\Example\\Shape\\ValidationExceptionField;',
            $content
        );
        $this->assertStringContainsString(
            '@param list<\\Smithy\\Example\\Shape\\'
                . 'ValidationExceptionField>|null $fieldList',
            $content
        );
    }

    public function testPhpDocTypeDoesNotCollideWithGeneratedClass(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#BigInteger": {
            "type": "structure",
            "members": {
                "values": {"target": "smithy.example#BigIntegerList"}
            }
        },
        "smithy.example#BigIntegerList": {
            "type": "list",
            "member": {"target": "smithy.api#BigInteger"}
        }
    }
}
JSON);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate(
            'smithy.example#BigInteger'
        )->content;

        $this->assertStringNotContainsString(
            'use Smithy\\Core\\Shape\\BigInteger;',
            $content
        );
        $this->assertStringContainsString(
            '@param list<\\Smithy\\Core\\Shape\\BigInteger>|null $values',
            $content
        );
    }

    public function testPhpDocTypeDoesNotCollideWithRuntimeImport(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#GetNumbers": {
            "type": "operation",
            "input": {"target": "smithy.example#GetNumbersInput"}
        },
        "smithy.example#GetNumbersInput": {
            "type": "structure",
            "members": {
                "number": {"target": "smithy.api#BigInteger"},
                "modeled": {"target": "smithy.example#ModeledNumbers"}
            }
        },
        "smithy.example#ModeledNumbers": {
            "type": "list",
            "member": {"target": "smithy.example#BigInteger"}
        },
        "smithy.example#BigInteger": {"type": "structure"}
    }
}
JSON);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate(
            'smithy.example#GetNumbersInput'
        )->content;

        $this->assertSame(
            1,
            \substr_count(
                $content,
                'use Smithy\\Core\\Shape\\BigInteger;'
            )
        );
        $this->assertStringNotContainsString(
            'use Smithy\\Example\\Shape\\BigInteger;',
            $content
        );
        $this->assertStringContainsString(
            '@param list<\\Smithy\\Example\\Shape\\BigInteger>|null '
                . '$modeled',
            $content
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

    public function testEmitsSensitiveTraitForTransitiveCollection(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#Secret": {
            "type": "string",
            "traits": {"smithy.api#sensitive": {}}
        },
        "smithy.example#Secrets": {
            "type": "list",
            "member": {"target": "smithy.example#Secret"}
        },
        "smithy.example#JsonSerializable": {
            "type": "structure",
            "members": {
                "secrets": {"target": "smithy.example#Secrets"}
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
        $generator = new StructureGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $content = $generator->generate(
            'smithy.example#JsonSerializable'
        )->content;

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

    public function testGeneratedSensitiveStructureRedactsSerialization(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#SensitiveContainer": {
            "type": "structure",
            "traits": {"smithy.api#sensitive": {}},
            "members": {
                "value": {"target": "smithy.api#String"}
            }
        }
    }
}
JSON);
        $outputDir = \sys_get_temp_dir()
            . '/smithy-sensitive-structure-'
            . \bin2hex(\random_bytes(4));
        $namespace = 'Smithy\\Tests\\Generated\\SensitiveStructure'
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
        ))->generate(['example#SensitiveContainer' => true]);
        $structureFile = (new StructureGenerator(
            $model,
            $symbols,
            $settings
        ))->generate('example#SensitiveContainer');
        $files = [$schemaFile, $structureFile];

        try {
            foreach ($files as $file) {
                $file->writeToDisk();
            }
            require $schemaFile->path;
            require $structureFile->path;

            $class = $namespace . '\\SensitiveContainer';
            $value = new $class('structure-secret');

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
            $this->assertStringNotContainsString(
                'structure-secret',
                $output
            );
        } finally {
            foreach ($files as $file) {
                @\unlink($file->path);
            }
            @\rmdir($outputDir);
        }
    }

    public function testSensitiveStructureReservesModeledJsonSerializableType(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#Operation": {
            "type": "operation",
            "input": {"target": "example#SensitiveContainer"}
        },
        "example#SensitiveContainer": {
            "type": "structure",
            "traits": {"smithy.api#sensitive": {}},
            "members": {
                "value": {"target": "example#JsonSerializable"}
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
                . '/smithy-sensitive-structure-import-'
                . \bin2hex(\random_bytes(4));
            $namespace = 'Smithy\\Tests\\Generated\\SensitiveStructureImport'
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
                'example#SensitiveContainer' => true,
                'example#JsonSerializable' => true
            ]);
            $memberFile = (new StructureGenerator(
                $model,
                $symbols,
                $settings
            ))->generate('example#JsonSerializable');
            $containerFile = (new StructureGenerator(
                $model,
                $symbols,
                $settings
            ))->generate('example#SensitiveContainer');
            $files = [$schemaFile, $memberFile, $containerFile];

            try {
                foreach ($files as $file) {
                    $file->writeToDisk();
                }
                require $schemaFile->path;
                require $memberFile->path;
                require $containerFile->path;

                $memberClass = $symbols->toClassName(
                    'example#JsonSerializable'
                );
                $containerClass = $symbols->toClassName(
                    'example#SensitiveContainer'
                );
                $member = new $memberClass();
                $value = new $containerClass($member);

                $this->assertSame($member, $value->value);
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

    public function testSensitiveErrorReservesCustomJsonSerializableParent(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#SensitiveFailure": {
            "type": "structure",
            "traits": {
                "smithy.api#error": "client",
                "smithy.api#sensitive": {}
            },
            "members": {
                "message": {"target": "smithy.api#String"}
            }
        }
    }
}
JSON);
        $outputDir = \sys_get_temp_dir()
            . '/smithy-sensitive-error-parent-'
            . \bin2hex(\random_bytes(4));
        $namespace = 'Smithy\\Tests\\Generated\\SensitiveError'
            . \bin2hex(\random_bytes(4));
        $parentNamespace = $namespace . '\\Parent';
        $parentClass = $parentNamespace . '\\JsonSerializable';
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: $outputDir,
            baseServiceException: $parentClass
        );
        $symbols = new PhpSymbolProvider($model, $settings);
        $parentFile = new GeneratedFile(
            path: $outputDir . '/JsonSerializable.php',
            content: \sprintf(<<<'PHP'
<?php

declare(strict_types=1);

namespace %s;

use Smithy\Core\Exception\ApiException;

class JsonSerializable extends ApiException {}
PHP, $parentNamespace)
        );
        $errorFile = (new StructureGenerator(
            $model,
            $symbols,
            $settings
        ))->generate('example#SensitiveFailure');
        $files = [$parentFile, $errorFile];

        try {
            foreach ($files as $file) {
                $file->writeToDisk();
            }
            require $parentFile->path;
            require $errorFile->path;

            $errorClass = $symbols->toClassName(
                'example#SensitiveFailure'
            );
            $error = new $errorClass('secret');

            $this->assertInstanceOf($parentClass, $error);
            $this->assertInstanceOf(\JsonSerializable::class, $error);
        } finally {
            foreach ($files as $file) {
                @\unlink($file->path);
            }
            @\rmdir(\dirname($errorFile->path));
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
