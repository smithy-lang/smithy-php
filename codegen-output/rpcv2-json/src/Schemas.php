<?php

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

    public static function bigDecimalStructure(): Schema
    {
        if (isset(self::$BigDecimalStructure)) {
            return self::$BigDecimalStructure;
        }

        return self::$BigDecimalStructure = new Schema(
            id: 'smithy.protocoltests.rpcv2Json#BigDecimalStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#BigDecimal', type: ShapeType::BigDecimal, memberName: 'value', memberIndex: 0)
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
                new Schema(id: 'smithy.api#BigInteger', type: ShapeType::BigInteger, memberName: 'value', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#ComplexNestedErrorData', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)], targetClass: ComplexNestedErrorData::class)
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
            id: 'smithy.protocoltests.rpcv2Json#Defaults',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0),
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3),
                new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4),
                new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5),
                new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7),
                new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8),
                new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9),
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class),
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
                new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0)
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
            id: 'smithy.protocoltests.rpcv2Json#InvalidGreeting',
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
            id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#Defaults', type: ShapeType::Structure, memberName: 'defaults', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0), new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1), new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3), new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4), new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5), new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6), new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7), new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8), new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9), new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10), new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)), new Schema(id: 'smithy.protocoltests.rpcv2Json#TestEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class), new Schema(id: 'smithy.protocoltests.rpcv2Json#TestIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class), new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 14), new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'falseBoolean', memberIndex: 15), new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'emptyBlob', memberIndex: 16), new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'zeroByte', memberIndex: 17), new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'zeroShort', memberIndex: 18), new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'zeroInteger', memberIndex: 19), new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'zeroLong', memberIndex: 20), new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'zeroFloat', memberIndex: 21), new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'zeroDouble', memberIndex: 22)], targetClass: Defaults::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#ClientOptionalDefaults', type: ShapeType::Structure, memberName: 'clientOptionalDefaults', memberIndex: 1, members: [new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)], targetClass: ClientOptionalDefaults::class),
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
            id: 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'defaultString', memberIndex: 0),
                new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'defaultBoolean', memberIndex: 1),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringList', type: ShapeType::List_, memberName: 'defaultList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'defaultTimestamp', memberIndex: 3),
                new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'defaultBlob', memberIndex: 4),
                new Schema(id: 'smithy.api#Byte', type: ShapeType::Byte, memberName: 'defaultByte', memberIndex: 5),
                new Schema(id: 'smithy.api#Short', type: ShapeType::Short, memberName: 'defaultShort', memberIndex: 6),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'defaultInteger', memberIndex: 7),
                new Schema(id: 'smithy.api#Long', type: ShapeType::Long, memberName: 'defaultLong', memberIndex: 8),
                new Schema(id: 'smithy.api#Float', type: ShapeType::Float_, memberName: 'defaultFloat', memberIndex: 9),
                new Schema(id: 'smithy.api#Double', type: ShapeType::Double, memberName: 'defaultDouble', memberIndex: 10),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestStringMap', type: ShapeType::Map, memberName: 'defaultMap', memberIndex: 11, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestEnum', type: ShapeType::Enum, memberName: 'defaultEnum', memberIndex: 12, targetClass: TestEnum::class),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#TestIntEnum', type: ShapeType::IntEnum, memberName: 'defaultIntEnum', memberIndex: 13, targetClass: TestIntEnum::class),
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
            id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
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
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'bar', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested1::class)], targetClass: RecursiveShapesInputOutputNested2::class)
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
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'bar', memberIndex: 0),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
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
                new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseStructMap', type: ShapeType::Map, memberName: 'denseStructMap', memberIndex: 0, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#GreetingStruct', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseNumberMap', type: ShapeType::Map, memberName: 'denseNumberMap', memberIndex: 1, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseBooleanMap', type: ShapeType::Map, memberName: 'denseBooleanMap', memberIndex: 2, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseStringMap', type: ShapeType::Map, memberName: 'denseStringMap', memberIndex: 3, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#DenseSetMap', type: ShapeType::Map, memberName: 'denseSetMap', memberIndex: 4, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#StringSet', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)))
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
                new Schema(id: 'smithy.protocoltests.shared#StringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#StringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#IntegerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#BooleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#TimestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'smithy.protocoltests.shared#FooEnumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: new Schema(id: 'smithy.protocoltests.shared#FooEnum', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'smithy.protocoltests.shared#IntegerEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: new Schema(id: 'smithy.protocoltests.shared#IntegerEnum', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'smithy.protocoltests.shared#NestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: new Schema(id: 'smithy.protocoltests.shared#StringList', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#StructureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 8, listMember: new Schema(id: 'smithy.protocoltests.rpcv2Json#StructureListMember', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'a', memberIndex: 0), new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'b', memberIndex: 1)], targetClass: StructureListMember::class)),
                new Schema(id: 'smithy.protocoltests.shared#BlobList', type: ShapeType::List_, memberName: 'blobList', memberIndex: 9, listMember: new Schema(id: 'smithy.api#Blob', type: ShapeType::Blob, memberName: 'member', memberIndex: 0))
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
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseStructMap', type: ShapeType::Map, memberName: 'sparseStructMap', memberIndex: 0, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#GreetingStruct', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseNumberMap', type: ShapeType::Map, memberName: 'sparseNumberMap', memberIndex: 1, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseBooleanMap', type: ShapeType::Map, memberName: 'sparseBooleanMap', memberIndex: 2, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#Boolean', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.shared#SparseStringMap', type: ShapeType::Map, memberName: 'sparseStringMap', memberIndex: 3, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'smithy.protocoltests.rpcv2Json#SparseSetMap', type: ShapeType::Map, memberName: 'sparseSetMap', memberIndex: 4, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'smithy.protocoltests.shared#StringSet', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), traits: [15 => true])
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
            id: 'smithy.protocoltests.rpcv2Json#SimpleStructure',
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
            id: 'smithy.protocoltests.rpcv2Json#SparseNullsOperationInputOutput',
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
            id: 'smithy.protocoltests.rpcv2Json#StructureListMember',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'a', memberIndex: 0),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'b', memberIndex: 1)
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
                new Schema(id: 'smithy.protocoltests.shared#DateTime', type: ShapeType::Timestamp, memberName: 'dateTime', memberIndex: 0, traits: [4 => 'date-time']),
                new Schema(id: 'smithy.protocoltests.shared#HttpDate', type: ShapeType::Timestamp, memberName: 'httpDate', memberIndex: 1, traits: [4 => 'http-date']),
                new Schema(id: 'smithy.protocoltests.shared#EpochSeconds', type: ShapeType::Timestamp, memberName: 'epochSeconds', memberIndex: 2, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'smithy.api#Timestamp', type: ShapeType::Timestamp, memberName: 'normal', memberIndex: 3)
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
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)
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
