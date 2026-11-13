<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsQuery\Exception\ComplexErrorException;
use Smithy\ProtocolTests\AwsQuery\Exception\CustomCodeErrorException;
use Smithy\ProtocolTests\AwsQuery\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\AwsQuery\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\AwsQuery\Input\HostLabelInput;
use Smithy\ProtocolTests\AwsQuery\Input\NestedStructuresInput;
use Smithy\ProtocolTests\AwsQuery\Input\NoInputAndOutputInput;
use Smithy\ProtocolTests\AwsQuery\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\AwsQuery\Input\QueryIdempotencyTokenAutoFillInput;
use Smithy\ProtocolTests\AwsQuery\Input\QueryListsInput;
use Smithy\ProtocolTests\AwsQuery\Input\QueryMapsInput;
use Smithy\ProtocolTests\AwsQuery\Input\QueryTimestampsInput;
use Smithy\ProtocolTests\AwsQuery\Input\SimpleInputParamsInput;
use Smithy\ProtocolTests\AwsQuery\Output\DatetimeOffsetsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\EmptyInputAndEmptyOutputOutput;
use Smithy\ProtocolTests\AwsQuery\Output\FlattenedXmlMapOutput;
use Smithy\ProtocolTests\AwsQuery\Output\FlattenedXmlMapWithXmlNameOutput;
use Smithy\ProtocolTests\AwsQuery\Output\FlattenedXmlMapWithXmlNamespaceOutput;
use Smithy\ProtocolTests\AwsQuery\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\IgnoresWrappingXmlNameOutput;
use Smithy\ProtocolTests\AwsQuery\Output\NoInputAndOutputOutput;
use Smithy\ProtocolTests\AwsQuery\Output\RecursiveXmlShapesOutput;
use Smithy\ProtocolTests\AwsQuery\Output\SimpleScalarXmlPropertiesOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlBlobsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlEnumsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlIntEnumsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlListsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlMapsOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlMapsXmlNameOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlNamespacesOutput;
use Smithy\ProtocolTests\AwsQuery\Output\XmlTimestampsOutput;
use Smithy\ProtocolTests\AwsQuery\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\AwsQuery\Shape\FooEnum;
use Smithy\ProtocolTests\AwsQuery\Shape\GreetingStruct;
use Smithy\ProtocolTests\AwsQuery\Shape\IntegerEnum;
use Smithy\ProtocolTests\AwsQuery\Shape\NestedStructWithList;
use Smithy\ProtocolTests\AwsQuery\Shape\NestedStructWithMap;
use Smithy\ProtocolTests\AwsQuery\Shape\RecursiveXmlShapesOutputNested1;
use Smithy\ProtocolTests\AwsQuery\Shape\RecursiveXmlShapesOutputNested2;
use Smithy\ProtocolTests\AwsQuery\Shape\StructArg;
use Smithy\ProtocolTests\AwsQuery\Shape\StructureListMember;
use Smithy\ProtocolTests\AwsQuery\Shape\XmlNamespaceNested;

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
    public static Schema $CustomCodeErrorException;
    public static Schema $DatetimeOffsetsOutput;
    public static Schema $EmptyInputAndEmptyOutputInput;
    public static Schema $EmptyInputAndEmptyOutputOutput;
    public static Schema $FlattenedXmlMapOutput;
    public static Schema $FlattenedXmlMapWithXmlNameOutput;
    public static Schema $FlattenedXmlMapWithXmlNamespaceOutput;
    public static Schema $FractionalSecondsOutput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $HostLabelInput;
    public static Schema $IgnoresWrappingXmlNameOutput;
    public static Schema $InvalidGreetingException;
    public static Schema $NestedStructWithList;
    public static Schema $NestedStructWithMap;
    public static Schema $NestedStructuresInput;
    public static Schema $NoInputAndOutputInput;
    public static Schema $NoInputAndOutputOutput;
    public static Schema $PutWithContentEncodingInput;
    public static Schema $QueryIdempotencyTokenAutoFillInput;
    public static Schema $QueryListsInput;
    public static Schema $QueryMapsInput;
    public static Schema $QueryTimestampsInput;
    public static Schema $RecursiveXmlShapesOutput;
    public static Schema $RecursiveXmlShapesOutputNested1;
    public static Schema $RecursiveXmlShapesOutputNested2;
    public static Schema $SimpleInputParamsInput;
    public static Schema $SimpleScalarXmlPropertiesOutput;
    public static Schema $StructArg;
    public static Schema $StructureListMember;
    public static Schema $XmlBlobsOutput;
    public static Schema $XmlEnumsOutput;
    public static Schema $XmlIntEnumsOutput;
    public static Schema $XmlListsOutput;
    public static Schema $XmlMapsOutput;
    public static Schema $XmlMapsXmlNameOutput;
    public static Schema $XmlNamespaceNested;
    public static Schema $XmlNamespacesOutput;
    public static Schema $XmlTimestampsOutput;
    public static Schema $GreetingStruct;
    public static Schema $FooEnum;
    public static Schema $IntegerEnum;

    public static function complexErrorException(): Schema
    {
        if (isset(self::$ComplexErrorException)) {
            return self::$ComplexErrorException;
        }

        return self::$ComplexErrorException = new Schema(
            id: 'aws.protocoltests.query#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#ComplexError$TopLevel', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#ComplexError$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.query#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)], targetClass: ComplexNestedErrorData::class)
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
            id: 'aws.protocoltests.query#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)
            ],
            targetClass: ComplexNestedErrorData::class
        );
    }

    public static function customCodeErrorException(): Schema
    {
        if (isset(self::$CustomCodeErrorException)) {
            return self::$CustomCodeErrorException;
        }

        return self::$CustomCodeErrorException = new Schema(
            id: 'aws.protocoltests.query#CustomCodeError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#CustomCodeError$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: CustomCodeErrorException::class
        );
    }

    public static function datetimeOffsetsOutput(): Schema
    {
        if (isset(self::$DatetimeOffsetsOutput)) {
            return self::$DatetimeOffsetsOutput;
        }

        return self::$DatetimeOffsetsOutput = new Schema(
            id: 'aws.protocoltests.query#DatetimeOffsetsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#DatetimeOffsetsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
            ],
            targetClass: DatetimeOffsetsOutput::class
        );
    }

    public static function emptyInputAndEmptyOutputInput(): Schema
    {
        if (isset(self::$EmptyInputAndEmptyOutputInput)) {
            return self::$EmptyInputAndEmptyOutputInput;
        }

        return self::$EmptyInputAndEmptyOutputInput = new Schema(
            id: 'aws.protocoltests.query#EmptyInputAndEmptyOutputInput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyInputAndEmptyOutputInput::class
        );
    }

    public static function emptyInputAndEmptyOutputOutput(): Schema
    {
        if (isset(self::$EmptyInputAndEmptyOutputOutput)) {
            return self::$EmptyInputAndEmptyOutputOutput;
        }

        return self::$EmptyInputAndEmptyOutputOutput = new Schema(
            id: 'aws.protocoltests.query#EmptyInputAndEmptyOutputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyInputAndEmptyOutputOutput::class
        );
    }

    public static function flattenedXmlMapOutput(): Schema
    {
        if (isset(self::$FlattenedXmlMapOutput)) {
            return self::$FlattenedXmlMapOutput;
        }

        return self::$FlattenedXmlMapOutput = new Schema(
            id: 'aws.protocoltests.query#FlattenedXmlMapOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#FlattenedXmlMapOutput$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class), traits: [3 => true])
            ],
            targetClass: FlattenedXmlMapOutput::class
        );
    }

    public static function flattenedXmlMapWithXmlNameOutput(): Schema
    {
        if (isset(self::$FlattenedXmlMapWithXmlNameOutput)) {
            return self::$FlattenedXmlMapWithXmlNameOutput;
        }

        return self::$FlattenedXmlMapWithXmlNameOutput = new Schema(
            id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNameOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNameOutput$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNameOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K']), mapValue: new Schema(id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNameOutputMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V']), traits: [0 => 'KVP', 3 => true])
            ],
            targetClass: FlattenedXmlMapWithXmlNameOutput::class
        );
    }

    public static function flattenedXmlMapWithXmlNamespaceOutput(): Schema
    {
        if (isset(self::$FlattenedXmlMapWithXmlNamespaceOutput)) {
            return self::$FlattenedXmlMapWithXmlNamespaceOutput;
        }

        return self::$FlattenedXmlMapWithXmlNamespaceOutput = new Schema(
            id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNamespaceOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNamespaceOutput$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNamespaceOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K', 1 => ['uri' => 'https://the-key.example.com']]), mapValue: new Schema(id: 'aws.protocoltests.query#FlattenedXmlMapWithXmlNamespaceOutputMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V', 1 => ['uri' => 'https://the-value.example.com']]), traits: [0 => 'KVP', 1 => ['uri' => 'https://the-member.example.com'], 3 => true])
            ],
            targetClass: FlattenedXmlMapWithXmlNamespaceOutput::class
        );
    }

    public static function fractionalSecondsOutput(): Schema
    {
        if (isset(self::$FractionalSecondsOutput)) {
            return self::$FractionalSecondsOutput;
        }

        return self::$FractionalSecondsOutput = new Schema(
            id: 'aws.protocoltests.query#FractionalSecondsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#FractionalSecondsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
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
            id: 'aws.protocoltests.query#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#GreetingWithErrorsOutput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
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
            id: 'aws.protocoltests.query#HostLabelInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#HostLabelInput$label', type: ShapeType::String_, memberName: 'label', memberIndex: 0, traits: [14 => true])
            ],
            targetClass: HostLabelInput::class
        );
    }

    public static function ignoresWrappingXmlNameOutput(): Schema
    {
        if (isset(self::$IgnoresWrappingXmlNameOutput)) {
            return self::$IgnoresWrappingXmlNameOutput;
        }

        return self::$IgnoresWrappingXmlNameOutput = new Schema(
            id: 'aws.protocoltests.query#IgnoresWrappingXmlNameOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#IgnoresWrappingXmlNameOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0)
            ], traits: [0 => 'IgnoreMe'],
            targetClass: IgnoresWrappingXmlNameOutput::class
        );
    }

    public static function invalidGreetingException(): Schema
    {
        if (isset(self::$InvalidGreetingException)) {
            return self::$InvalidGreetingException;
        }

        return self::$InvalidGreetingException = new Schema(
            id: 'aws.protocoltests.query#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#InvalidGreeting$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: InvalidGreetingException::class
        );
    }

    public static function nestedStructWithList(): Schema
    {
        if (isset(self::$NestedStructWithList)) {
            return self::$NestedStructWithList;
        }

        return self::$NestedStructWithList = new Schema(
            id: 'aws.protocoltests.query#NestedStructWithList',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#NestedStructWithList$ListArg', type: ShapeType::List_, memberName: 'ListArg', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))
            ],
            targetClass: NestedStructWithList::class
        );
    }

    public static function nestedStructWithMap(): Schema
    {
        if (isset(self::$NestedStructWithMap)) {
            return self::$NestedStructWithMap;
        }

        return self::$NestedStructWithMap = new Schema(
            id: 'aws.protocoltests.query#NestedStructWithMap',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#NestedStructWithMap$MapArg', type: ShapeType::Map, memberName: 'MapArg', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1))
            ],
            targetClass: NestedStructWithMap::class
        );
    }

    public static function nestedStructuresInput(): Schema
    {
        if (isset(self::$NestedStructuresInput)) {
            return self::$NestedStructuresInput;
        }

        return self::$NestedStructuresInput = new Schema(
            id: 'aws.protocoltests.query#NestedStructuresInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#NestedStructuresInput$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.query#StructArg$StringArg', type: ShapeType::String_, memberName: 'StringArg', memberIndex: 0), new Schema(id: 'aws.protocoltests.query#StructArg$OtherArg', type: ShapeType::Boolean, memberName: 'OtherArg', memberIndex: 1), new Schema(id: 'aws.protocoltests.query#StructArg$RecursiveArg', type: ShapeType::Structure, memberName: 'RecursiveArg', memberIndex: 2, targetClass: StructArg::class)], targetClass: StructArg::class)
            ],
            targetClass: NestedStructuresInput::class
        );
    }

    public static function noInputAndOutputInput(): Schema
    {
        if (isset(self::$NoInputAndOutputInput)) {
            return self::$NoInputAndOutputInput;
        }

        return self::$NoInputAndOutputInput = new Schema(
            id: 'aws.protocoltests.query#NoInputAndOutputInput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: NoInputAndOutputInput::class
        );
    }

    public static function noInputAndOutputOutput(): Schema
    {
        if (isset(self::$NoInputAndOutputOutput)) {
            return self::$NoInputAndOutputOutput;
        }

        return self::$NoInputAndOutputOutput = new Schema(
            id: 'aws.protocoltests.query#NoInputAndOutputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: NoInputAndOutputOutput::class
        );
    }

    public static function putWithContentEncodingInput(): Schema
    {
        if (isset(self::$PutWithContentEncodingInput)) {
            return self::$PutWithContentEncodingInput;
        }

        return self::$PutWithContentEncodingInput = new Schema(
            id: 'aws.protocoltests.query#PutWithContentEncodingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#PutWithContentEncodingInput$encoding', type: ShapeType::String_, memberName: 'encoding', memberIndex: 0, traits: [5 => 'Content-Encoding']),
                new Schema(id: 'aws.protocoltests.query#PutWithContentEncodingInput$data', type: ShapeType::String_, memberName: 'data', memberIndex: 1)
            ],
            targetClass: PutWithContentEncodingInput::class
        );
    }

    public static function queryIdempotencyTokenAutoFillInput(): Schema
    {
        if (isset(self::$QueryIdempotencyTokenAutoFillInput)) {
            return self::$QueryIdempotencyTokenAutoFillInput;
        }

        return self::$QueryIdempotencyTokenAutoFillInput = new Schema(
            id: 'aws.protocoltests.query#QueryIdempotencyTokenAutoFillInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#QueryIdempotencyTokenAutoFillInput$token', type: ShapeType::String_, memberName: 'token', memberIndex: 0, traits: [19 => true])
            ],
            targetClass: QueryIdempotencyTokenAutoFillInput::class
        );
    }

    public static function queryListsInput(): Schema
    {
        if (isset(self::$QueryListsInput)) {
            return self::$QueryListsInput;
        }

        return self::$QueryListsInput = new Schema(
            id: 'aws.protocoltests.query#QueryListsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#QueryListsInput$ListArg', type: ShapeType::List_, memberName: 'ListArg', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.query#QueryListsInput$ComplexListArg', type: ShapeType::List_, memberName: 'ComplexListArg', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#GreetingList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class)),
                new Schema(id: 'aws.protocoltests.query#QueryListsInput$FlattenedListArg', type: ShapeType::List_, memberName: 'FlattenedListArg', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.query#QueryListsInput$ListArgWithXmlNameMember', type: ShapeType::List_, memberName: 'ListArgWithXmlNameMember', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.query#ListWithXmlName$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item'])),
                new Schema(id: 'aws.protocoltests.query#QueryListsInput$FlattenedListArgWithXmlName', type: ShapeType::List_, memberName: 'FlattenedListArgWithXmlName', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.query#ListWithXmlName$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'Hi', 3 => true]),
                new Schema(id: 'aws.protocoltests.query#QueryListsInput$NestedWithList', type: ShapeType::Structure, memberName: 'NestedWithList', memberIndex: 5, members: [new Schema(id: 'aws.protocoltests.query#NestedStructWithList$ListArg', type: ShapeType::List_, memberName: 'ListArg', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))], targetClass: NestedStructWithList::class)
            ],
            targetClass: QueryListsInput::class
        );
    }

    public static function queryMapsInput(): Schema
    {
        if (isset(self::$QueryMapsInput)) {
            return self::$QueryMapsInput;
        }

        return self::$QueryMapsInput = new Schema(
            id: 'aws.protocoltests.query#QueryMapsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$MapArg', type: ShapeType::Map, memberName: 'MapArg', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$RenamedMapArg', type: ShapeType::Map, memberName: 'RenamedMapArg', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [0 => 'Foo']),
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$ComplexMapArg', type: ShapeType::Map, memberName: 'ComplexMapArg', memberIndex: 2, mapKey: new Schema(id: 'aws.protocoltests.query#ComplexMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.query#ComplexMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class)),
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$MapWithXmlMemberName', type: ShapeType::Map, memberName: 'MapWithXmlMemberName', memberIndex: 3, mapKey: new Schema(id: 'aws.protocoltests.query#MapWithXmlName$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K']), mapValue: new Schema(id: 'aws.protocoltests.query#MapWithXmlName$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V'])),
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$FlattenedMap', type: ShapeType::Map, memberName: 'FlattenedMap', memberIndex: 4, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$FlattenedMapWithXmlName', type: ShapeType::Map, memberName: 'FlattenedMapWithXmlName', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.query#MapWithXmlName$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K']), mapValue: new Schema(id: 'aws.protocoltests.query#MapWithXmlName$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V']), traits: [0 => 'Hi', 3 => true]),
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$MapOfLists', type: ShapeType::Map, memberName: 'MapOfLists', memberIndex: 6, mapKey: new Schema(id: 'aws.protocoltests.query#MapOfLists$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.query#MapOfLists$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.query#QueryMapsInput$NestedStructWithMap', type: ShapeType::Structure, memberName: 'NestedStructWithMap', memberIndex: 7, members: [new Schema(id: 'aws.protocoltests.query#NestedStructWithMap$MapArg', type: ShapeType::Map, memberName: 'MapArg', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1))], targetClass: NestedStructWithMap::class)
            ],
            targetClass: QueryMapsInput::class
        );
    }

    public static function queryTimestampsInput(): Schema
    {
        if (isset(self::$QueryTimestampsInput)) {
            return self::$QueryTimestampsInput;
        }

        return self::$QueryTimestampsInput = new Schema(
            id: 'aws.protocoltests.query#QueryTimestampsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#QueryTimestampsInput$normalFormat', type: ShapeType::Timestamp, memberName: 'normalFormat', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#QueryTimestampsInput$epochMember', type: ShapeType::Timestamp, memberName: 'epochMember', memberIndex: 1, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.query#QueryTimestampsInput$epochTarget', type: ShapeType::Timestamp, memberName: 'epochTarget', memberIndex: 2, traits: [4 => 'epoch-seconds'])
            ],
            targetClass: QueryTimestampsInput::class
        );
    }

    public static function recursiveXmlShapesOutput(): Schema
    {
        if (isset(self::$RecursiveXmlShapesOutput)) {
            return self::$RecursiveXmlShapesOutput;
        }

        return self::$RecursiveXmlShapesOutput = new Schema(
            id: 'aws.protocoltests.query#RecursiveXmlShapesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveXmlShapesOutputNested2::class)], targetClass: RecursiveXmlShapesOutputNested1::class)
            ],
            targetClass: RecursiveXmlShapesOutput::class
        );
    }

    public static function recursiveXmlShapesOutputNested1(): Schema
    {
        if (isset(self::$RecursiveXmlShapesOutputNested1)) {
            return self::$RecursiveXmlShapesOutputNested1;
        }

        return self::$RecursiveXmlShapesOutputNested1 = new Schema(
            id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0), new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, targetClass: RecursiveXmlShapesOutputNested1::class)], targetClass: RecursiveXmlShapesOutputNested2::class)
            ],
            targetClass: RecursiveXmlShapesOutputNested1::class
        );
    }

    public static function recursiveXmlShapesOutputNested2(): Schema
    {
        if (isset(self::$RecursiveXmlShapesOutputNested2)) {
            return self::$RecursiveXmlShapesOutputNested2;
        }

        return self::$RecursiveXmlShapesOutputNested2 = new Schema(
            id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested2',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveXmlShapesOutputNested2::class)], targetClass: RecursiveXmlShapesOutputNested1::class)
            ],
            targetClass: RecursiveXmlShapesOutputNested2::class
        );
    }

    public static function simpleInputParamsInput(): Schema
    {
        if (isset(self::$SimpleInputParamsInput)) {
            return self::$SimpleInputParamsInput;
        }

        return self::$SimpleInputParamsInput = new Schema(
            id: 'aws.protocoltests.query#SimpleInputParamsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$Bar', type: ShapeType::String_, memberName: 'Bar', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$Baz', type: ShapeType::Boolean, memberName: 'Baz', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$Bam', type: ShapeType::Integer, memberName: 'Bam', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$FloatValue', type: ShapeType::Float_, memberName: 'FloatValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$Boo', type: ShapeType::Double, memberName: 'Boo', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$Qux', type: ShapeType::Blob, memberName: 'Qux', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$FooEnum', type: ShapeType::Enum, memberName: 'FooEnum', memberIndex: 7, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.query#SimpleInputParamsInput$IntegerEnum', type: ShapeType::IntEnum, memberName: 'IntegerEnum', memberIndex: 8, targetClass: IntegerEnum::class)
            ],
            targetClass: SimpleInputParamsInput::class
        );
    }

    public static function simpleScalarXmlPropertiesOutput(): Schema
    {
        if (isset(self::$SimpleScalarXmlPropertiesOutput)) {
            return self::$SimpleScalarXmlPropertiesOutput;
        }

        return self::$SimpleScalarXmlPropertiesOutput = new Schema(
            id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$emptyStringValue', type: ShapeType::String_, memberName: 'emptyStringValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$trueBooleanValue', type: ShapeType::Boolean, memberName: 'trueBooleanValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$falseBooleanValue', type: ShapeType::Boolean, memberName: 'falseBooleanValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 7),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 8),
                new Schema(id: 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 9, traits: [0 => 'DoubleDribble'])
            ],
            targetClass: SimpleScalarXmlPropertiesOutput::class
        );
    }

    public static function structArg(): Schema
    {
        if (isset(self::$StructArg)) {
            return self::$StructArg;
        }

        return self::$StructArg = new Schema(
            id: 'aws.protocoltests.query#StructArg',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#StructArg$StringArg', type: ShapeType::String_, memberName: 'StringArg', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#StructArg$OtherArg', type: ShapeType::Boolean, memberName: 'OtherArg', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.query#StructArg$RecursiveArg', type: ShapeType::Structure, memberName: 'RecursiveArg', memberIndex: 2, targetClass: StructArg::class)
            ],
            targetClass: StructArg::class
        );
    }

    public static function structureListMember(): Schema
    {
        if (isset(self::$StructureListMember)) {
            return self::$StructureListMember;
        }

        return self::$StructureListMember = new Schema(
            id: 'aws.protocoltests.query#StructureListMember',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']),
                new Schema(id: 'aws.protocoltests.query#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])
            ],
            targetClass: StructureListMember::class
        );
    }

    public static function xmlBlobsOutput(): Schema
    {
        if (isset(self::$XmlBlobsOutput)) {
            return self::$XmlBlobsOutput;
        }

        return self::$XmlBlobsOutput = new Schema(
            id: 'aws.protocoltests.query#XmlBlobsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlBlobsOutput$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 0)
            ],
            targetClass: XmlBlobsOutput::class
        );
    }

    public static function xmlEnumsOutput(): Schema
    {
        if (isset(self::$XmlEnumsOutput)) {
            return self::$XmlEnumsOutput;
        }

        return self::$XmlEnumsOutput = new Schema(
            id: 'aws.protocoltests.query#XmlEnumsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlEnumsOutput$fooEnum1', type: ShapeType::Enum, memberName: 'fooEnum1', memberIndex: 0, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.query#XmlEnumsOutput$fooEnum2', type: ShapeType::Enum, memberName: 'fooEnum2', memberIndex: 1, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.query#XmlEnumsOutput$fooEnum3', type: ShapeType::Enum, memberName: 'fooEnum3', memberIndex: 2, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.query#XmlEnumsOutput$fooEnumList', type: ShapeType::List_, memberName: 'fooEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.query#XmlEnumsOutput$fooEnumSet', type: ShapeType::List_, memberName: 'fooEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumSet$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.query#XmlEnumsOutput$fooEnumMap', type: ShapeType::Map, memberName: 'fooEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))
            ],
            targetClass: XmlEnumsOutput::class
        );
    }

    public static function xmlIntEnumsOutput(): Schema
    {
        if (isset(self::$XmlIntEnumsOutput)) {
            return self::$XmlIntEnumsOutput;
        }

        return self::$XmlIntEnumsOutput = new Schema(
            id: 'aws.protocoltests.query#XmlIntEnumsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlIntEnumsOutput$intEnum1', type: ShapeType::IntEnum, memberName: 'intEnum1', memberIndex: 0, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.query#XmlIntEnumsOutput$intEnum2', type: ShapeType::IntEnum, memberName: 'intEnum2', memberIndex: 1, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.query#XmlIntEnumsOutput$intEnum3', type: ShapeType::IntEnum, memberName: 'intEnum3', memberIndex: 2, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.query#XmlIntEnumsOutput$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.query#XmlIntEnumsOutput$intEnumSet', type: ShapeType::List_, memberName: 'intEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumSet$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.query#XmlIntEnumsOutput$intEnumMap', type: ShapeType::Map, memberName: 'intEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$value', type: ShapeType::IntEnum, memberName: 'value', memberIndex: 1, targetClass: IntegerEnum::class))
            ],
            targetClass: XmlIntEnumsOutput::class
        );
    }

    public static function xmlListsOutput(): Schema
    {
        if (isset(self::$XmlListsOutput)) {
            return self::$XmlListsOutput;
        }

        return self::$XmlListsOutput = new Schema(
            id: 'aws.protocoltests.query#XmlListsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$renamedListMembers', type: ShapeType::List_, memberName: 'renamedListMembers', memberIndex: 8, listMember: new Schema(id: 'aws.protocoltests.query#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'renamed']),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$flattenedList', type: ShapeType::List_, memberName: 'flattenedList', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.query#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$flattenedList2', type: ShapeType::List_, memberName: 'flattenedList2', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.query#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'customName', 3 => true]),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$flattenedListWithMemberNamespace', type: ShapeType::List_, memberName: 'flattenedListWithMemberNamespace', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.query#ListWithMemberNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'https://xml-member.example.com']]), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$flattenedListWithNamespace', type: ShapeType::List_, memberName: 'flattenedListWithNamespace', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.query#ListWithNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.query#XmlListsOutput$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 13, listMember: new Schema(id: 'aws.protocoltests.query#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.query#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.query#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [0 => 'myStructureList'])
            ],
            targetClass: XmlListsOutput::class
        );
    }

    public static function xmlMapsOutput(): Schema
    {
        if (isset(self::$XmlMapsOutput)) {
            return self::$XmlMapsOutput;
        }

        return self::$XmlMapsOutput = new Schema(
            id: 'aws.protocoltests.query#XmlMapsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlMapsOutput$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.query#XmlMapsOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.query#XmlMapsOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlMapsOutput::class
        );
    }

    public static function xmlMapsXmlNameOutput(): Schema
    {
        if (isset(self::$XmlMapsXmlNameOutput)) {
            return self::$XmlMapsXmlNameOutput;
        }

        return self::$XmlMapsXmlNameOutput = new Schema(
            id: 'aws.protocoltests.query#XmlMapsXmlNameOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlMapsXmlNameOutput$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.query#XmlMapsXmlNameOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'Attribute']), mapValue: new Schema(id: 'aws.protocoltests.query#XmlMapsXmlNameOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], traits: [0 => 'Setting'], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlMapsXmlNameOutput::class
        );
    }

    public static function xmlNamespaceNested(): Schema
    {
        if (isset(self::$XmlNamespaceNested)) {
            return self::$XmlNamespaceNested;
        }

        return self::$XmlNamespaceNested = new Schema(
            id: 'aws.protocoltests.query#XmlNamespaceNested',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlNamespaceNested$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [1 => ['uri' => 'http://baz.com', 'prefix' => 'baz']]),
                new Schema(id: 'aws.protocoltests.query#XmlNamespaceNested$values', type: ShapeType::List_, memberName: 'values', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.query#XmlNamespacedList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'http://bux.com']]), traits: [1 => ['uri' => 'http://qux.com']])
            ], traits: [1 => ['uri' => 'http://boo.com']],
            targetClass: XmlNamespaceNested::class
        );
    }

    public static function xmlNamespacesOutput(): Schema
    {
        if (isset(self::$XmlNamespacesOutput)) {
            return self::$XmlNamespacesOutput;
        }

        return self::$XmlNamespacesOutput = new Schema(
            id: 'aws.protocoltests.query#XmlNamespacesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlNamespacesOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.query#XmlNamespaceNested$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [1 => ['uri' => 'http://baz.com', 'prefix' => 'baz']]), new Schema(id: 'aws.protocoltests.query#XmlNamespaceNested$values', type: ShapeType::List_, memberName: 'values', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.query#XmlNamespacedList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'http://bux.com']]), traits: [1 => ['uri' => 'http://qux.com']])], traits: [1 => ['uri' => 'http://boo.com']], targetClass: XmlNamespaceNested::class)
            ], traits: [1 => ['uri' => 'http://foo.com']],
            targetClass: XmlNamespacesOutput::class
        );
    }

    public static function xmlTimestampsOutput(): Schema
    {
        if (isset(self::$XmlTimestampsOutput)) {
            return self::$XmlTimestampsOutput;
        }

        return self::$XmlTimestampsOutput = new Schema(
            id: 'aws.protocoltests.query#XmlTimestampsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.query#XmlTimestampsOutput$normal', type: ShapeType::Timestamp, memberName: 'normal', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.query#XmlTimestampsOutput$dateTime', type: ShapeType::Timestamp, memberName: 'dateTime', memberIndex: 1, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.query#XmlTimestampsOutput$dateTimeOnTarget', type: ShapeType::Timestamp, memberName: 'dateTimeOnTarget', memberIndex: 2, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.query#XmlTimestampsOutput$epochSeconds', type: ShapeType::Timestamp, memberName: 'epochSeconds', memberIndex: 3, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.query#XmlTimestampsOutput$epochSecondsOnTarget', type: ShapeType::Timestamp, memberName: 'epochSecondsOnTarget', memberIndex: 4, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.query#XmlTimestampsOutput$httpDate', type: ShapeType::Timestamp, memberName: 'httpDate', memberIndex: 5, traits: [4 => 'http-date']),
                new Schema(id: 'aws.protocoltests.query#XmlTimestampsOutput$httpDateOnTarget', type: ShapeType::Timestamp, memberName: 'httpDateOnTarget', memberIndex: 6, traits: [4 => 'http-date'])
            ],
            targetClass: XmlTimestampsOutput::class
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
