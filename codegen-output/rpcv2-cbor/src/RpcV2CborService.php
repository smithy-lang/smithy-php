<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\EmptyInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\Float16Operation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\NoInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\OperationWithDefaultsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\OptionalInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RecursiveShapesOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborDenseMapsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborListsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborSparseMapsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborUnionsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\SparseNullsOperationOperation;

final class RpcV2CborService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'RpcV2Cbor';
    }

    public static function id(): string
    {
        return 'smithy.protocoltests.rpcv2Cbor#RpcV2Protocol';
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
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2Protocol',
            type: ShapeType::Service,
            traitsById: [
                'smithy.api#title' => 'RpcV2 Protocol Service',
                'smithy.protocols#rpcv2Cbor' => []
            ]
        );
    }

    public static function operations(): array
    {
        return [
            EmptyInputOutputOperation::class,
            Float16Operation::class,
            FractionalSecondsOperation::class,
            GreetingWithErrorsOperation::class,
            NoInputOutputOperation::class,
            OperationWithDefaultsOperation::class,
            OptionalInputOutputOperation::class,
            RecursiveShapesOperation::class,
            RpcV2CborDenseMapsOperation::class,
            RpcV2CborListsOperation::class,
            RpcV2CborSparseMapsOperation::class,
            RpcV2CborUnionsOperation::class,
            SimpleScalarPropertiesOperation::class,
            SparseNullsOperationOperation::class
        ];
    }
}
