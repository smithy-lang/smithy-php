<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\EndpointBytecodeGenerator;
use Smithy\Codegen\Model\SmithyModel;

/**
 * The generator's happy path - a full BDD compile -> source-emitted class -
 * is exercised by provider integration tests that load a committed model,
 * regenerate its bytecode, and evaluate every scenario against it.
 * Duplicating that with an inline BDD trait here would require
 * hand-authoring a valid BDD payload - too fragile for a unit test.
 *
 * This test locks down the surface behaviors instead:
 *   - No `endpointBdd` trait -> generator returns `null` (opt-out, not an error).
 *   - Unknown shape id -> `InvalidArgumentException`.
 *   - Non-service shape -> `InvalidArgumentException`.
 *   - Malformed trait (non-array) -> treated as absence, returns `null`.
 */
final class EndpointBytecodeGeneratorTest extends TestCase
{
    public function testReturnsNullWhenServiceHasNoEndpointBddTrait(): void
    {
        // Generic service in the defaults fixture - carries no `endpointBdd`.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EndpointBytecodeGenerator($model, $settings);

        $file = $generator->generate('smithy.example#DefaultsService');

        $this->assertNull($file);
    }

    public function testReturnsNullWhenEndpointBddTraitIsMalformed(): void
    {
        // Trait present but not an array - the generator must treat this as
        // "nothing to generate" rather than raising, so a partial model
        // doesn't break the whole codegen run.
        $model = SmithyModel::fromJson(json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Svc' => [
                    'type' => 'service',
                    'version' => '2025-01-01',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => 'not-an-array'
                    ]
                ]
            ]
        ]));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EndpointBytecodeGenerator($model, $settings);

        $this->assertNull($generator->generate('smithy.example#Svc'));
    }

    public function testGenerateRaisesForUnknownServiceShapeId(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EndpointBytecodeGenerator($model, $settings);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Shape not found/');

        $generator->generate('smithy.example#Nonexistent');
    }

    public function testGenerateRaisesForNonServiceShape(): void
    {
        // The Settings shape is a structure, not a service - must be rejected.
        $model = SmithyModel::fromFile(
            __DIR__ . '/../../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );
        $generator = new EndpointBytecodeGenerator($model, $settings);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is not a service/');

        $generator->generate('smithy.example#Settings');
    }
}
