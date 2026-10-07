<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\SchemasGenerator;
use Smithy\Codegen\Generator\StructureGenerator;
use Smithy\Codegen\Generator\UnionGenerator;
use Smithy\Codegen\Model\SensitiveShapeDetector;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Core\Serde\Schema;

final class SensitiveGeneratedShapeTest extends TestCase
{
    public function testGeneratedRootSchemasRedactSensitiveValues(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#SensitiveMixin": {
            "type": "structure",
            "traits": {
                "smithy.api#mixin": {},
                "smithy.api#sensitive": {}
            }
        },
        "example#SensitiveContainer": {
            "type": "structure",
            "mixins": [{"target": "example#SensitiveMixin"}],
            "members": {
                "value": {"target": "smithy.api#String"}
            }
        },
        "example#SensitiveChoice": {
            "type": "union",
            "traits": {"smithy.api#sensitive": {}},
            "members": {
                "secret": {"target": "smithy.api#String"}
            }
        },
        "example#NonSensitiveChoice": {
            "type": "union",
            "members": {
                "visible": {"target": "smithy.api#String"}
            }
        }
    }
}
JSON);
        $outputDir = \sys_get_temp_dir()
            . '/smithy-sensitive-generated-' . \bin2hex(\random_bytes(4));
        $namespace = 'Smithy\\Tests\\Generated\\Sensitive'
            . \bin2hex(\random_bytes(4));
        $settings = new CodegenSettings(
            namespace: $namespace,
            outputDir: $outputDir,
            organization: 'flat'
        );
        $symbols = new PhpSymbolProvider($model, $settings);
        $detector = new SensitiveShapeDetector($model);
        $structureGenerator = new StructureGenerator(
            $model,
            $symbols,
            $settings,
            sensitiveShapeDetector: $detector
        );
        $unionGenerator = new UnionGenerator(
            $model,
            $symbols,
            $settings,
            $detector
        );
        $schemaGenerator = new SchemasGenerator(
            $model,
            $symbols,
            $settings
        );
        $schemaFile = $schemaGenerator->generate([
            'example#SensitiveContainer' => true,
            'example#SensitiveChoice' => true,
            'example#NonSensitiveChoice' => true
        ]);
        $structureFile = $structureGenerator->generate(
            'example#SensitiveContainer'
        );
        $unionFiles = $unionGenerator->generate(
            'example#SensitiveChoice'
        );
        $nonSensitiveUnionFiles = $unionGenerator->generate(
            'example#NonSensitiveChoice'
        );
        $files = [
            $schemaFile,
            $structureFile,
            ...$unionFiles,
            ...$nonSensitiveUnionFiles
        ];

        try {
            foreach ($files as $file) {
                $file->writeToDisk();
            }
            require $schemaFile->path;
            require $structureFile->path;
            foreach ($unionFiles as $file) {
                require $file->path;
            }
            foreach ($nonSensitiveUnionFiles as $file) {
                require $file->path;
            }

            $structureClass = $namespace . '\\SensitiveContainer';
            $structure = new $structureClass('structure-secret');
            $unionClass = $namespace . '\\SensitiveChoice';
            $union = $unionClass::secret('union-secret');

            $this->assertTrue(
                $structure->getSchema()->hasTrait(Schema::T_SENSITIVE)
            );
            $this->assertTrue(
                $union->getSchema()->hasTrait(Schema::T_SENSITIVE)
            );

            foreach ([$structure, $union] as $value) {
                $output = \print_r($value, true)
                    . $this->dumpValue($value)
                    . (string) $value;
                $this->assertStringContainsString(
                    '***REDACTED***',
                    $output
                );
                $this->assertStringNotContainsString(
                    'structure-secret',
                    $output
                );
                $this->assertStringNotContainsString(
                    'union-secret',
                    $output
                );
            }

            $nonSensitiveUnionClass = $namespace . '\\NonSensitiveChoice';
            $unknownUnion = $nonSensitiveUnionClass::unknown(
                'future',
                'unknown-secret'
            );
            $this->assertFalse(
                $unknownUnion->getSchema()->hasTrait(Schema::T_SENSITIVE)
            );
            $unknownOutput = \print_r($unknownUnion, true)
                . $this->dumpValue($unknownUnion)
                . (string) $unknownUnion;
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

    private function dumpValue(mixed $value): string
    {
        \ob_start();
        \var_dump($value);

        return (string) \ob_get_clean();
    }
}
