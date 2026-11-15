<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RpcV2Json\Exception\ComplexErrorException;
use Smithy\ProtocolTests\RpcV2Json\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\RpcV2Json\Exception\ValidationException;
use Smithy\ProtocolTests\RpcV2Json\Input\OperationWithDefaultsInput;
use Smithy\ProtocolTests\RpcV2Json\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\RpcV2Json\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\RpcV2Json\Output\OperationWithDefaultsOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\BigDecimalStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\BigIntegerStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\ClientOptionalDefaults;
use Smithy\ProtocolTests\RpcV2Json\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\RpcV2Json\Shape\Defaults;
use Smithy\ProtocolTests\RpcV2Json\Shape\EmptyStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\FooEnum;
use Smithy\ProtocolTests\RpcV2Json\Shape\GreetingStruct;
use Smithy\ProtocolTests\RpcV2Json\Shape\IntegerEnum;
use Smithy\ProtocolTests\RpcV2Json\Shape\RecursiveShapesInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\RecursiveShapesInputOutputNested1;
use Smithy\ProtocolTests\RpcV2Json\Shape\RecursiveShapesInputOutputNested2;
use Smithy\ProtocolTests\RpcV2Json\Shape\RpcV2JsonDenseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\RpcV2JsonListInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\RpcV2JsonSparseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\SimpleScalarStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\SimpleStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\SparseNullsOperationInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\StructureListMember;
use Smithy\ProtocolTests\RpcV2Json\Shape\TestEnum;
use Smithy\ProtocolTests\RpcV2Json\Shape\TestIntEnum;
use Smithy\ProtocolTests\RpcV2Json\Shape\TimestampFormatIgnoredIO;
use Smithy\ProtocolTests\RpcV2Json\Shape\ValidationExceptionField;

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
    public static Schema $BigDecimalStructure;
    public static Schema $BigIntegerStructure;
    public static Schema $ClientOptionalDefaults;
    public static Schema $ComplexErrorException;
    public static Schema $ComplexNestedErrorData;
    public static Schema $Defaults;
    public static Schema $EmptyStructure;
    public static Schema $FractionalSecondsOutput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $InvalidGreetingException;
    public static Schema $OperationWithDefaultsInput;
    public static Schema $OperationWithDefaultsOutput;
    public static Schema $RecursiveShapesInputOutput;
    public static Schema $RecursiveShapesInputOutputNested1;
    public static Schema $RecursiveShapesInputOutputNested2;
    public static Schema $RpcV2JsonDenseMapsInputOutput;
    public static Schema $RpcV2JsonListInputOutput;
    public static Schema $RpcV2JsonSparseMapsInputOutput;
    public static Schema $SimpleScalarStructure;
    public static Schema $SimpleStructure;
    public static Schema $SparseNullsOperationInputOutput;
    public static Schema $StructureListMember;
    public static Schema $TimestampFormatIgnoredIO;
    public static Schema $GreetingStruct;
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
                new Schema(id: 'smithy.framework#ValidationException$message', type: ShapeType::String_, memberName: 'message', memberIndex: 0, traits: [14 => true]),
                new Schema(id: 'smithy.framework#ValidationException$fieldList', type: ShapeType::List_, memberName: 'fieldList', memberIndex: 1, listMember: SchemasMembers::$shared['smithy.framework#ValidationExceptionFieldList$member'] ??= new Schema(id: 'smithy.framework#ValidationExceptionFieldList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: self::validationExceptionField()->getMembers(), targetClass: ValidationExceptionField::class))
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
                new Schema(id: 'smithy.framework#ValidationExceptionField$path', type: ShapeType::String_, memberName: 'path', memberIndex: 0, traits: [14 => true]),
                new Schema(id: 'smithy.framework#ValidationExceptionField$message', type: ShapeType::String_, memberName: 'message', memberIndex: 1, traits: [14 => true])
            ],
            targetClass: ValidationExceptionField::class
        );
    }

    public static function bigDecimalStructure(): Schema
    {
        if (isset(self::$BigDecimalStructure)) {
            return self::$BigDecimalStructure;
        }

        return self::$BigDecimalStructure = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#BigDecimalStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#BigDecimalStructure$value', type: ShapeType::BigDecimal, memberName: 'value', memberIndex: 0)
            ],
            targetClass: BigDecimalStructure::class
        );
    }

    public static function bigIntegerStructure(): Schema
    {
        if (isset(self::$BigIntegerStructure)) {
            return self::$BigIntegerStructure;
        }

        return self::$BigIntegerStructure = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#BigIntegerStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#BigIntegerStructure$value', type: ShapeType::BigInteger, memberName: 'value', memberIndex: 0)
            ],
            targetClass: BigIntegerStructure::class
        );
    }

    public static function clientOptionalDefaults(): Schema
    {
        if (isset(self::$ClientOptionalDefaults)) {
            return self::$ClientOptionalDefaults;
        }

        return self::$ClientOptionalDefaults = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#ClientOptionalDefaults',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#ClientOptionalDefaults$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#ComplexError$TopLevel', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#ComplexError$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 1, members: self::complexNestedErrorData()->getMembers(), targetClass: ComplexNestedErrorData::class)
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
            id: 'smithy.protocoltests.rpcv2Json#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#Defaults',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultString', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultBoolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#TestStringList$member'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultTimestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultBlob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultByte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultShort', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultInteger', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultLong', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultFloat', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultDouble', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#TestStringMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#TestStringMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$defaultIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$emptyString', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 14),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$falseBoolean', type: ShapeType::Boolean, memberName: 'falseBoolean', memberIndex: 15),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$emptyBlob', type: ShapeType::Blob, memberName: 'emptyBlob', memberIndex: 16),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$zeroByte', type: ShapeType::Byte, memberName: 'zeroByte', memberIndex: 17),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$zeroShort', type: ShapeType::Short, memberName: 'zeroShort', memberIndex: 18),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$zeroInteger', type: ShapeType::Integer, memberName: 'zeroInteger', memberIndex: 19),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$zeroLong', type: ShapeType::Long, memberName: 'zeroLong', memberIndex: 20),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$zeroFloat', type: ShapeType::Float_, memberName: 'zeroFloat', memberIndex: 21),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults$zeroDouble', type: ShapeType::Double, memberName: 'zeroDouble', memberIndex: 22)
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
            id: 'smithy.protocoltests.rpcv2Json#EmptyStructure',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyStructure::class
        );
    }

    public static function fractionalSecondsOutput(): Schema
    {
        if (isset(self::$FractionalSecondsOutput)) {
            return self::$FractionalSecondsOutput;
        }

        return self::$FractionalSecondsOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#FractionalSecondsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#FractionalSecondsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#GreetingWithErrorsOutput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#InvalidGreeting$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput$defaults', type: ShapeType::Structure, memberName: 'defaults', memberIndex: 0, members: self::defaults()->getMembers(), targetClass: Defaults::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput$clientOptionalDefaults', type: ShapeType::Structure, memberName: 'clientOptionalDefaults', memberIndex: 1, members: self::clientOptionalDefaults()->getMembers(), targetClass: ClientOptionalDefaults::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput$topLevelDefault', type: ShapeType::String_, memberName: 'topLevelDefault', memberIndex: 2),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput$otherTopLevelDefault', type: ShapeType::Integer, memberName: 'otherTopLevelDefault', memberIndex: 3)
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
            id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultString', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultBoolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#TestStringList$member'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultTimestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultBlob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultByte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultShort', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultInteger', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultLong', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultFloat', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultDouble', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#TestStringMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#TestStringMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$defaultIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$emptyString', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 14),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$falseBoolean', type: ShapeType::Boolean, memberName: 'falseBoolean', memberIndex: 15),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$emptyBlob', type: ShapeType::Blob, memberName: 'emptyBlob', memberIndex: 16),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$zeroByte', type: ShapeType::Byte, memberName: 'zeroByte', memberIndex: 17),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$zeroShort', type: ShapeType::Short, memberName: 'zeroShort', memberIndex: 18),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$zeroInteger', type: ShapeType::Integer, memberName: 'zeroInteger', memberIndex: 19),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$zeroLong', type: ShapeType::Long, memberName: 'zeroLong', memberIndex: 20),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$zeroFloat', type: ShapeType::Float_, memberName: 'zeroFloat', memberIndex: 21),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput$zeroDouble', type: ShapeType::Double, memberName: 'zeroDouble', memberIndex: 22)
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
            id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
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
            id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, members: [new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested1::class)], targetClass: RecursiveShapesInputOutputNested2::class)
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
            id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, members: [new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
            ],
            targetClass: RecursiveShapesInputOutputNested2::class
        );
    }

    public static function rpcV2JsonDenseMapsInputOutput(): Schema
    {
        if (isset(self::$RpcV2JsonDenseMapsInputOutput)) {
            return self::$RpcV2JsonDenseMapsInputOutput;
        }

        return self::$RpcV2JsonDenseMapsInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput$denseStructMap', type: ShapeType::Map, memberName: 'denseStructMap', memberIndex: 0, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseStructMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseStructMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseStructMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseStructMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: self::greetingStruct()->getMembers(), targetClass: GreetingStruct::class)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput$denseNumberMap', type: ShapeType::Map, memberName: 'denseNumberMap', memberIndex: 1, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseNumberMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseNumberMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseNumberMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseNumberMap$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput$denseBooleanMap', type: ShapeType::Map, memberName: 'denseBooleanMap', memberIndex: 2, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseBooleanMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseBooleanMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseBooleanMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseBooleanMap$value', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput$denseStringMap', type: ShapeType::Map, memberName: 'denseStringMap', memberIndex: 3, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseStringMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseStringMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput$denseSetMap', type: ShapeType::Map, memberName: 'denseSetMap', memberIndex: 4, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseSetMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseSetMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#DenseSetMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseSetMap$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'smithy.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)))
            ],
            targetClass: RpcV2JsonDenseMapsInputOutput::class
        );
    }

    public static function rpcV2JsonListInputOutput(): Schema
    {
        if (isset(self::$RpcV2JsonListInputOutput)) {
            return self::$RpcV2JsonListInputOutput;
        }

        return self::$RpcV2JsonListInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#StringList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'smithy.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#IntegerList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#BooleanList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#TimestampList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#NestedStringList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#StringList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 8, listMember: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#StructureList$member'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: self::structureListMember()->getMembers(), targetClass: StructureListMember::class)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput$blobList', type: ShapeType::List_, memberName: 'blobList', memberIndex: 9, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#BlobList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#BlobList$member', type: ShapeType::Blob, memberName: 'member', memberIndex: 0))
            ],
            targetClass: RpcV2JsonListInputOutput::class
        );
    }

    public static function rpcV2JsonSparseMapsInputOutput(): Schema
    {
        if (isset(self::$RpcV2JsonSparseMapsInputOutput)) {
            return self::$RpcV2JsonSparseMapsInputOutput;
        }

        return self::$RpcV2JsonSparseMapsInputOutput = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput$sparseStructMap', type: ShapeType::Map, memberName: 'sparseStructMap', memberIndex: 0, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseStructMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseStructMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseStructMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseStructMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: self::greetingStruct()->getMembers(), targetClass: GreetingStruct::class), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput$sparseNumberMap', type: ShapeType::Map, memberName: 'sparseNumberMap', memberIndex: 1, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseNumberMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseNumberMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseNumberMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseNumberMap$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput$sparseBooleanMap', type: ShapeType::Map, memberName: 'sparseBooleanMap', memberIndex: 2, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseBooleanMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseBooleanMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseBooleanMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseBooleanMap$value', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput$sparseStringMap', type: ShapeType::Map, memberName: 'sparseStringMap', memberIndex: 3, mapKey: SchemasMembers::$shared['smithy.protocoltests.shared#SparseStringMap$key'] ??= new Schema(id: 'smithy.protocoltests.shared#SparseStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.shared#SparseStringMap$value'] ??= new Schema(id: 'smithy.protocoltests.shared#SparseStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput$sparseSetMap', type: ShapeType::Map, memberName: 'sparseSetMap', memberIndex: 4, mapKey: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseSetMap$key'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseSetMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.rpcv2Json#SparseSetMap$value'] ??= new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseSetMap$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'smithy.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), traits: [15 => true])
            ],
            targetClass: RpcV2JsonSparseMapsInputOutput::class
        );
    }

    public static function simpleScalarStructure(): Schema
    {
        if (isset(self::$SimpleScalarStructure)) {
            return self::$SimpleScalarStructure;
        }

        return self::$SimpleScalarStructure = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$trueBooleanValue', type: ShapeType::Boolean, memberName: 'trueBooleanValue', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$falseBooleanValue', type: ShapeType::Boolean, memberName: 'falseBooleanValue', memberIndex: 1),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$byteValue', type: ShapeType::Byte, memberName: 'byteValue', memberIndex: 2),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 3),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 4),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 5),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 6),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$shortValue', type: ShapeType::Short, memberName: 'shortValue', memberIndex: 7),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 8),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure$blobValue', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 9)
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
            id: 'smithy.protocoltests.rpcv2Json#SimpleStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SimpleStructure$value', type: ShapeType::String_, memberName: 'value', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#SparseNullsOperationInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseNullsOperationInputOutput$sparseStringList', type: ShapeType::List_, memberName: 'sparseStringList', memberIndex: 0, listMember: SchemasMembers::$shared['smithy.protocoltests.shared#SparseStringList$member'] ??= new Schema(id: 'smithy.protocoltests.shared#SparseStringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseNullsOperationInputOutput$sparseStringMap', type: ShapeType::Map, memberName: 'sparseStringMap', memberIndex: 1, mapKey: SchemasMembers::$shared['smithy.protocoltests.shared#SparseStringMap$key'] ??= new Schema(id: 'smithy.protocoltests.shared#SparseStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['smithy.protocoltests.shared#SparseStringMap$value'] ??= new Schema(id: 'smithy.protocoltests.shared#SparseStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [15 => true])
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
            id: 'smithy.protocoltests.rpcv2Json#StructureListMember',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1)
            ],
            targetClass: StructureListMember::class
        );
    }

    public static function timestampFormatIgnoredIO(): Schema
    {
        if (isset(self::$TimestampFormatIgnoredIO)) {
            return self::$TimestampFormatIgnoredIO;
        }

        return self::$TimestampFormatIgnoredIO = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO$dateTime', type: ShapeType::Timestamp, memberName: 'dateTime', memberIndex: 0, traits: [4 => 'date-time']),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO$httpDate', type: ShapeType::Timestamp, memberName: 'httpDate', memberIndex: 1, traits: [4 => 'http-date']),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO$epochSeconds', type: ShapeType::Timestamp, memberName: 'epochSeconds', memberIndex: 2, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO$normal', type: ShapeType::Timestamp, memberName: 'normal', memberIndex: 3)
            ],
            targetClass: TimestampFormatIgnoredIO::class
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
                new Schema(id: 'smithy.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)
            ],
            targetClass: GreetingStruct::class
        );
    }

    public static function testEnum(): Schema
    {
        if (isset(self::$TestEnum)) {
            return self::$TestEnum;
        }

        return self::$TestEnum = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#TestEnum',
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
            id: 'smithy.protocoltests.rpcv2Json#TestIntEnum',
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

/**
 * Shared list and map member schemas for this service, keyed by member ID.
 *
 * Held here rather than on Schemas so no generated shape property can collide with it.
 */
final class SchemasMembers
{
    /** @var array<string, Schema> */
    public static array $shared = [];
}
