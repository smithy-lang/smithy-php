<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\Ec2Query\Exception\ComplexErrorException;
use Smithy\ProtocolTests\Ec2Query\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\Ec2Query\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\Ec2Query\Input\HostLabelInput;
use Smithy\ProtocolTests\Ec2Query\Input\NestedStructuresInput;
use Smithy\ProtocolTests\Ec2Query\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryIdempotencyTokenAutoFillInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryListsInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryTimestampsInput;
use Smithy\ProtocolTests\Ec2Query\Input\SimpleInputParamsInput;
use Smithy\ProtocolTests\Ec2Query\Output\DatetimeOffsetsOutput;
use Smithy\ProtocolTests\Ec2Query\Output\EmptyInputAndEmptyOutputOutput;
use Smithy\ProtocolTests\Ec2Query\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\Ec2Query\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\Ec2Query\Output\IgnoresWrappingXmlNameOutput;
use Smithy\ProtocolTests\Ec2Query\Output\NoInputAndOutputOutput;
use Smithy\ProtocolTests\Ec2Query\Output\RecursiveXmlShapesOutput;
use Smithy\ProtocolTests\Ec2Query\Output\SimpleScalarXmlPropertiesOutput;
use Smithy\ProtocolTests\Ec2Query\Output\XmlBlobsOutput;
use Smithy\ProtocolTests\Ec2Query\Output\XmlEnumsOutput;
use Smithy\ProtocolTests\Ec2Query\Output\XmlIntEnumsOutput;
use Smithy\ProtocolTests\Ec2Query\Output\XmlListsOutput;
use Smithy\ProtocolTests\Ec2Query\Output\XmlNamespacesOutput;
use Smithy\ProtocolTests\Ec2Query\Output\XmlTimestampsOutput;
use Smithy\ProtocolTests\Ec2Query\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\Ec2Query\Shape\FooEnum;
use Smithy\ProtocolTests\Ec2Query\Shape\GreetingStruct;
use Smithy\ProtocolTests\Ec2Query\Shape\IntegerEnum;
use Smithy\ProtocolTests\Ec2Query\Shape\NestedStructWithList;
use Smithy\ProtocolTests\Ec2Query\Shape\RecursiveXmlShapesOutputNested1;
use Smithy\ProtocolTests\Ec2Query\Shape\RecursiveXmlShapesOutputNested2;
use Smithy\ProtocolTests\Ec2Query\Shape\StructArg;
use Smithy\ProtocolTests\Ec2Query\Shape\StructureListMember;
use Smithy\ProtocolTests\Ec2Query\Shape\XmlNamespaceNested;

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
    public static Schema $DatetimeOffsetsOutput;
    public static Schema $EmptyInputAndEmptyOutputInput;
    public static Schema $EmptyInputAndEmptyOutputOutput;
    public static Schema $FractionalSecondsOutput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $HostLabelInput;
    public static Schema $IgnoresWrappingXmlNameOutput;
    public static Schema $InvalidGreetingException;
    public static Schema $NestedStructWithList;
    public static Schema $NestedStructuresInput;
    public static Schema $NoInputAndOutputOutput;
    public static Schema $PutWithContentEncodingInput;
    public static Schema $QueryIdempotencyTokenAutoFillInput;
    public static Schema $QueryListsInput;
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
            id: 'aws.protocoltests.ec2#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#ComplexError$TopLevel', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.ec2#ComplexError$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 1, members: self::complexNestedErrorData()->getMembers(), targetClass: ComplexNestedErrorData::class)
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
            id: 'aws.protocoltests.ec2#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)
            ],
            targetClass: ComplexNestedErrorData::class
        );
    }

    public static function datetimeOffsetsOutput(): Schema
    {
        if (isset(self::$DatetimeOffsetsOutput)) {
            return self::$DatetimeOffsetsOutput;
        }

        return self::$DatetimeOffsetsOutput = new Schema(
            id: 'aws.protocoltests.ec2#DatetimeOffsetsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#DatetimeOffsetsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
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
            id: 'aws.protocoltests.ec2#EmptyInputAndEmptyOutputInput',
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
            id: 'aws.protocoltests.ec2#EmptyInputAndEmptyOutputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyInputAndEmptyOutputOutput::class
        );
    }

    public static function fractionalSecondsOutput(): Schema
    {
        if (isset(self::$FractionalSecondsOutput)) {
            return self::$FractionalSecondsOutput;
        }

        return self::$FractionalSecondsOutput = new Schema(
            id: 'aws.protocoltests.ec2#FractionalSecondsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#FractionalSecondsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
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
            id: 'aws.protocoltests.ec2#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#GreetingWithErrorsOutput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
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
            id: 'aws.protocoltests.ec2#HostLabelInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#HostLabelInput$label', type: ShapeType::String_, memberName: 'label', memberIndex: 0, traits: [14 => true])
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
            id: 'aws.protocoltests.ec2#IgnoresWrappingXmlNameOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#IgnoresWrappingXmlNameOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0)
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
            id: 'aws.protocoltests.ec2#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#InvalidGreeting$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
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
            id: 'aws.protocoltests.ec2#NestedStructWithList',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#NestedStructWithList$ListArg', type: ShapeType::List_, memberName: 'ListArg', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))
            ],
            targetClass: NestedStructWithList::class
        );
    }

    public static function nestedStructuresInput(): Schema
    {
        if (isset(self::$NestedStructuresInput)) {
            return self::$NestedStructuresInput;
        }

        return self::$NestedStructuresInput = new Schema(
            id: 'aws.protocoltests.ec2#NestedStructuresInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#NestedStructuresInput$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 0, members: self::structArg()->getMembers(), targetClass: StructArg::class)
            ],
            targetClass: NestedStructuresInput::class
        );
    }

    public static function noInputAndOutputOutput(): Schema
    {
        if (isset(self::$NoInputAndOutputOutput)) {
            return self::$NoInputAndOutputOutput;
        }

        return self::$NoInputAndOutputOutput = new Schema(
            id: 'aws.protocoltests.ec2#NoInputAndOutputOutput',
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
            id: 'aws.protocoltests.ec2#PutWithContentEncodingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#PutWithContentEncodingInput$encoding', type: ShapeType::String_, memberName: 'encoding', memberIndex: 0, traits: [5 => 'Content-Encoding']),
                new Schema(id: 'aws.protocoltests.ec2#PutWithContentEncodingInput$data', type: ShapeType::String_, memberName: 'data', memberIndex: 1)
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
            id: 'aws.protocoltests.ec2#QueryIdempotencyTokenAutoFillInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#QueryIdempotencyTokenAutoFillInput$token', type: ShapeType::String_, memberName: 'token', memberIndex: 0, traits: [19 => true])
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
            id: 'aws.protocoltests.ec2#QueryListsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#QueryListsInput$ListArg', type: ShapeType::List_, memberName: 'ListArg', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.ec2#QueryListsInput$ComplexListArg', type: ShapeType::List_, memberName: 'ComplexListArg', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#GreetingList$member'] ??= new Schema(id: 'aws.protocoltests.shared#GreetingList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: self::greetingStruct()->getMembers(), targetClass: GreetingStruct::class)),
                new Schema(id: 'aws.protocoltests.ec2#QueryListsInput$ListArgWithXmlNameMember', type: ShapeType::List_, memberName: 'ListArgWithXmlNameMember', memberIndex: 2, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#ListWithXmlName$member'] ??= new Schema(id: 'aws.protocoltests.ec2#ListWithXmlName$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item'])),
                new Schema(id: 'aws.protocoltests.ec2#QueryListsInput$ListArgWithXmlName', type: ShapeType::List_, memberName: 'ListArgWithXmlName', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#ListWithXmlName$member'] ??= new Schema(id: 'aws.protocoltests.ec2#ListWithXmlName$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'Hi']),
                new Schema(id: 'aws.protocoltests.ec2#QueryListsInput$NestedWithList', type: ShapeType::Structure, memberName: 'NestedWithList', memberIndex: 4, members: self::nestedStructWithList()->getMembers(), targetClass: NestedStructWithList::class)
            ],
            targetClass: QueryListsInput::class
        );
    }

    public static function queryTimestampsInput(): Schema
    {
        if (isset(self::$QueryTimestampsInput)) {
            return self::$QueryTimestampsInput;
        }

        return self::$QueryTimestampsInput = new Schema(
            id: 'aws.protocoltests.ec2#QueryTimestampsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#QueryTimestampsInput$normalFormat', type: ShapeType::Timestamp, memberName: 'normalFormat', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.ec2#QueryTimestampsInput$epochMember', type: ShapeType::Timestamp, memberName: 'epochMember', memberIndex: 1, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.ec2#QueryTimestampsInput$epochTarget', type: ShapeType::Timestamp, memberName: 'epochTarget', memberIndex: 2, traits: [4 => 'epoch-seconds'])
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
            id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveXmlShapesOutputNested2::class)], targetClass: RecursiveXmlShapesOutputNested1::class)
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
            id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0), new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, targetClass: RecursiveXmlShapesOutputNested1::class)], targetClass: RecursiveXmlShapesOutputNested2::class)
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
            id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested2',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveXmlShapesOutputNested2::class)], targetClass: RecursiveXmlShapesOutputNested1::class)
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
            id: 'aws.protocoltests.ec2#SimpleInputParamsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$Bar', type: ShapeType::String_, memberName: 'Bar', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$Baz', type: ShapeType::Boolean, memberName: 'Baz', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$Bam', type: ShapeType::Integer, memberName: 'Bam', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$FloatValue', type: ShapeType::Float_, memberName: 'FloatValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$Boo', type: ShapeType::Double, memberName: 'Boo', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$Qux', type: ShapeType::Blob, memberName: 'Qux', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$FooEnum', type: ShapeType::Enum, memberName: 'FooEnum', memberIndex: 7, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$HasQueryName', type: ShapeType::String_, memberName: 'HasQueryName', memberIndex: 8, traits: [27 => 'HasQueryName'], traitsById: ['aws.protocols#ec2QueryName' => 'HasQueryName'], directTraitsById: ['aws.protocols#ec2QueryName' => 'HasQueryName']),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$HasQueryAndXmlName', type: ShapeType::String_, memberName: 'HasQueryAndXmlName', memberIndex: 9, traits: [0 => 'hasQueryAndXmlName', 27 => 'HasQueryAndXmlName'], traitsById: ['aws.protocols#ec2QueryName' => 'HasQueryAndXmlName'], directTraitsById: ['aws.protocols#ec2QueryName' => 'HasQueryAndXmlName']),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$UsesXmlName', type: ShapeType::String_, memberName: 'UsesXmlName', memberIndex: 10, traits: [0 => 'usesXmlName']),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$DistinctQueryName', type: ShapeType::String_, memberName: 'DistinctQueryName', memberIndex: 11, traits: [27 => 'QueryName'], traitsById: ['aws.protocols#ec2QueryName' => 'QueryName'], directTraitsById: ['aws.protocols#ec2QueryName' => 'QueryName']),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$DistinctQueryAndXmlName', type: ShapeType::String_, memberName: 'DistinctQueryAndXmlName', memberIndex: 12, traits: [0 => 'xmlAndQueryName', 27 => 'queryAndXmlName'], traitsById: ['aws.protocols#ec2QueryName' => 'queryAndXmlName'], directTraitsById: ['aws.protocols#ec2QueryName' => 'queryAndXmlName']),
                new Schema(id: 'aws.protocoltests.ec2#SimpleInputParamsInput$DistinctXmlName', type: ShapeType::String_, memberName: 'DistinctXmlName', memberIndex: 13, traits: [0 => 'xmlNameOnly'])
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
            id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0, traits: [27 => 'IgnoreMe'], traitsById: ['aws.protocols#ec2QueryName' => 'IgnoreMe'], directTraitsById: ['aws.protocols#ec2QueryName' => 'IgnoreMe']),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$emptyStringValue', type: ShapeType::String_, memberName: 'emptyStringValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$trueBooleanValue', type: ShapeType::Boolean, memberName: 'trueBooleanValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$falseBooleanValue', type: ShapeType::Boolean, memberName: 'falseBooleanValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 7),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 8),
                new Schema(id: 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 9, traits: [0 => 'DoubleDribble'])
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
            id: 'aws.protocoltests.ec2#StructArg',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#StructArg$StringArg', type: ShapeType::String_, memberName: 'StringArg', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.ec2#StructArg$OtherArg', type: ShapeType::Boolean, memberName: 'OtherArg', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.ec2#StructArg$RecursiveArg', type: ShapeType::Structure, memberName: 'RecursiveArg', memberIndex: 2, targetClass: StructArg::class)
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
            id: 'aws.protocoltests.ec2#StructureListMember',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']),
                new Schema(id: 'aws.protocoltests.ec2#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])
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
            id: 'aws.protocoltests.ec2#XmlBlobsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#XmlBlobsOutput$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 0)
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
            id: 'aws.protocoltests.ec2#XmlEnumsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#XmlEnumsOutput$fooEnum1', type: ShapeType::Enum, memberName: 'fooEnum1', memberIndex: 0, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.ec2#XmlEnumsOutput$fooEnum2', type: ShapeType::Enum, memberName: 'fooEnum2', memberIndex: 1, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.ec2#XmlEnumsOutput$fooEnum3', type: ShapeType::Enum, memberName: 'fooEnum3', memberIndex: 2, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.ec2#XmlEnumsOutput$fooEnumList', type: ShapeType::List_, memberName: 'fooEnumList', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.ec2#XmlEnumsOutput$fooEnumSet', type: ShapeType::List_, memberName: 'fooEnumSet', memberIndex: 4, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumSet$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.ec2#XmlEnumsOutput$fooEnumMap', type: ShapeType::Map, memberName: 'fooEnumMap', memberIndex: 5, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))
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
            id: 'aws.protocoltests.ec2#XmlIntEnumsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#XmlIntEnumsOutput$intEnum1', type: ShapeType::IntEnum, memberName: 'intEnum1', memberIndex: 0, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.ec2#XmlIntEnumsOutput$intEnum2', type: ShapeType::IntEnum, memberName: 'intEnum2', memberIndex: 1, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.ec2#XmlIntEnumsOutput$intEnum3', type: ShapeType::IntEnum, memberName: 'intEnum3', memberIndex: 2, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.ec2#XmlIntEnumsOutput$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.ec2#XmlIntEnumsOutput$intEnumSet', type: ShapeType::List_, memberName: 'intEnumSet', memberIndex: 4, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumSet$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.ec2#XmlIntEnumsOutput$intEnumMap', type: ShapeType::Map, memberName: 'intEnumMap', memberIndex: 5, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$value', type: ShapeType::IntEnum, memberName: 'value', memberIndex: 1, targetClass: IntegerEnum::class))
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
            id: 'aws.protocoltests.ec2#XmlListsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.shared#BooleanList$member'] ??= new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: SchemasMembers::$shared['aws.protocoltests.shared#TimestampList$member'] ??= new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: SchemasMembers::$shared['aws.protocoltests.shared#NestedStringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$renamedListMembers', type: ShapeType::List_, memberName: 'renamedListMembers', memberIndex: 8, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#RenamedListMembers$member'] ??= new Schema(id: 'aws.protocoltests.ec2#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'renamed']),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$flattenedList', type: ShapeType::List_, memberName: 'flattenedList', memberIndex: 9, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#RenamedListMembers$member'] ??= new Schema(id: 'aws.protocoltests.ec2#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$flattenedList2', type: ShapeType::List_, memberName: 'flattenedList2', memberIndex: 10, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#RenamedListMembers$member'] ??= new Schema(id: 'aws.protocoltests.ec2#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'customName', 3 => true]),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$flattenedListWithMemberNamespace', type: ShapeType::List_, memberName: 'flattenedListWithMemberNamespace', memberIndex: 11, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#ListWithMemberNamespace$member'] ??= new Schema(id: 'aws.protocoltests.ec2#ListWithMemberNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'https://xml-member.example.com']]), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$flattenedListWithNamespace', type: ShapeType::List_, memberName: 'flattenedListWithNamespace', memberIndex: 12, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#ListWithNamespace$member'] ??= new Schema(id: 'aws.protocoltests.ec2#ListWithNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.ec2#XmlListsOutput$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 13, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#StructureList$member'] ??= new Schema(id: 'aws.protocoltests.ec2#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: self::structureListMember()->getMembers(), traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [0 => 'myStructureList'])
            ],
            targetClass: XmlListsOutput::class
        );
    }

    public static function xmlNamespaceNested(): Schema
    {
        if (isset(self::$XmlNamespaceNested)) {
            return self::$XmlNamespaceNested;
        }

        return self::$XmlNamespaceNested = new Schema(
            id: 'aws.protocoltests.ec2#XmlNamespaceNested',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#XmlNamespaceNested$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [1 => ['uri' => 'http://baz.com', 'prefix' => 'baz']]),
                new Schema(id: 'aws.protocoltests.ec2#XmlNamespaceNested$values', type: ShapeType::List_, memberName: 'values', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.ec2#XmlNamespacedList$member'] ??= new Schema(id: 'aws.protocoltests.ec2#XmlNamespacedList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'http://bux.com']]), traits: [1 => ['uri' => 'http://qux.com']])
            ], traits: [1 => ['uri' => 'http://foo.com']],
            targetClass: XmlNamespaceNested::class
        );
    }

    public static function xmlNamespacesOutput(): Schema
    {
        if (isset(self::$XmlNamespacesOutput)) {
            return self::$XmlNamespacesOutput;
        }

        return self::$XmlNamespacesOutput = new Schema(
            id: 'aws.protocoltests.ec2#XmlNamespacesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#XmlNamespacesOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: self::xmlNamespaceNested()->getMembers(), traits: [1 => ['uri' => 'http://foo.com']], targetClass: XmlNamespaceNested::class)
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
            id: 'aws.protocoltests.ec2#XmlTimestampsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.ec2#XmlTimestampsOutput$normal', type: ShapeType::Timestamp, memberName: 'normal', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.ec2#XmlTimestampsOutput$dateTime', type: ShapeType::Timestamp, memberName: 'dateTime', memberIndex: 1, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.ec2#XmlTimestampsOutput$dateTimeOnTarget', type: ShapeType::Timestamp, memberName: 'dateTimeOnTarget', memberIndex: 2, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.ec2#XmlTimestampsOutput$epochSeconds', type: ShapeType::Timestamp, memberName: 'epochSeconds', memberIndex: 3, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.ec2#XmlTimestampsOutput$epochSecondsOnTarget', type: ShapeType::Timestamp, memberName: 'epochSecondsOnTarget', memberIndex: 4, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.ec2#XmlTimestampsOutput$httpDate', type: ShapeType::Timestamp, memberName: 'httpDate', memberIndex: 5, traits: [4 => 'http-date']),
                new Schema(id: 'aws.protocoltests.ec2#XmlTimestampsOutput$httpDateOnTarget', type: ShapeType::Timestamp, memberName: 'httpDateOnTarget', memberIndex: 6, traits: [4 => 'http-date'])
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
