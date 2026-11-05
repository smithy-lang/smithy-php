<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore;

use Smithy\CodegenOutput\TrimmedPetStore\Operation\GetPetOperation;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;

final class TrimmedPetStoreService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'TrimmedPetStore';
    }

    public static function id(): string
    {
        return 'smithy.example#PetStore';
    }

    public static function version(): string
    {
        return '2026-10-14';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.example#PetStore',
            type: ShapeType::Service,
            traitsById: [
                'smithy.protocols#rpcv2Cbor' => []
            ]
        );
    }

    public static function operations(): array
    {
        return [
            GetPetOperation::class
        ];
    }
}
