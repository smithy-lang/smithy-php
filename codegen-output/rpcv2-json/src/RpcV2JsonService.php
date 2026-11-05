<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Json\Operation\BigDecimalOperationOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\BigIntegerOperationOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\EmptyInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\NoInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\OperationWithDefaultsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\OptionalInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RecursiveShapesOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RpcV2JsonDenseMapsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RpcV2JsonListsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RpcV2JsonSparseMapsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\SparseNullsOperationOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\TimestampFormatIgnoredOperation;

final class RpcV2JsonService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'RpcV2Json';
    }

    public static function id(): string
    {
        return 'smithy.protocoltests.rpcv2Json#RpcV2JsonProtocol';
    }

    public static function version(): string
    {
        return '2020-07-14';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonProtocol',
            type: ShapeType::Service,
            traitsById: [
                'smithy.api#title' => 'RpcV2 JSON Protocol Service',
                'smithy.protocols#rpcv2Json' => []
            ]
        );
    }

    public static function operations(): array
    {
        return [
            BigDecimalOperationOperation::class,
            BigIntegerOperationOperation::class,
            EmptyInputOutputOperation::class,
            FractionalSecondsOperation::class,
            GreetingWithErrorsOperation::class,
            NoInputOutputOperation::class,
            OperationWithDefaultsOperation::class,
            OptionalInputOutputOperation::class,
            RecursiveShapesOperation::class,
            RpcV2JsonDenseMapsOperation::class,
            RpcV2JsonListsOperation::class,
            RpcV2JsonSparseMapsOperation::class,
            SimpleScalarPropertiesOperation::class,
            SparseNullsOperationOperation::class,
            TimestampFormatIgnoredOperation::class
        ];
    }
}
