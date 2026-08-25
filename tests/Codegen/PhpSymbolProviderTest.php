<?php

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

    public function testAllHardReservedNamesAreRewritten(): void
    {
        $names = [
            'Object', 'Resource', 'Mixed', 'Iterable', 'Null',
            'True', 'False', 'Numeric', 'Void', 'Never',
            'Self', 'Parent', 'Static', 'Int', 'Float', 'Bool',
            'String', 'Enum', 'Readonly', 'List', 'Match'
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
