<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\PriorCodegenEvaluator;

/**
 * Coverage for the token scanner that recovers each shape's historical
 * member order from a previously-generated `Schemas.php`.
 *
 * These tests use *synthetic* Schemas.php fixtures written into a
 * per-test temp directory so we're never dependent on the real
 * generator's output shape at the time the test runs. The scanner is
 * paired tightly with what {@see \Smithy\Codegen\Generator\SchemasGenerator}
 * emits, but this test suite documents the exact grammar the scanner
 * commits to - any generator-side format change that would break the
 * scanner should also break these tests.
 */
final class PriorCodegenEvaluatorTest extends TestCase
{
    /** @var list<string> */
    private array $cleanupPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupPaths as $path) {
            if (\is_file($path)) {
                @\unlink($path);
            }
            if (\is_dir($path)) {
                @\rmdir($path);
            }
        }
        $this->cleanupPaths = [];
        parent::tearDown();
    }

    public function testReturnsNullWhenSchemasFileMissing(): void
    {
        $dir = $this->makeTempDir();

        $this->assertNull(
            PriorCodegenEvaluator::inspect($dir),
            'A prior generation that never ran should surface as null so '
            . 'codegen proceeds with fresh member ordering.'
        );
    }

    public function testExtractsMembersInMemberIndexOrder(): void
    {
        // Deliberately emit members OUT of memberIndex order to prove the
        // scanner keys off `memberIndex:` rather than source-file order.
        $source = <<<'PHP'
<?php
declare(strict_types=1);
namespace Aws\Foo;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;
final class Schemas
{
    public static Schema $Bucket;
    public static function bucket(): Schema
    {
        return self::$Bucket = new Schema(
            id: 'com.amazonaws.s3#Bucket',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'x',
                    type: ShapeType::String_,
                    memberName: 'CreationDate',
                    memberIndex: 1
                ),
                new Schema(
                    id: 'x',
                    type: ShapeType::String_,
                    memberName: 'Name',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'x',
                    type: ShapeType::String_,
                    memberName: 'BucketRegion',
                    memberIndex: 2
                )
            ]
        );
    }
}
PHP;
        $dir = $this->writeSchemasFixture($source);

        $evaluator = PriorCodegenEvaluator::inspect($dir);
        $this->assertNotNull($evaluator);
        $this->assertTrue($evaluator->hasPriorOrdering());
        $this->assertSame(
            ['Name', 'CreationDate', 'BucketRegion'],
            $evaluator->memberOrderFor('com.amazonaws.s3#Bucket'),
            'Members must be returned in memberIndex order regardless '
            . 'of their textual order in the source file.'
        );
    }

    public function testRecordsMemberlessShapes(): void
    {
        // Empty-members shapes still need to be registered so the
        // caller can tell "this shape existed before" from "this
        // shape is brand new."
        $source = <<<'PHP'
<?php
namespace Aws\Foo;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;
final class Schemas
{
    public static Schema $Empty;
    public static function empty(): Schema
    {
        return self::$Empty = new Schema(
            id: 'com.amazonaws.s3#NoMembers',
            type: ShapeType::Structure,
            members: []
        );
    }
}
PHP;
        $dir = $this->writeSchemasFixture($source);

        $evaluator = PriorCodegenEvaluator::inspect($dir);
        $this->assertNotNull($evaluator);
        $this->assertSame(
            [],
            $evaluator->memberOrderFor('com.amazonaws.s3#NoMembers')
        );
    }

    public function testReturnsNullForShapeAbsentFromPriorGeneration(): void
    {
        // Prior generation only knew about `Bucket`. Any other shape
        // (a brand-new addition in the current model) should surface as
        // "no historical order" so the generator uses fresh partition
        // for it.
        $source = <<<'PHP'
<?php
namespace Aws\Foo;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;
final class Schemas
{
    public static Schema $Bucket;
    public static function bucket(): Schema
    {
        return self::$Bucket = new Schema(
            id: 'com.amazonaws.s3#Bucket',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'x',
                    type: ShapeType::String_,
                    memberName: 'Name',
                    memberIndex: 0
                )
            ]
        );
    }
}
PHP;
        $dir = $this->writeSchemasFixture($source);

        $evaluator = PriorCodegenEvaluator::inspect($dir);
        $this->assertNotNull($evaluator);
        $this->assertNull(
            $evaluator->memberOrderFor('com.amazonaws.s3#UnknownShape')
        );
    }

    public function testIgnoresNestedNonMemberSchemasInCollectionParams(): void
    {
        // `listMember:` and `mapValue:` open nested `new Schema(...)`
        // blocks that are NOT struct members of the parent shape. The
        // scanner should skip them (they have `id:` but no
        // `memberName:` / `memberIndex:`) and still recover the parent
        // shape correctly.
        $source = <<<'PHP'
<?php
namespace Aws\Foo;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;
final class Schemas
{
    public static Schema $Bucket;
    public static function bucket(): Schema
    {
        return self::$Bucket = new Schema(
            id: 'com.amazonaws.s3#Bucket',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'BucketTags',
                    type: ShapeType::List_,
                    memberName: 'Tags',
                    memberIndex: 0,
                    listMember: new Schema(
                        id: 'TagValue',
                        type: ShapeType::String_
                    )
                ),
                new Schema(
                    id: 'BucketMeta',
                    type: ShapeType::Map,
                    memberName: 'Meta',
                    memberIndex: 1,
                    mapKey: new Schema(id: 'MetaKey', type: ShapeType::String_),
                    mapValue: new Schema(
                        id: 'MetaValue',
                        type: ShapeType::String_
                    )
                )
            ]
        );
    }
}
PHP;
        $dir = $this->writeSchemasFixture($source);

        $evaluator = PriorCodegenEvaluator::inspect($dir);
        $this->assertNotNull($evaluator);
        $this->assertSame(
            ['Tags', 'Meta'],
            $evaluator->memberOrderFor('com.amazonaws.s3#Bucket'),
            'listMember/mapKey/mapValue Schemas have no memberName or '
            . 'memberIndex, so the scanner must ignore them when '
            . 'building the parent shape\'s member list.'
        );
    }

    public function testHardFailsOnUnbalancedParens(): void
    {
        // Deliberately mangled fixture: unclosed `new Schema(` block.
        // The scanner should throw rather than silently emit partial
        // data - silent degradation would break ABI without warning.
        $source = <<<'PHP'
<?php
namespace Aws\Foo;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\ShapeType;
final class Schemas
{
    public static Schema $Broken;
    public static function broken(): Schema
    {
        return self::$Broken = new Schema(
            id: 'com.amazonaws.s3#Broken',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'x',
                    type: ShapeType::String_,
                    memberName: 'Field',
                    memberIndex: 0
                )
    }
}
PHP;
        $dir = $this->writeSchemasFixture($source);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches(
            '/unbalanced|malformed|tokenize/i'
        );
        PriorCodegenEvaluator::inspect($dir);
    }

    public function testHasPriorOrderingReturnsFalseForEmptyFile(): void
    {
        // Legitimately empty Schemas file (no shape blocks). Loader
        // succeeds but reports no ordering to consume.
        $source = <<<'PHP'
<?php
namespace Aws\Foo;
final class Schemas {}
PHP;
        $dir = $this->writeSchemasFixture($source);

        $evaluator = PriorCodegenEvaluator::inspect($dir);
        $this->assertNotNull($evaluator);
        $this->assertFalse($evaluator->hasPriorOrdering());
        $this->assertNull($evaluator->memberOrderFor('anything#Here'));
    }

    /**
     * Write a synthetic `Schemas.php` into a fresh temp dir and
     * queue the dir for cleanup after the test. Returns the temp
     * directory (what `PriorCodegenEvaluator::inspect()` takes as
     * an argument).
     */
    private function writeSchemasFixture(string $source): string
    {
        $dir = $this->makeTempDir();
        $path = $dir
            . \DIRECTORY_SEPARATOR
            . PriorCodegenEvaluator::SCHEMAS_FILENAME;
        \file_put_contents($path, $source);
        $this->cleanupPaths[] = $path;
        // Directory removal is queued in tearDown reverse order -
        // append the dir *after* the file so the file is unlinked
        // first, then the dir is empty when we try to rmdir it.
        $this->cleanupPaths[] = $dir;
        return $dir;
    }

    private function makeTempDir(): string
    {
        $base = \sys_get_temp_dir()
            . \DIRECTORY_SEPARATOR
            . 'prior-codegen-eval-'
            . \bin2hex(\random_bytes(4));
        if (!\mkdir($base, 0755, true) && !\is_dir($base)) {
            $this->fail("Could not create temp dir {$base}");
        }
        return $base;
    }
}
