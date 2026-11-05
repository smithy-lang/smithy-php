<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\ComplexErrorException;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\RpcV2Cbor\Exception\ValidationException;
use Smithy\ProtocolTests\RpcV2Cbor\Input\OperationWithDefaultsInput;
use Smithy\ProtocolTests\RpcV2Cbor\Output\Float16Output;
use Smithy\ProtocolTests\RpcV2Cbor\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Output\OperationWithDefaultsOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\ClientOptionalDefaults;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\Defaults;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\EmptyStructure;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\FooEnum;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\GreetingStruct;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\IntegerEnum;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RecursiveShapesInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RecursiveShapesInputOutputNested1;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RecursiveShapesInputOutputNested2;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborDenseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborListInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborNestedUnion;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborSparseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborUnion;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborUnionInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\SimpleScalarStructure;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\SimpleStructure;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\SparseNullsOperationInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\StructureListMember;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\TestEnum;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\TestIntEnum;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\ValidationExceptionField;

/**
 * Centralized schema registry for all shapes in this service.
 *
 * Stores Schema objects as lazy public static properties.
 * Each structure has a dedicated member initializer.
 */
final class Schemas
{
    public static Schema $ValidationException;
    public static Schema $ValidationExceptionField;
    public static Schema $ClientOptionalDefaults;
    public static Schema $ComplexErrorException;
    public static Schema $ComplexNestedErrorData;
    public static Schema $Defaults;
    public static Schema $EmptyStructure;
    public static Schema $Float16Output;
    public static Schema $FractionalSecondsOutput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $InvalidGreetingException;
    public static Schema $OperationWithDefaultsInput;
    public static Schema $OperationWithDefaultsOutput;
    public static Schema $RecursiveShapesInputOutput;
    public static Schema $RecursiveShapesInputOutputNested1;
    public static Schema $RecursiveShapesInputOutputNested2;
    public static Schema $RpcV2CborDenseMapsInputOutput;
    public static Schema $RpcV2CborListInputOutput;
    public static Schema $RpcV2CborSparseMapsInputOutput;
    public static Schema $RpcV2CborUnionInputOutput;
    public static Schema $SimpleScalarStructure;
    public static Schema $SimpleStructure;
    public static Schema $SparseNullsOperationInputOutput;
    public static Schema $StructureListMember;
    public static Schema $GreetingStruct;
    public static Schema $RpcV2CborNestedUnion;
    public static Schema $RpcV2CborUnion;
    public static Schema $TestEnum;
    public static Schema $TestIntEnum;
    public static Schema $FooEnum;
    public static Schema $IntegerEnum;

    public static function validationException(): Schema
    {
        if (isset(self::$ValidationException)) {
            return self::$ValidationException;
        }

        return self::$ValidationException = new Schema(
            id: 'smithy.framework#ValidationException',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'message', memberIndex: 0, traits: [14 => true]),
                new Schema(id: 'smithy.framework#ValidationExceptionFieldList', type: ShapeType::List_, memberName: 'fieldList', memberIndex: 1, listMember: new Schema(id: 'smithy.framework#ValidationExceptionField', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'path', memberIndex: 0, traits: [14 => true]), new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'message', memberIndex: 1, traits: [14 => true])], targetClass: ValidationExceptionField::class))
            ], traits: [23 => 'client'],
            targetClass: ValidationException::class
        );
    }

    public static function validationExceptionField(): Schema
    {
        if (isset(self::$ValidationExceptionField)) {
            return self::$ValidationExceptionField;
        }

        return self::$ValidationExceptionField = new Schema(
            id: 'smithy.framework#ValidationExceptionField',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'path', memberIndex: 0, traits: [14 => true]),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'message', memberIndex: 1, traits: [14 => true])
            ],
            targetClass: ValidationExceptionField::class
        );
    }

    public static function clientOptionalDefaults(): Schema
    {
        if (isset(self::$ClientOptionalDefaults)) {
            return self::$ClientOptionalDefaults;
        }

        return self::$ClientOptionalDefaults = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#ClientOptionalDefaults',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)
            ],
            targetClass: ClientOptionalDefaults::class
        );
    }

    public static function complexErrorException(): Schema
    {
        if (isset(self::$ComplexErrorException)) {
            return self::$ComplexErrorException;
        }

        return self::$ComplexErrorException = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#ComplexNestedErrorData', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)], targetClass: ComplexNestedErrorData::class)
            ], traits: [23 => 'client'],
            targetClass: ComplexErrorException::class
        );
    }

    public static function complexNestedErrorData(): Schema
    {
        if (isset(self::$ComplexNestedErrorData)) {
            return self::$ComplexNestedErrorData;
        }

        return self::$ComplexNestedErrorData = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)
            ],
            targetClass: ComplexNestedErrorData::class
        );
    }

    public static function defaults(): Schema
    {
        if (isset(self::$Defaults)) {
            return self::$Defaults;
        }

        return self::$Defaults = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#Defaults',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0),
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestStringList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3),
                new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4),
                new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5),
                new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7),
                new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8),
                new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9),
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestStringMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 14),
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'falseBoolean', memberIndex: 15),
                new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'emptyBlob', memberIndex: 16),
                new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'zeroByte', memberIndex: 17),
                new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'zeroShort', memberIndex: 18),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'zeroInteger', memberIndex: 19),
                new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'zeroLong', memberIndex: 20),
                new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'zeroFloat', memberIndex: 21),
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'zeroDouble', memberIndex: 22)
            ],
            targetClass: Defaults::class
        );
    }

    public static function emptyStructure(): Schema
    {
        if (isset(self::$EmptyStructure)) {
            return self::$EmptyStructure;
        }

        return self::$EmptyStructure = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#EmptyStructure',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyStructure::class
        );
    }

    public static function float16Output(): Schema
    {
        if (isset(self::$Float16Output)) {
            return self::$Float16Output;
        }

        return self::$Float16Output = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#Float16Output',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'value', memberIndex: 0)
            ],
            targetClass: Float16Output::class
        );
    }

    public static function fractionalSecondsOutput(): Schema
    {
        if (isset(self::$FractionalSecondsOutput)) {
            return self::$FractionalSecondsOutput;
        }

        return self::$FractionalSecondsOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#FractionalSecondsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.shared#DateTime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
            ],
            targetClass: FractionalSecondsOutput::class
        );
    }

    public static function greetingWithErrorsOutput(): Schema
    {
        if (isset(self::$GreetingWithErrorsOutput)) {
            return self::$GreetingWithErrorsOutput;
        }

        return self::$GreetingWithErrorsOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
            ],
            targetClass: GreetingWithErrorsOutput::class
        );
    }

    public static function invalidGreetingException(): Schema
    {
        if (isset(self::$InvalidGreetingException)) {
            return self::$InvalidGreetingException;
        }

        return self::$InvalidGreetingException = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: InvalidGreetingException::class
        );
    }

    public static function operationWithDefaultsInput(): Schema
    {
        if (isset(self::$OperationWithDefaultsInput)) {
            return self::$OperationWithDefaultsInput;
        }

        return self::$OperationWithDefaultsInput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#OperationWithDefaultsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#Defaults', type: ShapeType::Structure, memberName: 'defaults', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0), new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestStringList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3), new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4), new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5), new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6), new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7), new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8), new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9), new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestStringMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class), new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 14), new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'falseBoolean', memberIndex: 15), new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'emptyBlob', memberIndex: 16), new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'zeroByte', memberIndex: 17), new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'zeroShort', memberIndex: 18), new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'zeroInteger', memberIndex: 19), new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'zeroLong', memberIndex: 20), new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'zeroFloat', memberIndex: 21), new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'zeroDouble', memberIndex: 22)], targetClass: Defaults::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#ClientOptionalDefaults', type: ShapeType::Structure, memberName: 'clientOptionalDefaults', memberIndex: 1, members: [new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)], targetClass: ClientOptionalDefaults::class),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'topLevelDefault', memberIndex: 2),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'otherTopLevelDefault', memberIndex: 3)
            ],
            targetClass: OperationWithDefaultsInput::class
        );
    }

    public static function operationWithDefaultsOutput(): Schema
    {
        if (isset(self::$OperationWithDefaultsOutput)) {
            return self::$OperationWithDefaultsOutput;
        }

        return self::$OperationWithDefaultsOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#OperationWithDefaultsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0),
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestStringList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3),
                new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4),
                new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5),
                new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7),
                new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8),
                new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9),
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestStringMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#TestIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 14),
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'falseBoolean', memberIndex: 15),
                new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'emptyBlob', memberIndex: 16),
                new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'zeroByte', memberIndex: 17),
                new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'zeroShort', memberIndex: 18),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'zeroInteger', memberIndex: 19),
                new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'zeroLong', memberIndex: 20),
                new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'zeroFloat', memberIndex: 21),
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'zeroDouble', memberIndex: 22)
            ],
            targetClass: OperationWithDefaultsOutput::class
        );
    }

    public static function recursiveShapesInputOutput(): Schema
    {
        if (isset(self::$RecursiveShapesInputOutput)) {
            return self::$RecursiveShapesInputOutput;
        }

        return self::$RecursiveShapesInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested1', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested2', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
            ],
            targetClass: RecursiveShapesInputOutput::class
        );
    }

    public static function recursiveShapesInputOutputNested1(): Schema
    {
        if (isset(self::$RecursiveShapesInputOutputNested1)) {
            return self::$RecursiveShapesInputOutputNested1;
        }

        return self::$RecursiveShapesInputOutputNested1 = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested1',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested2', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'bar', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested1', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested1::class)], targetClass: RecursiveShapesInputOutputNested2::class)
            ],
            targetClass: RecursiveShapesInputOutputNested1::class
        );
    }

    public static function recursiveShapesInputOutputNested2(): Schema
    {
        if (isset(self::$RecursiveShapesInputOutputNested2)) {
            return self::$RecursiveShapesInputOutputNested2;
        }

        return self::$RecursiveShapesInputOutputNested2 = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested2',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'bar', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested1', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested2', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
            ],
            targetClass: RecursiveShapesInputOutputNested2::class
        );
    }

    public static function rpcV2CborDenseMapsInputOutput(): Schema
    {
        if (isset(self::$RpcV2CborDenseMapsInputOutput)) {
            return self::$RpcV2CborDenseMapsInputOutput;
        }

        return self::$RpcV2CborDenseMapsInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborDenseMapsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#DenseStructMap', type: ShapeType::Map, memberName: 'denseStructMap', memberIndex: 0, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#GreetingStruct', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class)),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#DenseNumberMap', type: ShapeType::Map, memberName: 'denseNumberMap', memberIndex: 1, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#DenseBooleanMap', type: ShapeType::Map, memberName: 'denseBooleanMap', memberIndex: 2, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#DenseStringMap', type: ShapeType::Map, memberName: 'denseStringMap', memberIndex: 3, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#DenseSetMap', type: ShapeType::Map, memberName: 'denseSetMap', memberIndex: 4, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#StringSet', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)))
            ],
            targetClass: RpcV2CborDenseMapsInputOutput::class
        );
    }

    public static function rpcV2CborListInputOutput(): Schema
    {
        if (isset(self::$RpcV2CborListInputOutput)) {
            return self::$RpcV2CborListInputOutput;
        }

        return self::$RpcV2CborListInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborListInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.shared#StringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#StringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#IntegerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#BooleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#TimestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#FooEnumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: new Schema(id: 'smithy.protocoltests.shared#FooEnum', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'smithy.protocoltests.shared#IntegerEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: new Schema(id: 'smithy.protocoltests.shared#IntegerEnum', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'smithy.protocoltests.shared#NestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: new Schema(id: 'smithy.protocoltests.shared#StringList', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#StructureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 8, listMember: new Schema(id: 'smithy.protocoltests.rpcv2Cbor#StructureListMember', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'a', memberIndex: 0), new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'b', memberIndex: 1)], targetClass: StructureListMember::class)),
                new Schema(id: 'smithy.protocoltests.shared#BlobList', type: ShapeType::List_, memberName: 'blobList', memberIndex: 9, listMember: new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'member', memberIndex: 0))
            ],
            targetClass: RpcV2CborListInputOutput::class
        );
    }

    public static function rpcV2CborSparseMapsInputOutput(): Schema
    {
        if (isset(self::$RpcV2CborSparseMapsInputOutput)) {
            return self::$RpcV2CborSparseMapsInputOutput;
        }

        return self::$RpcV2CborSparseMapsInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborSparseMapsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#SparseStructMap', type: ShapeType::Map, memberName: 'sparseStructMap', memberIndex: 0, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#GreetingStruct', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#SparseNumberMap', type: ShapeType::Map, memberName: 'sparseNumberMap', memberIndex: 1, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#SparseBooleanMap', type: ShapeType::Map, memberName: 'sparseBooleanMap', memberIndex: 2, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.shared#SparseStringMap', type: ShapeType::Map, memberName: 'sparseStringMap', memberIndex: 3, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#SparseSetMap', type: ShapeType::Map, memberName: 'sparseSetMap', memberIndex: 4, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#StringSet', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), traits: [15 => true])
            ],
            targetClass: RpcV2CborSparseMapsInputOutput::class
        );
    }

    public static function rpcV2CborUnionInputOutput(): Schema
    {
        if (isset(self::$RpcV2CborUnionInputOutput)) {
            return self::$RpcV2CborUnionInputOutput;
        }

        return self::$RpcV2CborUnionInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnionInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnion', type: ShapeType::Union, memberName: 'contents', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborNestedUnion', type: ShapeType::Union, memberName: 'unionValue', memberIndex: 1, targetClass: RpcV2CborNestedUnion::class)], targetClass: RpcV2CborUnion::class),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'otherValue', memberIndex: 1)
            ],
            targetClass: RpcV2CborUnionInputOutput::class
        );
    }

    public static function simpleScalarStructure(): Schema
    {
        if (isset(self::$SimpleScalarStructure)) {
            return self::$SimpleScalarStructure;
        }

        return self::$SimpleScalarStructure = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#SimpleScalarStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'trueBooleanValue', memberIndex: 0),
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'falseBooleanValue', memberIndex: 1),
                new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'byteValue', memberIndex: 2),
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 3),
                new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 4),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 5),
                new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'longValue', memberIndex: 6),
                new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'shortValue', memberIndex: 7),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 8),
                new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 9)
            ],
            targetClass: SimpleScalarStructure::class
        );
    }

    public static function simpleStructure(): Schema
    {
        if (isset(self::$SimpleStructure)) {
            return self::$SimpleStructure;
        }

        return self::$SimpleStructure = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#SimpleStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 0)
            ],
            targetClass: SimpleStructure::class
        );
    }

    public static function sparseNullsOperationInputOutput(): Schema
    {
        if (isset(self::$SparseNullsOperationInputOutput)) {
            return self::$SparseNullsOperationInputOutput;
        }

        return self::$SparseNullsOperationInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#SparseNullsOperationInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.shared#SparseStringList', type: ShapeType::List_, memberName: 'sparseStringList', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.shared#SparseStringMap', type: ShapeType::Map, memberName: 'sparseStringMap', memberIndex: 1, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [15 => true])
            ],
            targetClass: SparseNullsOperationInputOutput::class
        );
    }

    public static function structureListMember(): Schema
    {
        if (isset(self::$StructureListMember)) {
            return self::$StructureListMember;
        }

        return self::$StructureListMember = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#StructureListMember',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'a', memberIndex: 0),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'b', memberIndex: 1)
            ],
            targetClass: StructureListMember::class
        );
    }

    public static function greetingStruct(): Schema
    {
        if (isset(self::$GreetingStruct)) {
            return self::$GreetingStruct;
        }

        return self::$GreetingStruct = new Schema(
            id: 'smithy.protocoltests.shared#GreetingStruct',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)
            ],
            targetClass: GreetingStruct::class
        );
    }

    public static function rpcV2CborNestedUnion(): Schema
    {
        if (isset(self::$RpcV2CborNestedUnion)) {
            return self::$RpcV2CborNestedUnion;
        }

        return self::$RpcV2CborNestedUnion = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborNestedUnion',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0)
            ],
            targetClass: RpcV2CborNestedUnion::class
        );
    }

    public static function rpcV2CborUnion(): Schema
    {
        if (isset(self::$RpcV2CborUnion)) {
            return self::$RpcV2CborUnion;
        }

        return self::$RpcV2CborUnion = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnion',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Cbor#RpcV2CborNestedUnion', type: ShapeType::Union, memberName: 'unionValue', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0)], targetClass: RpcV2CborNestedUnion::class)
            ],
            targetClass: RpcV2CborUnion::class
        );
    }

    public static function testEnum(): Schema
    {
        if (isset(self::$TestEnum)) {
            return self::$TestEnum;
        }

        return self::$TestEnum = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#TestEnum',
            type: ShapeType::Enum,
            targetClass: TestEnum::class
        );
    }

    public static function testIntEnum(): Schema
    {
        if (isset(self::$TestIntEnum)) {
            return self::$TestIntEnum;
        }

        return self::$TestIntEnum = new Schema(
            id: 'smithy.protocoltests.rpcv2Cbor#TestIntEnum',
            type: ShapeType::IntEnum,
            targetClass: TestIntEnum::class
        );
    }

    public static function fooEnum(): Schema
    {
        if (isset(self::$FooEnum)) {
            return self::$FooEnum;
        }

        return self::$FooEnum = new Schema(
            id: 'smithy.protocoltests.shared#FooEnum',
            type: ShapeType::Enum,
            targetClass: FooEnum::class
        );
    }

    public static function integerEnum(): Schema
    {
        if (isset(self::$IntegerEnum)) {
            return self::$IntegerEnum;
        }

        return self::$IntegerEnum = new Schema(
            id: 'smithy.protocoltests.shared#IntegerEnum',
            type: ShapeType::IntEnum,
            targetClass: IntegerEnum::class
        );
    }

}
