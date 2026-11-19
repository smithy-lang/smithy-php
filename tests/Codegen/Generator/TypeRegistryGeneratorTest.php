<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\TypeRegistryGenerator;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;
use Smithy\Codegen\PhpSymbolProviderInterface;
use Smithy\Codegen\PhpType;

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
        $this->assertStringContainsString(
            'implements \\Smithy\\Core\\Serde\\TypeRegistryInterface',
            $file->content
        );
        $this->assertStringContainsString(
            'use \\Smithy\\Core\\Serde\\TypeRegistryDeserializeTrait;',
            $file->content
        );
        // Preamble is present.
        $this->assertStringStartsWith(
            "<?php\n\n"
            . "/*\n"
            . " * Copyright Amazon.com, Inc. or its affiliates."
            . " All Rights Reserved.\n"
            . " * SPDX-License-Identifier: Apache-2.0\n"
            . " */\n\n"
            . "declare(strict_types=1);",
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

    public function testExcludesMixinShapes(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#ExampleService": {
            "type": "service",
            "version": "1.0"
        },
        "smithy.example#GeneratedShape": {
            "type": "structure",
            "members": {}
        },
        "smithy.example#ShapeMixin": {
            "type": "structure",
            "members": {},
            "traits": {
                "smithy.api#mixin": {}
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
        $generator = new TypeRegistryGenerator(
            $model,
            new PhpSymbolProvider($model, $settings),
            $settings
        );

        $file = $generator->generate(
            'smithy.example#ExampleService',
            [
                'smithy.example#GeneratedShape' => true,
                'smithy.example#ShapeMixin' => true
            ]
        );

        self::assertStringContainsString(
            "'smithy.example#GeneratedShape'",
            $file->content
        );
        self::assertStringNotContainsString(
            'smithy.example#ShapeMixin',
            $file->content
        );
    }

    public function testNormalizesLeadingNamespaceSeparator(): void
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
            new LeadingSeparatorSymbolProvider(
                new PhpSymbolProvider($model, $settings)
            ),
            $settings
        );

        $file = $generator->generate(
            'smithy.example#DefaultsService',
            ['smithy.example#Settings' => true]
        );

        self::assertStringContainsString(
            "'Smithy\\\\Example\\\\Settings' => "
                . "'smithy.example#Settings'",
            $file->content
        );
        self::assertStringNotContainsString(
            "'\\\\Smithy\\\\Example\\\\Settings'",
            $file->content
        );
    }
}

final readonly class LeadingSeparatorSymbolProvider implements
    PhpSymbolProviderInterface
{
    public function __construct(
        private PhpSymbolProviderInterface $delegate
    ) {}

    public function toPhpType(string $shapeId): PhpType
    {
        return $this->delegate->toPhpType($shapeId);
    }

    public function toMemberType(
        string $parentShapeId,
        string $memberName
    ): PhpType {
        return $this->delegate->toMemberType($parentShapeId, $memberName);
    }

    public function toPropertyName(string $memberName): string
    {
        return $this->delegate->toPropertyName($memberName);
    }

    public function toClassName(string $shapeId): string
    {
        return '\\' . $this->delegate->toClassName($shapeId);
    }

    public function toShortClassName(string $shapeId): string
    {
        return $this->delegate->toShortClassName($shapeId);
    }
}
