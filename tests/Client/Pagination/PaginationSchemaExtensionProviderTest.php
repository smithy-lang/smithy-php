<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Pagination\{
    PaginationSchemaExtension,
    PaginationSchemaExtensionProvider
};
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Client\Pagination\Fixture\{
    NotPaginatedOperation,
    PaginationOperation
};

#[CoversClass(PaginationSchemaExtensionProvider::class)]
final class PaginationSchemaExtensionProviderTest extends TestCase
{
    public function testProvidesModeledPaginationMetadata(): void
    {
        $extension = PaginationOperation::instance()
            ->getSchema()
            ->getExtension(PaginationSchemaExtensionProvider::key());

        self::assertInstanceOf(PaginationSchemaExtension::class, $extension);
        self::assertSame('nextToken', $extension->inputToken);
        self::assertSame('result.nextToken', $extension->outputToken);
        self::assertSame('result.items', $extension->items);
        self::assertSame('maxResults', $extension->pageSize);
    }

    public function testReturnsNullWithoutPaginationMetadata(): void
    {
        self::assertNull(
            NotPaginatedOperation::instance()
                ->getSchema()
                ->getExtension(PaginationSchemaExtensionProvider::key())
        );
    }

    public function testPaginationAndChecksumTraitsDoNotCollide(): void
    {
        $checksum = ['requestAlgorithmMember' => 'checksumAlgorithm'];
        $schema = new Schema(
            id: 'smithy.tests#PaginatedChecksumOperation',
            type: ShapeType::Operation,
            traits: [
                Schema::T_HTTP_CHECKSUM => $checksum,
                Schema::T_PAGINATED => [
                    'inputToken' => 'nextToken',
                    'outputToken' => 'nextToken'
                ]
            ]
        );

        $extension = $schema->getExtension(
            PaginationSchemaExtensionProvider::key()
        );

        self::assertInstanceOf(
            PaginationSchemaExtension::class,
            $extension
        );
        self::assertSame('nextToken', $extension->inputToken);
        self::assertSame('nextToken', $extension->outputToken);
        self::assertSame(
            $checksum,
            $schema->getTrait(Schema::T_HTTP_CHECKSUM)
        );
    }

    public function testReturnsSingletonExtensionKey(): void
    {
        self::assertSame(
            PaginationSchemaExtensionProvider::key(),
            PaginationSchemaExtensionProvider::key()
        );
    }
}
