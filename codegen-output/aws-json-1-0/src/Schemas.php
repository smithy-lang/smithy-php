<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_0\Exception\ComplexErrorException;
use Smithy\ProtocolTests\AwsJson1_0\Exception\FooErrorException;
use Smithy\ProtocolTests\AwsJson1_0\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\AwsJson1_0\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\EndpointWithHostLabelOperationInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\GreetingWithErrorsInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\JsonUnionsInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\SimpleScalarPropertiesInput;
use Smithy\ProtocolTests\AwsJson1_0\Output\ContentTypeParametersOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\EmptyInputAndEmptyOutputOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\JsonUnionsOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\NoInputAndOutputOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\SimpleScalarPropertiesOutput;
use Smithy\ProtocolTests\AwsJson1_0\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\AwsJson1_0\Shape\FooEnum;
use Smithy\ProtocolTests\AwsJson1_0\Shape\GreetingStruct;
use Smithy\ProtocolTests\AwsJson1_0\Shape\IntegerEnum;
use Smithy\ProtocolTests\AwsJson1_0\Shape\MyUnion;

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
    public static Schema $EmptyInputAndEmptyOutputInput;
    public static Schema $EmptyInputAndEmptyOutputOutput;
    public static Schema $EndpointWithHostLabelOperationInput;
    public static Schema $FooErrorException;
    public static Schema $GreetingWithErrorsInput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $InvalidGreetingException;
    public static Schema $JsonUnionsInput;
    public static Schema $JsonUnionsOutput;
    public static Schema $NoInputAndOutputOutput;
    public static Schema $PutWithContentEncodingInput;
    public static Schema $SimpleScalarPropertiesInput;
    public static Schema $SimpleScalarPropertiesOutput;
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
            id: 'aws.protocoltests.json10#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#ComplexError$TopLevel', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json10#ComplexError$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.json10#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0, traits: [12 => 'Fooooo'])], targetClass: ComplexNestedErrorData::class)
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
            id: 'aws.protocoltests.json10#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0, traits: [12 => 'Fooooo'])
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
            id: 'aws.protocoltests.json10#ContentTypeParametersInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#ContentTypeParametersInput$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 0)
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
            id: 'aws.protocoltests.json10#ContentTypeParametersOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: ContentTypeParametersOutput::class
        );
    }

    public static function emptyInputAndEmptyOutputInput(): Schema
    {
        if (isset(self::$EmptyInputAndEmptyOutputInput)) {
            return self::$EmptyInputAndEmptyOutputInput;
        }

        return self::$EmptyInputAndEmptyOutputInput = new Schema(
            id: 'aws.protocoltests.json10#EmptyInputAndEmptyOutputInput',
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
            id: 'aws.protocoltests.json10#EmptyInputAndEmptyOutputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyInputAndEmptyOutputOutput::class
        );
    }

    public static function endpointWithHostLabelOperationInput(): Schema
    {
        if (isset(self::$EndpointWithHostLabelOperationInput)) {
            return self::$EndpointWithHostLabelOperationInput;
        }

        return self::$EndpointWithHostLabelOperationInput = new Schema(
            id: 'aws.protocoltests.json10#EndpointWithHostLabelOperationInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#EndpointWithHostLabelOperationInput$label', type: ShapeType::String_, memberName: 'label', memberIndex: 0, traits: [14 => true])
            ],
            targetClass: EndpointWithHostLabelOperationInput::class
        );
    }

    public static function fooErrorException(): Schema
    {
        if (isset(self::$FooErrorException)) {
            return self::$FooErrorException;
        }

        return self::$FooErrorException = new Schema(
            id: 'aws.protocoltests.json10#FooError',
            type: ShapeType::Structure,
            members: [
            ], traits: [23 => 'server'],
            targetClass: FooErrorException::class
        );
    }

    public static function greetingWithErrorsInput(): Schema
    {
        if (isset(self::$GreetingWithErrorsInput)) {
            return self::$GreetingWithErrorsInput;
        }

        return self::$GreetingWithErrorsInput = new Schema(
            id: 'aws.protocoltests.json10#GreetingWithErrorsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#GreetingWithErrorsInput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
            ],
            targetClass: GreetingWithErrorsInput::class
        );
    }

    public static function greetingWithErrorsOutput(): Schema
    {
        if (isset(self::$GreetingWithErrorsOutput)) {
            return self::$GreetingWithErrorsOutput;
        }

        return self::$GreetingWithErrorsOutput = new Schema(
            id: 'aws.protocoltests.json10#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#GreetingWithErrorsOutput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
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
            id: 'aws.protocoltests.json10#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#InvalidGreeting$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: InvalidGreetingException::class
        );
    }

    public static function jsonUnionsInput(): Schema
    {
        if (isset(self::$JsonUnionsInput)) {
            return self::$JsonUnionsInput;
        }

        return self::$JsonUnionsInput = new Schema(
            id: 'aws.protocoltests.json10#JsonUnionsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#JsonUnionsInput$contents', type: ShapeType::Union, memberName: 'contents', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.json10#MyUnion$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0), new Schema(id: 'aws.protocoltests.json10#MyUnion$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1), new Schema(id: 'aws.protocoltests.json10#MyUnion$numberValue', type: ShapeType::Integer, memberName: 'numberValue', memberIndex: 2), new Schema(id: 'aws.protocoltests.json10#MyUnion$blobValue', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 3), new Schema(id: 'aws.protocoltests.json10#MyUnion$timestampValue', type: ShapeType::Timestamp, memberName: 'timestampValue', memberIndex: 4), new Schema(id: 'aws.protocoltests.json10#MyUnion$enumValue', type: ShapeType::Enum, memberName: 'enumValue', memberIndex: 5, targetClass: FooEnum::class), new Schema(id: 'aws.protocoltests.json10#MyUnion$intEnumValue', type: ShapeType::IntEnum, memberName: 'intEnumValue', memberIndex: 6, targetClass: IntegerEnum::class), new Schema(id: 'aws.protocoltests.json10#MyUnion$listValue', type: ShapeType::List_, memberName: 'listValue', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), new Schema(id: 'aws.protocoltests.json10#MyUnion$mapValue', type: ShapeType::Map, memberName: 'mapValue', memberIndex: 8, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)), new Schema(id: 'aws.protocoltests.json10#MyUnion$structureValue', type: ShapeType::Structure, memberName: 'structureValue', memberIndex: 9, targetClass: GreetingStruct::class)], targetClass: MyUnion::class)
            ],
            targetClass: JsonUnionsInput::class
        );
    }

    public static function jsonUnionsOutput(): Schema
    {
        if (isset(self::$JsonUnionsOutput)) {
            return self::$JsonUnionsOutput;
        }

        return self::$JsonUnionsOutput = new Schema(
            id: 'aws.protocoltests.json10#JsonUnionsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#JsonUnionsOutput$contents', type: ShapeType::Union, memberName: 'contents', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.json10#MyUnion$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0), new Schema(id: 'aws.protocoltests.json10#MyUnion$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1), new Schema(id: 'aws.protocoltests.json10#MyUnion$numberValue', type: ShapeType::Integer, memberName: 'numberValue', memberIndex: 2), new Schema(id: 'aws.protocoltests.json10#MyUnion$blobValue', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 3), new Schema(id: 'aws.protocoltests.json10#MyUnion$timestampValue', type: ShapeType::Timestamp, memberName: 'timestampValue', memberIndex: 4), new Schema(id: 'aws.protocoltests.json10#MyUnion$enumValue', type: ShapeType::Enum, memberName: 'enumValue', memberIndex: 5, targetClass: FooEnum::class), new Schema(id: 'aws.protocoltests.json10#MyUnion$intEnumValue', type: ShapeType::IntEnum, memberName: 'intEnumValue', memberIndex: 6, targetClass: IntegerEnum::class), new Schema(id: 'aws.protocoltests.json10#MyUnion$listValue', type: ShapeType::List_, memberName: 'listValue', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), new Schema(id: 'aws.protocoltests.json10#MyUnion$mapValue', type: ShapeType::Map, memberName: 'mapValue', memberIndex: 8, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)), new Schema(id: 'aws.protocoltests.json10#MyUnion$structureValue', type: ShapeType::Structure, memberName: 'structureValue', memberIndex: 9, targetClass: GreetingStruct::class)], targetClass: MyUnion::class)
            ],
            targetClass: JsonUnionsOutput::class
        );
    }

    public static function noInputAndOutputOutput(): Schema
    {
        if (isset(self::$NoInputAndOutputOutput)) {
            return self::$NoInputAndOutputOutput;
        }

        return self::$NoInputAndOutputOutput = new Schema(
            id: 'aws.protocoltests.json10#NoInputAndOutputOutput',
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
            id: 'aws.protocoltests.json10#PutWithContentEncodingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#PutWithContentEncodingInput$encoding', type: ShapeType::String_, memberName: 'encoding', memberIndex: 0, traits: [5 => 'Content-Encoding']),
                new Schema(id: 'aws.protocoltests.json10#PutWithContentEncodingInput$data', type: ShapeType::String_, memberName: 'data', memberIndex: 1)
            ],
            targetClass: PutWithContentEncodingInput::class
        );
    }

    public static function simpleScalarPropertiesInput(): Schema
    {
        if (isset(self::$SimpleScalarPropertiesInput)) {
            return self::$SimpleScalarPropertiesInput;
        }

        return self::$SimpleScalarPropertiesInput = new Schema(
            id: 'aws.protocoltests.json10#SimpleScalarPropertiesInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#SimpleScalarPropertiesInput$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json10#SimpleScalarPropertiesInput$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 1)
            ],
            targetClass: SimpleScalarPropertiesInput::class
        );
    }

    public static function simpleScalarPropertiesOutput(): Schema
    {
        if (isset(self::$SimpleScalarPropertiesOutput)) {
            return self::$SimpleScalarPropertiesOutput;
        }

        return self::$SimpleScalarPropertiesOutput = new Schema(
            id: 'aws.protocoltests.json10#SimpleScalarPropertiesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.json10#SimpleScalarPropertiesOutput$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json10#SimpleScalarPropertiesOutput$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 1)
            ],
            targetClass: SimpleScalarPropertiesOutput::class
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
            id: 'aws.protocoltests.json10#MyUnion',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.json10#MyUnion$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$numberValue', type: ShapeType::Integer, memberName: 'numberValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$blobValue', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$timestampValue', type: ShapeType::Timestamp, memberName: 'timestampValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$enumValue', type: ShapeType::Enum, memberName: 'enumValue', memberIndex: 5, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$intEnumValue', type: ShapeType::IntEnum, memberName: 'intEnumValue', memberIndex: 6, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$listValue', type: ShapeType::List_, memberName: 'listValue', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$mapValue', type: ShapeType::Map, memberName: 'mapValue', memberIndex: 8, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.json10#MyUnion$structureValue', type: ShapeType::Structure, memberName: 'structureValue', memberIndex: 9, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class)
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
