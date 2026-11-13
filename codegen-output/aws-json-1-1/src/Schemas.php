<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_1\Exception\ComplexErrorException;
use Smithy\ProtocolTests\AwsJson1_1\Exception\ErrorWithMembersException;
use Smithy\ProtocolTests\AwsJson1_1\Exception\ErrorWithoutMembersException;
use Smithy\ProtocolTests\AwsJson1_1\Exception\FooErrorException;
use Smithy\ProtocolTests\AwsJson1_1\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\AwsJson1_1\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\HostLabelInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\OperationWithOptionalInputOutputInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\AwsJson1_1\Output\ContentTypeParametersOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\DatetimeOffsetsOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\OperationWithOptionalInputOutputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\AwsJson1_1\Shape\EmptyStruct;
use Smithy\ProtocolTests\AwsJson1_1\Shape\FooEnum;
use Smithy\ProtocolTests\AwsJson1_1\Shape\GreetingStruct;
use Smithy\ProtocolTests\AwsJson1_1\Shape\IntegerEnum;
use Smithy\ProtocolTests\AwsJson1_1\Shape\JsonEnumsInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\JsonIntEnumsInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\KitchenSink;
use Smithy\ProtocolTests\AwsJson1_1\Shape\MyUnion;
use Smithy\ProtocolTests\AwsJson1_1\Shape\NullOperationInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\PutAndGetInlineDocumentsInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\SimpleScalarPropertiesInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\SimpleStruct;
use Smithy\ProtocolTests\AwsJson1_1\Shape\SparseNullsOperationInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\StructWithJsonName;
use Smithy\ProtocolTests\AwsJson1_1\Shape\UnionInputOutput;

/**
 * Centralized schema registry for all shapes in this service.
 *
 * Stores Schema objects as lazy public static properties.
 * Each structure has a dedicated member initializer.
 */
final class Schemas
{
    public static Schema $ComplexErrorException;
    public static Schema $ComplexNestedErrorData;
    public static Schema $ContentTypeParametersInput;
    public static Schema $ContentTypeParametersOutput;
    public static Schema $DatetimeOffsetsOutput;
    public static Schema $EmptyStruct;
    public static Schema $ErrorWithMembersException;
    public static Schema $ErrorWithoutMembersException;
    public static Schema $FooErrorException;
    public static Schema $FractionalSecondsOutput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $HostLabelInput;
    public static Schema $InvalidGreetingException;
    public static Schema $JsonEnumsInputOutput;
    public static Schema $JsonIntEnumsInputOutput;
    public static Schema $KitchenSink;
    public static Schema $NullOperationInputOutput;
    public static Schema $OperationWithOptionalInputOutputInput;
    public static Schema $OperationWithOptionalInputOutputOutput;
    public static Schema $PutAndGetInlineDocumentsInputOutput;
    public static Schema $PutWithContentEncodingInput;
    public static Schema $SimpleScalarPropertiesInputOutput;
    public static Schema $SimpleStruct;
    public static Schema $SparseNullsOperationInputOutput;
    public static Schema $StructWithJsonName;
    public static Schema $UnionInputOutput;
    public static Schema $GreetingStruct;
    public static Schema $MyUnion;
    public static Schema $FooEnum;
    public static Schema $IntegerEnum;

    public static function complexErrorException(): Schema
    {
        if (isset(self::$ComplexErrorException)) {
            return self::$ComplexErrorException;
        }

        return self::$ComplexErrorException = new Schema(
            id: 'aws.protocoltests.json#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#ComplexError$TopLevel', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json#ComplexError$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.json#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0, traits: [12 => 'Fooooo'])], targetClass: ComplexNestedErrorData::class)
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
            id: 'aws.protocoltests.json#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0, traits: [12 => 'Fooooo'])
            ],
            targetClass: ComplexNestedErrorData::class
        );
    }

    public static function contentTypeParametersInput(): Schema
    {
        if (isset(self::$ContentTypeParametersInput)) {
            return self::$ContentTypeParametersInput;
        }

        return self::$ContentTypeParametersInput = new Schema(
            id: 'aws.protocoltests.json#ContentTypeParametersInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#ContentTypeParametersInput$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 0)
            ],
            targetClass: ContentTypeParametersInput::class
        );
    }

    public static function contentTypeParametersOutput(): Schema
    {
        if (isset(self::$ContentTypeParametersOutput)) {
            return self::$ContentTypeParametersOutput;
        }

        return self::$ContentTypeParametersOutput = new Schema(
            id: 'aws.protocoltests.json#ContentTypeParametersOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: ContentTypeParametersOutput::class
        );
    }

    public static function datetimeOffsetsOutput(): Schema
    {
        if (isset(self::$DatetimeOffsetsOutput)) {
            return self::$DatetimeOffsetsOutput;
        }

        return self::$DatetimeOffsetsOutput = new Schema(
            id: 'aws.protocoltests.json#DatetimeOffsetsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#DatetimeOffsetsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
            ],
            targetClass: DatetimeOffsetsOutput::class
        );
    }

    public static function emptyStruct(): Schema
    {
        if (isset(self::$EmptyStruct)) {
            return self::$EmptyStruct;
        }

        return self::$EmptyStruct = new Schema(
            id: 'aws.protocoltests.json#EmptyStruct',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyStruct::class
        );
    }

    public static function errorWithMembersException(): Schema
    {
        if (isset(self::$ErrorWithMembersException)) {
            return self::$ErrorWithMembersException;
        }

        return self::$ErrorWithMembersException = new Schema(
            id: 'aws.protocoltests.json#ErrorWithMembers',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#ErrorWithMembers$Code', type: ShapeType::String_, memberName: 'Code', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json#ErrorWithMembers$ComplexData', type: ShapeType::Structure, memberName: 'ComplexData', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.json#KitchenSink$Blob', type: ShapeType::Blob, memberName: 'Blob', memberIndex: 0), new Schema(id: 'aws.protocoltests.json#KitchenSink$Boolean', type: ShapeType::Boolean, memberName: 'Boolean', memberIndex: 1), new Schema(id: 'aws.protocoltests.json#KitchenSink$Double', type: ShapeType::Double, memberName: 'Double', memberIndex: 2), new Schema(id: 'aws.protocoltests.json#KitchenSink$EmptyStruct', type: ShapeType::Structure, memberName: 'EmptyStruct', memberIndex: 3, targetClass: EmptyStruct::class), new Schema(id: 'aws.protocoltests.json#KitchenSink$Float', type: ShapeType::Float_, memberName: 'Float', memberIndex: 4), new Schema(id: 'aws.protocoltests.json#KitchenSink$HttpdateTimestamp', type: ShapeType::Timestamp, memberName: 'HttpdateTimestamp', memberIndex: 5, traits: [4 => 'http-date']), new Schema(id: 'aws.protocoltests.json#KitchenSink$Integer', type: ShapeType::Integer, memberName: 'Integer', memberIndex: 6), new Schema(id: 'aws.protocoltests.json#KitchenSink$Iso8601Timestamp', type: ShapeType::Timestamp, memberName: 'Iso8601Timestamp', memberIndex: 7, traits: [4 => 'date-time']), new Schema(id: 'aws.protocoltests.json#KitchenSink$JsonValue', type: ShapeType::String_, memberName: 'JsonValue', memberIndex: 8, traits: [13 => 'application/json']), new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfLists', type: ShapeType::List_, memberName: 'ListOfLists', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.json#ListOfListOfStrings$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStrings$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))), new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfMapsOfStrings', type: ShapeType::List_, memberName: 'ListOfMapsOfStrings', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.json#ListOfMapsOfStrings$member', type: ShapeType::Map, memberName: 'member', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStrings$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1))), new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfStrings', type: ShapeType::List_, memberName: 'ListOfStrings', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStrings$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfStructs', type: ShapeType::List_, memberName: 'ListOfStructs', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStructs$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.json#SimpleStruct$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)], targetClass: SimpleStruct::class)), new Schema(id: 'aws.protocoltests.json#KitchenSink$Long', type: ShapeType::Long, memberName: 'Long', memberIndex: 13), new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfListsOfStrings', type: ShapeType::Map, memberName: 'MapOfListsOfStrings', memberIndex: 14, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfListsOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfListsOfStrings$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStrings$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))), new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfMaps', type: ShapeType::Map, memberName: 'MapOfMaps', memberIndex: 15, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfMapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfMapOfStrings$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStrings$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1))), new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfStrings', type: ShapeType::Map, memberName: 'MapOfStrings', memberIndex: 16, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStrings$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)), new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfStructs', type: ShapeType::Map, memberName: 'MapOfStructs', memberIndex: 17, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStructs$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStructs$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.json#SimpleStruct$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)], targetClass: SimpleStruct::class)), new Schema(id: 'aws.protocoltests.json#KitchenSink$RecursiveList', type: ShapeType::List_, memberName: 'RecursiveList', memberIndex: 18, listMember: new Schema(id: 'aws.protocoltests.json#ListOfKitchenSinks$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, targetClass: KitchenSink::class)), new Schema(id: 'aws.protocoltests.json#KitchenSink$RecursiveMap', type: ShapeType::Map, memberName: 'RecursiveMap', memberIndex: 19, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfKitchenSinks$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfKitchenSinks$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, targetClass: KitchenSink::class)), new Schema(id: 'aws.protocoltests.json#KitchenSink$RecursiveStruct', type: ShapeType::Structure, memberName: 'RecursiveStruct', memberIndex: 20, targetClass: KitchenSink::class), new Schema(id: 'aws.protocoltests.json#KitchenSink$SimpleStruct', type: ShapeType::Structure, memberName: 'SimpleStruct', memberIndex: 21, targetClass: SimpleStruct::class), new Schema(id: 'aws.protocoltests.json#KitchenSink$String', type: ShapeType::String_, memberName: 'String', memberIndex: 22), new Schema(id: 'aws.protocoltests.json#KitchenSink$StructWithJsonName', type: ShapeType::Structure, memberName: 'StructWithJsonName', memberIndex: 23, targetClass: StructWithJsonName::class), new Schema(id: 'aws.protocoltests.json#KitchenSink$Timestamp', type: ShapeType::Timestamp, memberName: 'Timestamp', memberIndex: 24), new Schema(id: 'aws.protocoltests.json#KitchenSink$UnixTimestamp', type: ShapeType::Timestamp, memberName: 'UnixTimestamp', memberIndex: 25, traits: [4 => 'epoch-seconds'])], targetClass: KitchenSink::class),
                new Schema(id: 'aws.protocoltests.json#ErrorWithMembers$IntegerField', type: ShapeType::Integer, memberName: 'IntegerField', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.json#ErrorWithMembers$ListField', type: ShapeType::List_, memberName: 'ListField', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStrings$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.json#ErrorWithMembers$MapField', type: ShapeType::Map, memberName: 'MapField', memberIndex: 4, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStrings$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.json#ErrorWithMembers$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.json#ErrorWithMembers$StringField', type: ShapeType::String_, memberName: 'StringField', memberIndex: 6)
            ], traits: [23 => 'client'],
            targetClass: ErrorWithMembersException::class
        );
    }

    public static function errorWithoutMembersException(): Schema
    {
        if (isset(self::$ErrorWithoutMembersException)) {
            return self::$ErrorWithoutMembersException;
        }

        return self::$ErrorWithoutMembersException = new Schema(
            id: 'aws.protocoltests.json#ErrorWithoutMembers',
            type: ShapeType::Structure,
            members: [
            ], traits: [23 => 'server'],
            targetClass: ErrorWithoutMembersException::class
        );
    }

    public static function fooErrorException(): Schema
    {
        if (isset(self::$FooErrorException)) {
            return self::$FooErrorException;
        }

        return self::$FooErrorException = new Schema(
            id: 'aws.protocoltests.json#FooError',
            type: ShapeType::Structure,
            members: [
            ], traits: [23 => 'server'],
            targetClass: FooErrorException::class
        );
    }

    public static function fractionalSecondsOutput(): Schema
    {
        if (isset(self::$FractionalSecondsOutput)) {
            return self::$FractionalSecondsOutput;
        }

        return self::$FractionalSecondsOutput = new Schema(
            id: 'aws.protocoltests.json#FractionalSecondsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#FractionalSecondsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
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
            id: 'aws.protocoltests.json#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#GreetingWithErrorsOutput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
            ],
            targetClass: GreetingWithErrorsOutput::class
        );
    }

    public static function hostLabelInput(): Schema
    {
        if (isset(self::$HostLabelInput)) {
            return self::$HostLabelInput;
        }

        return self::$HostLabelInput = new Schema(
            id: 'aws.protocoltests.json#HostLabelInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#HostLabelInput$label', type: ShapeType::String_, memberName: 'label', memberIndex: 0, traits: [14 => true])
            ],
            targetClass: HostLabelInput::class
        );
    }

    public static function invalidGreetingException(): Schema
    {
        if (isset(self::$InvalidGreetingException)) {
            return self::$InvalidGreetingException;
        }

        return self::$InvalidGreetingException = new Schema(
            id: 'aws.protocoltests.json#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#InvalidGreeting$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: InvalidGreetingException::class
        );
    }

    public static function jsonEnumsInputOutput(): Schema
    {
        if (isset(self::$JsonEnumsInputOutput)) {
            return self::$JsonEnumsInputOutput;
        }

        return self::$JsonEnumsInputOutput = new Schema(
            id: 'aws.protocoltests.json#JsonEnumsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#JsonEnumsInputOutput$fooEnum1', type: ShapeType::Enum, memberName: 'fooEnum1', memberIndex: 0, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.json#JsonEnumsInputOutput$fooEnum2', type: ShapeType::Enum, memberName: 'fooEnum2', memberIndex: 1, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.json#JsonEnumsInputOutput$fooEnum3', type: ShapeType::Enum, memberName: 'fooEnum3', memberIndex: 2, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.json#JsonEnumsInputOutput$fooEnumList', type: ShapeType::List_, memberName: 'fooEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.json#JsonEnumsInputOutput$fooEnumSet', type: ShapeType::List_, memberName: 'fooEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumSet$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.json#JsonEnumsInputOutput$fooEnumMap', type: ShapeType::Map, memberName: 'fooEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))
            ],
            targetClass: JsonEnumsInputOutput::class
        );
    }

    public static function jsonIntEnumsInputOutput(): Schema
    {
        if (isset(self::$JsonIntEnumsInputOutput)) {
            return self::$JsonIntEnumsInputOutput;
        }

        return self::$JsonIntEnumsInputOutput = new Schema(
            id: 'aws.protocoltests.json#JsonIntEnumsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#JsonIntEnumsInputOutput$intEnum1', type: ShapeType::IntEnum, memberName: 'intEnum1', memberIndex: 0, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.json#JsonIntEnumsInputOutput$intEnum2', type: ShapeType::IntEnum, memberName: 'intEnum2', memberIndex: 1, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.json#JsonIntEnumsInputOutput$intEnum3', type: ShapeType::IntEnum, memberName: 'intEnum3', memberIndex: 2, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.json#JsonIntEnumsInputOutput$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.json#JsonIntEnumsInputOutput$intEnumSet', type: ShapeType::List_, memberName: 'intEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumSet$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.json#JsonIntEnumsInputOutput$intEnumMap', type: ShapeType::Map, memberName: 'intEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$value', type: ShapeType::IntEnum, memberName: 'value', memberIndex: 1, targetClass: IntegerEnum::class))
            ],
            targetClass: JsonIntEnumsInputOutput::class
        );
    }

    public static function kitchenSink(): Schema
    {
        if (isset(self::$KitchenSink)) {
            return self::$KitchenSink;
        }

        return self::$KitchenSink = new Schema(
            id: 'aws.protocoltests.json#KitchenSink',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Blob', type: ShapeType::Blob, memberName: 'Blob', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Boolean', type: ShapeType::Boolean, memberName: 'Boolean', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Double', type: ShapeType::Double, memberName: 'Double', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$EmptyStruct', type: ShapeType::Structure, memberName: 'EmptyStruct', memberIndex: 3, targetClass: EmptyStruct::class),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Float', type: ShapeType::Float_, memberName: 'Float', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$HttpdateTimestamp', type: ShapeType::Timestamp, memberName: 'HttpdateTimestamp', memberIndex: 5, traits: [4 => 'http-date']),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Integer', type: ShapeType::Integer, memberName: 'Integer', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Iso8601Timestamp', type: ShapeType::Timestamp, memberName: 'Iso8601Timestamp', memberIndex: 7, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$JsonValue', type: ShapeType::String_, memberName: 'JsonValue', memberIndex: 8, traits: [13 => 'application/json']),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfLists', type: ShapeType::List_, memberName: 'ListOfLists', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.json#ListOfListOfStrings$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStrings$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfMapsOfStrings', type: ShapeType::List_, memberName: 'ListOfMapsOfStrings', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.json#ListOfMapsOfStrings$member', type: ShapeType::Map, memberName: 'member', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStrings$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1))),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfStrings', type: ShapeType::List_, memberName: 'ListOfStrings', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStrings$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$ListOfStructs', type: ShapeType::List_, memberName: 'ListOfStructs', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStructs$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.json#SimpleStruct$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)], targetClass: SimpleStruct::class)),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Long', type: ShapeType::Long, memberName: 'Long', memberIndex: 13),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfListsOfStrings', type: ShapeType::Map, memberName: 'MapOfListsOfStrings', memberIndex: 14, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfListsOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfListsOfStrings$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.json#ListOfStrings$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfMaps', type: ShapeType::Map, memberName: 'MapOfMaps', memberIndex: 15, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfMapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfMapOfStrings$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStrings$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1))),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfStrings', type: ShapeType::Map, memberName: 'MapOfStrings', memberIndex: 16, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStrings$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStrings$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$MapOfStructs', type: ShapeType::Map, memberName: 'MapOfStructs', memberIndex: 17, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfStructs$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfStructs$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.json#SimpleStruct$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)], targetClass: SimpleStruct::class)),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$RecursiveList', type: ShapeType::List_, memberName: 'RecursiveList', memberIndex: 18, listMember: new Schema(id: 'aws.protocoltests.json#ListOfKitchenSinks$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, targetClass: KitchenSink::class)),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$RecursiveMap', type: ShapeType::Map, memberName: 'RecursiveMap', memberIndex: 19, mapKey: new Schema(id: 'aws.protocoltests.json#MapOfKitchenSinks$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.json#MapOfKitchenSinks$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, targetClass: KitchenSink::class)),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$RecursiveStruct', type: ShapeType::Structure, memberName: 'RecursiveStruct', memberIndex: 20, targetClass: KitchenSink::class),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$SimpleStruct', type: ShapeType::Structure, memberName: 'SimpleStruct', memberIndex: 21, members: [new Schema(id: 'aws.protocoltests.json#SimpleStruct$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)], targetClass: SimpleStruct::class),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$String', type: ShapeType::String_, memberName: 'String', memberIndex: 22),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$StructWithJsonName', type: ShapeType::Structure, memberName: 'StructWithJsonName', memberIndex: 23, members: [new Schema(id: 'aws.protocoltests.json#StructWithJsonName$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0, traits: [12 => 'RenamedMember'])], targetClass: StructWithJsonName::class),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$Timestamp', type: ShapeType::Timestamp, memberName: 'Timestamp', memberIndex: 24),
                new Schema(id: 'aws.protocoltests.json#KitchenSink$UnixTimestamp', type: ShapeType::Timestamp, memberName: 'UnixTimestamp', memberIndex: 25, traits: [4 => 'epoch-seconds'])
            ],
            targetClass: KitchenSink::class
        );
    }

    public static function nullOperationInputOutput(): Schema
    {
        if (isset(self::$NullOperationInputOutput)) {
            return self::$NullOperationInputOutput;
        }

        return self::$NullOperationInputOutput = new Schema(
            id: 'aws.protocoltests.json#NullOperationInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#NullOperationInputOutput$string', type: ShapeType::String_, memberName: 'string', memberIndex: 0)
            ],
            targetClass: NullOperationInputOutput::class
        );
    }

    public static function operationWithOptionalInputOutputInput(): Schema
    {
        if (isset(self::$OperationWithOptionalInputOutputInput)) {
            return self::$OperationWithOptionalInputOutputInput;
        }

        return self::$OperationWithOptionalInputOutputInput = new Schema(
            id: 'aws.protocoltests.json#OperationWithOptionalInputOutputInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#OperationWithOptionalInputOutputInput$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)
            ],
            targetClass: OperationWithOptionalInputOutputInput::class
        );
    }

    public static function operationWithOptionalInputOutputOutput(): Schema
    {
        if (isset(self::$OperationWithOptionalInputOutputOutput)) {
            return self::$OperationWithOptionalInputOutputOutput;
        }

        return self::$OperationWithOptionalInputOutputOutput = new Schema(
            id: 'aws.protocoltests.json#OperationWithOptionalInputOutputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#OperationWithOptionalInputOutputOutput$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)
            ],
            targetClass: OperationWithOptionalInputOutputOutput::class
        );
    }

    public static function putAndGetInlineDocumentsInputOutput(): Schema
    {
        if (isset(self::$PutAndGetInlineDocumentsInputOutput)) {
            return self::$PutAndGetInlineDocumentsInputOutput;
        }

        return self::$PutAndGetInlineDocumentsInputOutput = new Schema(
            id: 'aws.protocoltests.json#PutAndGetInlineDocumentsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#PutAndGetInlineDocumentsInputOutput$inlineDocument', type: ShapeType::Document, memberName: 'inlineDocument', memberIndex: 0)
            ],
            targetClass: PutAndGetInlineDocumentsInputOutput::class
        );
    }

    public static function putWithContentEncodingInput(): Schema
    {
        if (isset(self::$PutWithContentEncodingInput)) {
            return self::$PutWithContentEncodingInput;
        }

        return self::$PutWithContentEncodingInput = new Schema(
            id: 'aws.protocoltests.json#PutWithContentEncodingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#PutWithContentEncodingInput$encoding', type: ShapeType::String_, memberName: 'encoding', memberIndex: 0, traits: [5 => 'Content-Encoding']),
                new Schema(id: 'aws.protocoltests.json#PutWithContentEncodingInput$data', type: ShapeType::String_, memberName: 'data', memberIndex: 1)
            ],
            targetClass: PutWithContentEncodingInput::class
        );
    }

    public static function simpleScalarPropertiesInputOutput(): Schema
    {
        if (isset(self::$SimpleScalarPropertiesInputOutput)) {
            return self::$SimpleScalarPropertiesInputOutput;
        }

        return self::$SimpleScalarPropertiesInputOutput = new Schema(
            id: 'aws.protocoltests.json#SimpleScalarPropertiesInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#SimpleScalarPropertiesInputOutput$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json#SimpleScalarPropertiesInputOutput$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 1)
            ],
            targetClass: SimpleScalarPropertiesInputOutput::class
        );
    }

    public static function simpleStruct(): Schema
    {
        if (isset(self::$SimpleStruct)) {
            return self::$SimpleStruct;
        }

        return self::$SimpleStruct = new Schema(
            id: 'aws.protocoltests.json#SimpleStruct',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#SimpleStruct$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0)
            ],
            targetClass: SimpleStruct::class
        );
    }

    public static function sparseNullsOperationInputOutput(): Schema
    {
        if (isset(self::$SparseNullsOperationInputOutput)) {
            return self::$SparseNullsOperationInputOutput;
        }

        return self::$SparseNullsOperationInputOutput = new Schema(
            id: 'aws.protocoltests.json#SparseNullsOperationInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#SparseNullsOperationInputOutput$sparseStringList', type: ShapeType::List_, memberName: 'sparseStringList', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#SparseStringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [15 => true]),
                new Schema(id: 'aws.protocoltests.json#SparseNullsOperationInputOutput$sparseStringMap', type: ShapeType::Map, memberName: 'sparseStringMap', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#SparseStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#SparseStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [15 => true])
            ],
            targetClass: SparseNullsOperationInputOutput::class
        );
    }

    public static function structWithJsonName(): Schema
    {
        if (isset(self::$StructWithJsonName)) {
            return self::$StructWithJsonName;
        }

        return self::$StructWithJsonName = new Schema(
            id: 'aws.protocoltests.json#StructWithJsonName',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#StructWithJsonName$Value', type: ShapeType::String_, memberName: 'Value', memberIndex: 0, traits: [12 => 'RenamedMember'])
            ],
            targetClass: StructWithJsonName::class
        );
    }

    public static function unionInputOutput(): Schema
    {
        if (isset(self::$UnionInputOutput)) {
            return self::$UnionInputOutput;
        }

        return self::$UnionInputOutput = new Schema(
            id: 'aws.protocoltests.json#UnionInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json#UnionInputOutput$contents', type: ShapeType::Union, memberName: 'contents', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.json#MyUnion$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0), new Schema(id: 'aws.protocoltests.json#MyUnion$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1), new Schema(id: 'aws.protocoltests.json#MyUnion$numberValue', type: ShapeType::Integer, memberName: 'numberValue', memberIndex: 2), new Schema(id: 'aws.protocoltests.json#MyUnion$blobValue', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 3), new Schema(id: 'aws.protocoltests.json#MyUnion$timestampValue', type: ShapeType::Timestamp, memberName: 'timestampValue', memberIndex: 4), new Schema(id: 'aws.protocoltests.json#MyUnion$enumValue', type: ShapeType::Enum, memberName: 'enumValue', memberIndex: 5, targetClass: FooEnum::class), new Schema(id: 'aws.protocoltests.json#MyUnion$listValue', type: ShapeType::List_, memberName: 'listValue', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), new Schema(id: 'aws.protocoltests.json#MyUnion$mapValue', type: ShapeType::Map, memberName: 'mapValue', memberIndex: 7, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)), new Schema(id: 'aws.protocoltests.json#MyUnion$structureValue', type: ShapeType::Structure, memberName: 'structureValue', memberIndex: 8, targetClass: GreetingStruct::class)], targetClass: MyUnion::class)
            ],
            targetClass: UnionInputOutput::class
        );
    }

    public static function greetingStruct(): Schema
    {
        if (isset(self::$GreetingStruct)) {
            return self::$GreetingStruct;
        }

        return self::$GreetingStruct = new Schema(
            id: 'aws.protocoltests.shared#GreetingStruct',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)
            ],
            targetClass: GreetingStruct::class
        );
    }

    public static function myUnion(): Schema
    {
        if (isset(self::$MyUnion)) {
            return self::$MyUnion;
        }

        return self::$MyUnion = new Schema(
            id: 'aws.protocoltests.json#MyUnion',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.json#MyUnion$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json#MyUnion$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.json#MyUnion$numberValue', type: ShapeType::Integer, memberName: 'numberValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.json#MyUnion$blobValue', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.json#MyUnion$timestampValue', type: ShapeType::Timestamp, memberName: 'timestampValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.json#MyUnion$enumValue', type: ShapeType::Enum, memberName: 'enumValue', memberIndex: 5, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.json#MyUnion$listValue', type: ShapeType::List_, memberName: 'listValue', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.json#MyUnion$mapValue', type: ShapeType::Map, memberName: 'mapValue', memberIndex: 7, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.json#MyUnion$structureValue', type: ShapeType::Structure, memberName: 'structureValue', memberIndex: 8, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class)
            ],
            targetClass: MyUnion::class
        );
    }

    public static function fooEnum(): Schema
    {
        if (isset(self::$FooEnum)) {
            return self::$FooEnum;
        }

        return self::$FooEnum = new Schema(
            id: 'aws.protocoltests.shared#FooEnum',
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
            id: 'aws.protocoltests.shared#IntegerEnum',
            type: ShapeType::IntEnum,
            targetClass: IntegerEnum::class
        );
    }

}
