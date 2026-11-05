<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\PhpSymbolProvider;

/**
 * Focused unit tests for {@see PhpSymbolProvider} - specifically the
 * reserved-word rewriting rule.
 *
 * These tests build a tiny in-memory Smithy AST model (via
 * {@see SmithyModel::fromJson}) and assert that shape names which
 * would otherwise collide with PHP reserved identifiers get
 * prefixed with the service short name.
 */
final class PhpSymbolProviderTest extends TestCase
{
    public function testReservedShapeNameGetsServicePrefix(): void
    {
        $provider = $this->buildProvider(['Object']);
        self::assertSame(
            'S3Object',
            $provider->toShortClassName('com.amazonaws.s3#Object')
        );
        self::assertSame(
            'Aws\\S3\\Shape\\S3Object',
            $provider->toClassName('com.amazonaws.s3#Object')
        );
    }

    public function testNonReservedShapeNameIsUnchanged(): void
    {
        $provider = $this->buildProvider(['Object', 'Bucket']);
        self::assertSame(
            'Bucket',
            $provider->toShortClassName('com.amazonaws.s3#Bucket')
        );
    }

    public function testAllReservedClassNamesAreRewritten(): void
    {
        $names = [
            '__halt_compiler',
            'abstract', 'and', 'array', 'as',
            'break',
            'callable', 'case', 'catch', 'class', 'clone', 'const',
            'continue',
            'declare', 'default', 'die', 'do',
            'echo', 'else', 'elseif', 'empty',
            'enddeclare', 'endfor', 'endforeach', 'endif',
            'endswitch', 'endwhile', 'eval', 'exit', 'extends',
            'final', 'finally', 'fn', 'for', 'foreach', 'function',
            'global', 'goto',
            'if', 'implements', 'include', 'include_once',
            'instanceof', 'insteadof', 'interface', 'isset',
            'list',
            'match',
            'namespace', 'new',
            'or',
            'print', 'private', 'protected', 'public',
            'readonly', 'require', 'require_once', 'return',
            'static', 'switch',
            'throw', 'trait', 'try',
            'unset', 'use',
            'var',
            'while',
            'xor',
            'yield',
            '__class__', '__dir__', '__file__', '__function__', '__line__',
            '__method__', '__namespace__', '__property__', '__trait__',
            'parent', 'self',
            'int', 'float', 'bool', 'string', 'true', 'false', 'null',
            'void', 'iterable', 'object', 'mixed', 'never',
            'enum', 'resource', 'numeric'
        ];
        $provider = $this->buildProvider($names);
        foreach ($names as $name) {
            self::assertSame(
                'S3' . $name,
                $provider->toShortClassName('com.amazonaws.s3#' . $name),
                "Reserved name '{$name}' should be prefixed"
            );
        }
    }

    public function testReservedNameMatchingIsCaseInsensitive(): void
    {
        $provider = $this->buildProvider(['cLaSs']);

        self::assertSame(
            'S3cLaSs',
            $provider->toShortClassName('com.amazonaws.s3#cLaSs')
        );
    }

    public function testErrorShapeGetsExceptionSuffix(): void
    {
        // Error shapes must render with an `Exception` suffix on the PHP
        // class name - every downstream consumer relies on this being
        // handled centrally in `toShortClassName()`. Without it the
        // operation-level error class list, structure `extends`, and
        // schema registry each spelled the name differently and blew up
        // at first-fault dispatch (see BucketAlreadyExists regression).
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'com.amazonaws.s3#BucketAlreadyExists' => [
                    'type' => 'structure',
                    'traits' => ['smithy.api#error' => 'client']
                ]
            ]
        ]));
        $provider = new PhpSymbolProvider(
            $model,
            new CodegenSettings(namespace: 'Aws\\S3', outputDir: '/tmp/out')
        );

        self::assertSame(
            'BucketAlreadyExistsException',
            $provider->toShortClassName('com.amazonaws.s3#BucketAlreadyExists')
        );
        self::assertSame(
            'Aws\\S3\\Exception\\BucketAlreadyExistsException',
            $provider->toClassName('com.amazonaws.s3#BucketAlreadyExists')
        );
    }

    public function testErrorSuffixIsIdempotent(): void
    {
        // An error shape that already ends in `Exception` (e.g. a hand-
        // authored `AwsException`) must not double-suffix into
        // `AwsExceptionException`. The rule lives on `toShortClassName`
        // itself so any downstream `str_ends_with` guard the callers
        // used to carry can be safely removed.
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'com.amazonaws.s3#AlreadySuffixedException' => [
                    'type' => 'structure',
                    'traits' => ['smithy.api#error' => 'client']
                ]
            ]
        ]));
        $provider = new PhpSymbolProvider(
            $model,
            new CodegenSettings(namespace: 'Aws\\S3', outputDir: '/tmp/out')
        );
        self::assertSame(
            'AlreadySuffixedException',
            $provider->toShortClassName(
                'com.amazonaws.s3#AlreadySuffixedException'
            )
        );
    }

    public function testNonErrorStructureDoesNotGetExceptionSuffix(): void
    {
        // Regression guard: only the `error`-trait carriers pick up the
        // suffix. Any plain structure that happens to be named `Error`
        // (or `Exception`) stays as-is.
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'com.amazonaws.s3#PlainStruct' => ['type' => 'structure']
            ]
        ]));
        $provider = new PhpSymbolProvider(
            $model,
            new CodegenSettings(namespace: 'Aws\\S3', outputDir: '/tmp/out')
        );
        self::assertSame(
            'PlainStruct',
            $provider->toShortClassName('com.amazonaws.s3#PlainStruct')
        );
    }

    public function testReservedNamePreEmptsCollisionResolution(): void
    {
        // Two shapes both bare-named `Object` in different namespaces -
        // both should end up prefixed with the service short name and
        // the second one should acquire the standard collision suffix.
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'com.amazonaws.s3#Object' => ['type' => 'structure'],
                'com.amazonaws.s3.sub#Object' => ['type' => 'structure']
            ]
        ]));
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out'
        );
        $provider = new PhpSymbolProvider($model, $settings);

        // First (alphabetical) shape keeps `S3Object`, the second gets
        // the namespace-derived prefix on top.
        self::assertSame(
            'S3Object',
            $provider->toShortClassName('com.amazonaws.s3#Object')
        );
        self::assertSame(
            'SubS3Object',
            $provider->toShortClassName('com.amazonaws.s3.sub#Object')
        );
    }

    public function testReservedRewriteParticipatesInCollisionResolution(): void
    {
        $provider = $this->buildProvider(['Class', 'S3Class']);

        self::assertSame(
            'S3Class',
            $provider->toShortClassName('com.amazonaws.s3#Class')
        );
        self::assertSame(
            'S3S3Class',
            $provider->toShortClassName('com.amazonaws.s3#S3Class')
        );
    }

    public function testSparseCollectionDocTypesAllowNullValues(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#SparseList": {
            "type": "list",
            "member": {"target": "smithy.api#String"},
            "traits": {"smithy.api#sparse": {}}
        },
        "smithy.example#SparseMap": {
            "type": "map",
            "key": {"target": "smithy.api#String"},
            "value": {"target": "smithy.api#String"},
            "traits": {"smithy.api#sparse": {}}
        },
        "smithy.example#DenseList": {
            "type": "list",
            "member": {"target": "smithy.api#String"}
        },
        "smithy.example#DenseMap": {
            "type": "map",
            "key": {"target": "smithy.api#String"},
            "value": {"target": "smithy.api#String"}
        }
    }
}
JSON);
        $provider = new PhpSymbolProvider(
            $model,
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        self::assertSame(
            'list<string|null>',
            $provider->toPhpType('smithy.example#SparseList')->docType
        );
        self::assertSame(
            'list<string|null>|null',
            $provider->toPhpType(
                'smithy.example#SparseList'
            )->nullableDocType()
        );
        self::assertSame(
            'array<string, string|null>',
            $provider->toPhpType('smithy.example#SparseMap')->docType
        );
        self::assertSame(
            'array<string, string|null>|null',
            $provider->toPhpType(
                'smithy.example#SparseMap'
            )->nullableDocType()
        );
        self::assertSame(
            'list<string>',
            $provider->toPhpType('smithy.example#DenseList')->docType
        );
        self::assertSame(
            'array<string, string>',
            $provider->toPhpType('smithy.example#DenseMap')->docType
        );
    }

    public function testCollectionDocTypesFullyQualifyNestedClasses(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "smithy.example#Child": {"type": "structure"},
        "smithy.example#Children": {
            "type": "list",
            "member": {"target": "smithy.example#Child"}
        },
        "smithy.example#ChildrenByName": {
            "type": "map",
            "key": {"target": "smithy.api#String"},
            "value": {"target": "smithy.example#Children"}
        }
    }
}
JSON);
        $provider = new PhpSymbolProvider(
            $model,
            new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/out'
            )
        );

        $type = $provider->toPhpType('smithy.example#ChildrenByName');
        self::assertSame(
            'array<string, list<\\Smithy\\Example\\Shape\\Child>>',
            $type->docType
        );
    }

    /**
     * Build a provider whose model contains one empty structure per
     * given short name, all in the `com.amazonaws.s3` namespace,
     * configured for the `Aws\S3` output namespace.
     *
     * @param list<string> $names
     */
    private function buildProvider(array $names): PhpSymbolProvider
    {
        $shapes = [];
        foreach ($names as $name) {
            $shapes['com.amazonaws.s3#' . $name] = ['type' => 'structure'];
        }
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => $shapes
        ]));
        $settings = new CodegenSettings(
            namespace: 'Aws\\S3',
            outputDir: '/tmp/out'
        );
        return new PhpSymbolProvider($model, $settings);
    }
}
