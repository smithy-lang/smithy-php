<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\EnumGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;

final class EnumGeneratorTest extends TestCase
{
    public function testGeneratesEnumClassForStringEnumShape(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Status');

        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'Status.php',
            $file->path
        );
        // Class declaration + interface implementation.
        $this->assertStringContainsString(
            'final class Status implements EnumInterface',
            $file->content
        );
        // The shape ID is exposed as a class constant.
        $this->assertStringContainsString(
            "public const SHAPE_ID = 'smithy.example#Status'",
            $file->content
        );
        // Each enum member gets a lowercase static factory backed by a
        // singleton cache.
        $this->assertStringContainsString(
            'public static function active(): self',
            $file->content
        );
        $this->assertStringContainsString(
            'public static function inactive(): self',
            $file->content
        );
        $this->assertStringContainsString(
            'public static function pending(): self',
            $file->content
        );
        // The forward-compatible `fromValue()` factory is emitted alongside.
        $this->assertStringContainsString(
            'public static function fromValue(string $value): self',
            $file->content
        );
        // Constructor takes the backing string type.
        $this->assertStringContainsString(
            'private function __construct(string $value)',
            $file->content
        );
        // The value-returning API is `string|int` (satisfies both enum
        // flavors).
        $this->assertStringContainsString(
            'public function getValue(): string|int',
            $file->content
        );
    }

    public function testGeneratesIntBackedEnumForIntEnumShape(): void
    {
        // Priority is an intEnum in the fixture.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Priority');

        $this->assertSame(
            '/tmp/out' . DIRECTORY_SEPARATOR . 'Priority.php',
            $file->path
        );
        // The backing type is `int` for intEnum, not `string`.
        $this->assertStringContainsString(
            'private function __construct(int $value)',
            $file->content
        );
    }

    public function testStringEnumValuesEscapeBackslashesAndQuotes(): void
    {
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#PathEnum' => [
                    'type' => 'enum',
                    'members' => [
                        'WindowsPath' => [
                            'traits' => [
                                'smithy.api#enumValue' => 'C:\\path\\'
                            ]
                        ],
                        'QuotedPath' => [
                            'traits' => [
                                'smithy.api#enumValue' => "it's\\there"
                            ]
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
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#PathEnum');

        $this->assertStringContainsString("'C:\\\\path\\\\'", $file->content);
        $this->assertStringContainsString("'it\\'s\\\\there'", $file->content);
    }

    public function testFactoryNamesAvoidGeneratedMethodCollisions(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Status' => [
                    'type' => 'enum',
                    'members' => [
                        'fromValue' => [
                            'traits' => [
                                'smithy.api#enumValue' => 'first'
                            ]
                        ],
                        'FROM_VALUE' => [
                            'traits' => [
                                'smithy.api#enumValue' => 'second'
                            ]
                        ],
                        'schema' => [
                            'traits' => [
                                'smithy.api#enumValue' => 'third'
                            ]
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
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Status');

        $this->assertStringContainsString(
            'public static function fromValueValue(): self',
            $file->content
        );
        $this->assertStringContainsString(
            'public static function fromValueValueValue(): self',
            $file->content
        );
        $this->assertStringContainsString(
            'public static function schemaValue(): self',
            $file->content
        );
        $this->assertSame(
            1,
            \substr_count($file->content, 'function fromValue(')
        );
    }

    public function testGeneratedClassUsesCategorySubNamespace(): void
    {
        // With `categorized` organization, non-error shapes land in the Shape
        // sub-namespace.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out'
        );
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Status');

        $this->assertStringContainsString(
            'namespace Smithy\\Example\\Shape;',
            $file->content
        );
        $this->assertSame(
            '/tmp/out'
                . DIRECTORY_SEPARATOR
                . 'Shape'
                . DIRECTORY_SEPARATOR
                . 'Status.php',
            $file->path
        );
    }

    public function testGenerateRaisesWhenShapeIdMissingFromModel(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Shape not found/');

        $generator->generate('smithy.example#Ghost');
    }

    public function testGenerateRaisesWhenShapeIsNotAnEnum(): void
    {
        // Human is a structure, not an enum - the generator must reject.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not an enum or intEnum/');

        $generator->generate('smithy.example#Human');
    }

    public function testGeneratedFileEmitsPreamble(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/person-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EnumGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate('smithy.example#Status');

        $this->assertStringStartsWith(
            "<?php\n\ndeclare(strict_types=1);",
            $file->content
        );
    }
}
