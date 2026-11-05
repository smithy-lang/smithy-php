<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Operation\{
    ListMapsOperation,
    ListStringsOperation,
    ListWithoutPageSizeOperation
};

final class PaginationService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'Pagination';
    }

    public static function id(): string
    {
        return 'smithy.pagination#Pagination';
    }

    public static function version(): string
    {
        return '2026-08-03';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.pagination#Pagination',
            type: ShapeType::Service,
            traitsById: ['smithy.protocols#rpcv2Cbor' => true]
        );
    }

    public static function operations(): array
    {
        return [
            ListMapsOperation::class,
            ListStringsOperation::class,
            ListWithoutPageSizeOperation::class
        ];
    }
}
