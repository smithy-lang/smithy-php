<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Exception\ComplexErrorException;
use Smithy\ProtocolTests\RestXml\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\RestXml\Input\AllQueryStringTypesInput;
use Smithy\ProtocolTests\RestXml\Input\ConstantAndVariableQueryStringInput;
use Smithy\ProtocolTests\RestXml\Input\ConstantQueryStringInput;
use Smithy\ProtocolTests\RestXml\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\RestXml\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\RestXml\Input\EndpointWithHostLabelOperationRequest;
use Smithy\ProtocolTests\RestXml\Input\FlattenedXmlMapRequest;
use Smithy\ProtocolTests\RestXml\Input\FlattenedXmlMapWithXmlNameRequest;
use Smithy\ProtocolTests\RestXml\Input\HostLabelHeaderInput;
use Smithy\ProtocolTests\RestXml\Input\HttpEmptyPrefixHeadersInput;
use Smithy\ProtocolTests\RestXml\Input\HttpRequestWithFloatLabelsInput;
use Smithy\ProtocolTests\RestXml\Input\HttpRequestWithGreedyLabelInPathInput;
use Smithy\ProtocolTests\RestXml\Input\HttpRequestWithLabelsAndTimestampFormatInput;
use Smithy\ProtocolTests\RestXml\Input\HttpRequestWithLabelsInput;
use Smithy\ProtocolTests\RestXml\Input\NestedXmlMapWithXmlNameRequest;
use Smithy\ProtocolTests\RestXml\Input\NestedXmlMapsRequest;
use Smithy\ProtocolTests\RestXml\Input\OmitsNullSerializesEmptyStringInput;
use Smithy\ProtocolTests\RestXml\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\RestXml\Input\QueryIdempotencyTokenAutoFillInput;
use Smithy\ProtocolTests\RestXml\Input\QueryParamsAsStringListMapInput;
use Smithy\ProtocolTests\RestXml\Input\QueryPrecedenceInput;
use Smithy\ProtocolTests\RestXml\Input\RecursiveShapesRequest;
use Smithy\ProtocolTests\RestXml\Input\SimpleScalarPropertiesRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlAttributesInMiddleRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlAttributesOnPayloadRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlAttributesRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlBlobsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlEmptyBlobsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlEmptyListsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlEmptyMapsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlEmptyStringsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlEnumsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlIntEnumsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlListsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlMapWithXmlNamespaceRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlMapsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlMapsXmlNameRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlNamespacesRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlTimestampsRequest;
use Smithy\ProtocolTests\RestXml\Input\XmlUnionsRequest;
use Smithy\ProtocolTests\RestXml\Output\ContentTypeParametersOutput;
use Smithy\ProtocolTests\RestXml\Output\DatetimeOffsetsOutput;
use Smithy\ProtocolTests\RestXml\Output\EmptyInputAndEmptyOutputOutput;
use Smithy\ProtocolTests\RestXml\Output\FlattenedXmlMapResponse;
use Smithy\ProtocolTests\RestXml\Output\FlattenedXmlMapWithXmlNameResponse;
use Smithy\ProtocolTests\RestXml\Output\FlattenedXmlMapWithXmlNamespaceOutput;
use Smithy\ProtocolTests\RestXml\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\RestXml\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\RestXml\Output\HttpEmptyPrefixHeadersOutput;
use Smithy\ProtocolTests\RestXml\Output\HttpResponseCodeOutput;
use Smithy\ProtocolTests\RestXml\Output\IgnoreQueryParamsInResponseOutput;
use Smithy\ProtocolTests\RestXml\Output\NestedXmlMapWithXmlNameResponse;
use Smithy\ProtocolTests\RestXml\Output\NestedXmlMapsResponse;
use Smithy\ProtocolTests\RestXml\Output\NoInputAndOutputOutput;
use Smithy\ProtocolTests\RestXml\Output\RecursiveShapesResponse;
use Smithy\ProtocolTests\RestXml\Output\SimpleScalarPropertiesResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlAttributesInMiddleResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlAttributesOnPayloadResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlAttributesResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlBlobsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlEmptyBlobsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlEmptyListsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlEmptyMapsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlEmptyStringsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlEnumsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlIntEnumsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlListsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlMapWithXmlNamespaceResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlMapsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlMapsXmlNameResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlNamespacesResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlTimestampsResponse;
use Smithy\ProtocolTests\RestXml\Output\XmlUnionsResponse;
use Smithy\ProtocolTests\RestXml\Shape\BodyWithXmlNameInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\RestXml\Shape\EnumPayloadInput;
use Smithy\ProtocolTests\RestXml\Shape\FooEnum;
use Smithy\ProtocolTests\RestXml\Shape\GreetingStruct;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadTraitsInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithMemberXmlNameInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithStructureInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithUnionInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithXmlNameInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithXmlNamespaceAndPrefixInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithXmlNamespaceInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPrefixHeadersInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\InputAndOutputWithHeadersIO;
use Smithy\ProtocolTests\RestXml\Shape\IntegerEnum;
use Smithy\ProtocolTests\RestXml\Shape\NestedPayload;
use Smithy\ProtocolTests\RestXml\Shape\NullAndEmptyHeadersIO;
use Smithy\ProtocolTests\RestXml\Shape\PayloadWithXmlName;
use Smithy\ProtocolTests\RestXml\Shape\PayloadWithXmlNamespace;
use Smithy\ProtocolTests\RestXml\Shape\PayloadWithXmlNamespaceAndPrefix;
use Smithy\ProtocolTests\RestXml\Shape\RecursiveShapesInputOutputNested1;
use Smithy\ProtocolTests\RestXml\Shape\RecursiveShapesInputOutputNested2;
use Smithy\ProtocolTests\RestXml\Shape\StringEnum;
use Smithy\ProtocolTests\RestXml\Shape\StringPayloadInput;
use Smithy\ProtocolTests\RestXml\Shape\StructureListMember;
use Smithy\ProtocolTests\RestXml\Shape\TimestampFormatHeadersIO;
use Smithy\ProtocolTests\RestXml\Shape\UnionPayload;
use Smithy\ProtocolTests\RestXml\Shape\XmlAttributesInMiddlePayloadRequest;
use Smithy\ProtocolTests\RestXml\Shape\XmlAttributesInMiddlePayloadResponse;
use Smithy\ProtocolTests\RestXml\Shape\XmlAttributesPayloadRequest;
use Smithy\ProtocolTests\RestXml\Shape\XmlAttributesPayloadResponse;
use Smithy\ProtocolTests\RestXml\Shape\XmlNamespaceNested;
use Smithy\ProtocolTests\RestXml\Shape\XmlNestedUnionStruct;
use Smithy\ProtocolTests\RestXml\Shape\XmlUnionShape;

/**
 * Centralized schema registry for all shapes in this service.
 *
 * Stores Schema objects as lazy public static properties.
 * Each structure has a dedicated member initializer.
 */
final class Schemas
{
    public static Schema $AllQueryStringTypesInput;
    public static Schema $BodyWithXmlNameInputOutput;
    public static Schema $ComplexErrorException;
    public static Schema $ComplexNestedErrorData;
    public static Schema $ConstantAndVariableQueryStringInput;
    public static Schema $ConstantQueryStringInput;
    public static Schema $ContentTypeParametersInput;
    public static Schema $ContentTypeParametersOutput;
    public static Schema $DatetimeOffsetsOutput;
    public static Schema $EmptyInputAndEmptyOutputInput;
    public static Schema $EmptyInputAndEmptyOutputOutput;
    public static Schema $EndpointWithHostLabelOperationRequest;
    public static Schema $EnumPayloadInput;
    public static Schema $FlattenedXmlMapRequest;
    public static Schema $FlattenedXmlMapResponse;
    public static Schema $FlattenedXmlMapWithXmlNameRequest;
    public static Schema $FlattenedXmlMapWithXmlNameResponse;
    public static Schema $FlattenedXmlMapWithXmlNamespaceOutput;
    public static Schema $FractionalSecondsOutput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $HostLabelHeaderInput;
    public static Schema $HttpEmptyPrefixHeadersInput;
    public static Schema $HttpEmptyPrefixHeadersOutput;
    public static Schema $HttpPayloadTraitsInputOutput;
    public static Schema $HttpPayloadWithMemberXmlNameInputOutput;
    public static Schema $HttpPayloadWithStructureInputOutput;
    public static Schema $HttpPayloadWithUnionInputOutput;
    public static Schema $HttpPayloadWithXmlNameInputOutput;
    public static Schema $HttpPayloadWithXmlNamespaceAndPrefixInputOutput;
    public static Schema $HttpPayloadWithXmlNamespaceInputOutput;
    public static Schema $HttpPrefixHeadersInputOutput;
    public static Schema $HttpRequestWithFloatLabelsInput;
    public static Schema $HttpRequestWithGreedyLabelInPathInput;
    public static Schema $HttpRequestWithLabelsAndTimestampFormatInput;
    public static Schema $HttpRequestWithLabelsInput;
    public static Schema $HttpResponseCodeOutput;
    public static Schema $IgnoreQueryParamsInResponseOutput;
    public static Schema $InputAndOutputWithHeadersIO;
    public static Schema $InvalidGreetingException;
    public static Schema $NestedPayload;
    public static Schema $NestedXmlMapWithXmlNameRequest;
    public static Schema $NestedXmlMapWithXmlNameResponse;
    public static Schema $NestedXmlMapsRequest;
    public static Schema $NestedXmlMapsResponse;
    public static Schema $NoInputAndOutputOutput;
    public static Schema $NullAndEmptyHeadersIO;
    public static Schema $OmitsNullSerializesEmptyStringInput;
    public static Schema $PayloadWithXmlName;
    public static Schema $PayloadWithXmlNamespace;
    public static Schema $PayloadWithXmlNamespaceAndPrefix;
    public static Schema $PutWithContentEncodingInput;
    public static Schema $QueryIdempotencyTokenAutoFillInput;
    public static Schema $QueryParamsAsStringListMapInput;
    public static Schema $QueryPrecedenceInput;
    public static Schema $RecursiveShapesInputOutputNested1;
    public static Schema $RecursiveShapesInputOutputNested2;
    public static Schema $RecursiveShapesRequest;
    public static Schema $RecursiveShapesResponse;
    public static Schema $SimpleScalarPropertiesRequest;
    public static Schema $SimpleScalarPropertiesResponse;
    public static Schema $StringPayloadInput;
    public static Schema $StructureListMember;
    public static Schema $TimestampFormatHeadersIO;
    public static Schema $XmlAttributesInMiddlePayloadRequest;
    public static Schema $XmlAttributesInMiddlePayloadResponse;
    public static Schema $XmlAttributesInMiddleRequest;
    public static Schema $XmlAttributesInMiddleResponse;
    public static Schema $XmlAttributesOnPayloadRequest;
    public static Schema $XmlAttributesOnPayloadResponse;
    public static Schema $XmlAttributesPayloadRequest;
    public static Schema $XmlAttributesPayloadResponse;
    public static Schema $XmlAttributesRequest;
    public static Schema $XmlAttributesResponse;
    public static Schema $XmlBlobsRequest;
    public static Schema $XmlBlobsResponse;
    public static Schema $XmlEmptyBlobsRequest;
    public static Schema $XmlEmptyBlobsResponse;
    public static Schema $XmlEmptyListsRequest;
    public static Schema $XmlEmptyListsResponse;
    public static Schema $XmlEmptyMapsRequest;
    public static Schema $XmlEmptyMapsResponse;
    public static Schema $XmlEmptyStringsRequest;
    public static Schema $XmlEmptyStringsResponse;
    public static Schema $XmlEnumsRequest;
    public static Schema $XmlEnumsResponse;
    public static Schema $XmlIntEnumsRequest;
    public static Schema $XmlIntEnumsResponse;
    public static Schema $XmlListsRequest;
    public static Schema $XmlListsResponse;
    public static Schema $XmlMapWithXmlNamespaceRequest;
    public static Schema $XmlMapWithXmlNamespaceResponse;
    public static Schema $XmlMapsRequest;
    public static Schema $XmlMapsResponse;
    public static Schema $XmlMapsXmlNameRequest;
    public static Schema $XmlMapsXmlNameResponse;
    public static Schema $XmlNamespaceNested;
    public static Schema $XmlNamespacesRequest;
    public static Schema $XmlNamespacesResponse;
    public static Schema $XmlNestedUnionStruct;
    public static Schema $XmlTimestampsRequest;
    public static Schema $XmlTimestampsResponse;
    public static Schema $XmlUnionsRequest;
    public static Schema $XmlUnionsResponse;
    public static Schema $GreetingStruct;
    public static Schema $UnionPayload;
    public static Schema $XmlUnionShape;
    public static Schema $StringEnum;
    public static Schema $FooEnum;
    public static Schema $IntegerEnum;

    public static function allQueryStringTypesInput(): Schema
    {
        if (isset(self::$AllQueryStringTypesInput)) {
            return self::$AllQueryStringTypesInput;
        }

        return self::$AllQueryStringTypesInput = new Schema(
            id: 'aws.protocoltests.restxml#AllQueryStringTypesInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryString', type: ShapeType::String_, memberName: 'queryString', memberIndex: 0, traits: [9 => 'String']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryStringList', type: ShapeType::List_, memberName: 'queryStringList', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [9 => 'StringList']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryStringSet', type: ShapeType::List_, memberName: 'queryStringSet', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [9 => 'StringSet']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryByte', type: ShapeType::Integer, memberName: 'queryByte', memberIndex: 3, traits: [9 => 'Byte']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryShort', type: ShapeType::Integer, memberName: 'queryShort', memberIndex: 4, traits: [9 => 'Short']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryInteger', type: ShapeType::Integer, memberName: 'queryInteger', memberIndex: 5, traits: [9 => 'Integer']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryIntegerList', type: ShapeType::List_, memberName: 'queryIntegerList', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [9 => 'IntegerList']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryIntegerSet', type: ShapeType::List_, memberName: 'queryIntegerSet', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerSet$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [9 => 'IntegerSet']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryLong', type: ShapeType::Long, memberName: 'queryLong', memberIndex: 8, traits: [9 => 'Long']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryFloat', type: ShapeType::Float_, memberName: 'queryFloat', memberIndex: 9, traits: [9 => 'Float']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryDouble', type: ShapeType::Double, memberName: 'queryDouble', memberIndex: 10, traits: [9 => 'Double']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryDoubleList', type: ShapeType::List_, memberName: 'queryDoubleList', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.shared#DoubleList$member', type: ShapeType::Double, memberName: 'member', memberIndex: 0), traits: [9 => 'DoubleList']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryBoolean', type: ShapeType::Boolean, memberName: 'queryBoolean', memberIndex: 12, traits: [9 => 'Boolean']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryBooleanList', type: ShapeType::List_, memberName: 'queryBooleanList', memberIndex: 13, listMember: new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0), traits: [9 => 'BooleanList']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryTimestamp', type: ShapeType::Timestamp, memberName: 'queryTimestamp', memberIndex: 14, traits: [9 => 'Timestamp']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryTimestampList', type: ShapeType::List_, memberName: 'queryTimestampList', memberIndex: 15, listMember: new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0), traits: [9 => 'TimestampList']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryEnum', type: ShapeType::Enum, memberName: 'queryEnum', memberIndex: 16, traits: [9 => 'Enum'], targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryEnumList', type: ShapeType::List_, memberName: 'queryEnumList', memberIndex: 17, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class), traits: [9 => 'EnumList']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryIntegerEnum', type: ShapeType::IntEnum, memberName: 'queryIntegerEnum', memberIndex: 18, traits: [9 => 'IntegerEnum'], targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryIntegerEnumList', type: ShapeType::List_, memberName: 'queryIntegerEnumList', memberIndex: 19, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class), traits: [9 => 'IntegerEnumList']),
                new Schema(id: 'aws.protocoltests.restxml#AllQueryStringTypesInput$queryParamsMapOfStrings', type: ShapeType::Map, memberName: 'queryParamsMapOfStrings', memberIndex: 20, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [10 => true])
            ],
            targetClass: AllQueryStringTypesInput::class
        );
    }

    public static function bodyWithXmlNameInputOutput(): Schema
    {
        if (isset(self::$BodyWithXmlNameInputOutput)) {
            return self::$BodyWithXmlNameInputOutput;
        }

        return self::$BodyWithXmlNameInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#BodyWithXmlNameInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#BodyWithXmlNameInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlName$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)], targetClass: PayloadWithXmlName::class)
            ], traits: [0 => 'Ahoy'],
            targetClass: BodyWithXmlNameInputOutput::class
        );
    }

    public static function complexErrorException(): Schema
    {
        if (isset(self::$ComplexErrorException)) {
            return self::$ComplexErrorException;
        }

        return self::$ComplexErrorException = new Schema(
            id: 'aws.protocoltests.restxml#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#ComplexError$Header', type: ShapeType::String_, memberName: 'Header', memberIndex: 0, traits: [5 => 'X-Header']),
                new Schema(id: 'aws.protocoltests.restxml#ComplexError$TopLevel', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restxml#ComplexError$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 2, members: [new Schema(id: 'aws.protocoltests.restxml#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)], targetClass: ComplexNestedErrorData::class)
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
            id: 'aws.protocoltests.restxml#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0)
            ],
            targetClass: ComplexNestedErrorData::class
        );
    }

    public static function constantAndVariableQueryStringInput(): Schema
    {
        if (isset(self::$ConstantAndVariableQueryStringInput)) {
            return self::$ConstantAndVariableQueryStringInput;
        }

        return self::$ConstantAndVariableQueryStringInput = new Schema(
            id: 'aws.protocoltests.restxml#ConstantAndVariableQueryStringInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#ConstantAndVariableQueryStringInput$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 0, traits: [9 => 'baz']),
                new Schema(id: 'aws.protocoltests.restxml#ConstantAndVariableQueryStringInput$maybeSet', type: ShapeType::String_, memberName: 'maybeSet', memberIndex: 1, traits: [9 => 'maybeSet'])
            ],
            targetClass: ConstantAndVariableQueryStringInput::class
        );
    }

    public static function constantQueryStringInput(): Schema
    {
        if (isset(self::$ConstantQueryStringInput)) {
            return self::$ConstantQueryStringInput;
        }

        return self::$ConstantQueryStringInput = new Schema(
            id: 'aws.protocoltests.restxml#ConstantQueryStringInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#ConstantQueryStringInput$hello', type: ShapeType::String_, memberName: 'hello', memberIndex: 0, traits: [6 => true, 14 => true])
            ],
            targetClass: ConstantQueryStringInput::class
        );
    }

    public static function contentTypeParametersInput(): Schema
    {
        if (isset(self::$ContentTypeParametersInput)) {
            return self::$ContentTypeParametersInput;
        }

        return self::$ContentTypeParametersInput = new Schema(
            id: 'aws.protocoltests.restxml#ContentTypeParametersInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#ContentTypeParametersInput$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 0)
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
            id: 'aws.protocoltests.restxml#ContentTypeParametersOutput',
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
            id: 'aws.protocoltests.restxml#DatetimeOffsetsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#DatetimeOffsetsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
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
            id: 'aws.protocoltests.restxml#EmptyInputAndEmptyOutputInput',
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
            id: 'aws.protocoltests.restxml#EmptyInputAndEmptyOutputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyInputAndEmptyOutputOutput::class
        );
    }

    public static function endpointWithHostLabelOperationRequest(): Schema
    {
        if (isset(self::$EndpointWithHostLabelOperationRequest)) {
            return self::$EndpointWithHostLabelOperationRequest;
        }

        return self::$EndpointWithHostLabelOperationRequest = new Schema(
            id: 'aws.protocoltests.restxml#EndpointWithHostLabelOperationRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#EndpointWithHostLabelOperationRequest$label', type: ShapeType::String_, memberName: 'label', memberIndex: 0, traits: [14 => true])
            ],
            targetClass: EndpointWithHostLabelOperationRequest::class
        );
    }

    public static function enumPayloadInput(): Schema
    {
        if (isset(self::$EnumPayloadInput)) {
            return self::$EnumPayloadInput;
        }

        return self::$EnumPayloadInput = new Schema(
            id: 'aws.protocoltests.restxml#EnumPayloadInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#EnumPayloadInput$payload', type: ShapeType::Enum, memberName: 'payload', memberIndex: 0, traits: [7 => true], targetClass: StringEnum::class)
            ],
            targetClass: EnumPayloadInput::class
        );
    }

    public static function flattenedXmlMapRequest(): Schema
    {
        if (isset(self::$FlattenedXmlMapRequest)) {
            return self::$FlattenedXmlMapRequest;
        }

        return self::$FlattenedXmlMapRequest = new Schema(
            id: 'aws.protocoltests.restxml#FlattenedXmlMapRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapRequest$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class), traits: [3 => true])
            ],
            targetClass: FlattenedXmlMapRequest::class
        );
    }

    public static function flattenedXmlMapResponse(): Schema
    {
        if (isset(self::$FlattenedXmlMapResponse)) {
            return self::$FlattenedXmlMapResponse;
        }

        return self::$FlattenedXmlMapResponse = new Schema(
            id: 'aws.protocoltests.restxml#FlattenedXmlMapResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapResponse$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class), traits: [3 => true])
            ],
            targetClass: FlattenedXmlMapResponse::class
        );
    }

    public static function flattenedXmlMapWithXmlNameRequest(): Schema
    {
        if (isset(self::$FlattenedXmlMapWithXmlNameRequest)) {
            return self::$FlattenedXmlMapWithXmlNameRequest;
        }

        return self::$FlattenedXmlMapWithXmlNameRequest = new Schema(
            id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameRequest$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K']), mapValue: new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameInputOutputMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V']), traits: [0 => 'KVP', 3 => true])
            ],
            targetClass: FlattenedXmlMapWithXmlNameRequest::class
        );
    }

    public static function flattenedXmlMapWithXmlNameResponse(): Schema
    {
        if (isset(self::$FlattenedXmlMapWithXmlNameResponse)) {
            return self::$FlattenedXmlMapWithXmlNameResponse;
        }

        return self::$FlattenedXmlMapWithXmlNameResponse = new Schema(
            id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameResponse$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K']), mapValue: new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameInputOutputMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V']), traits: [0 => 'KVP', 3 => true])
            ],
            targetClass: FlattenedXmlMapWithXmlNameResponse::class
        );
    }

    public static function flattenedXmlMapWithXmlNamespaceOutput(): Schema
    {
        if (isset(self::$FlattenedXmlMapWithXmlNamespaceOutput)) {
            return self::$FlattenedXmlMapWithXmlNamespaceOutput;
        }

        return self::$FlattenedXmlMapWithXmlNamespaceOutput = new Schema(
            id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNamespaceOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNamespaceOutput$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNamespaceOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K', 1 => ['uri' => 'https://the-key.example.com']]), mapValue: new Schema(id: 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNamespaceOutputMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V', 1 => ['uri' => 'https://the-value.example.com']]), traits: [0 => 'KVP', 1 => ['uri' => 'https://the-member.example.com'], 3 => true])
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
            id: 'aws.protocoltests.restxml#FractionalSecondsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#FractionalSecondsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
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
            id: 'aws.protocoltests.restxml#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#GreetingWithErrorsOutput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0, traits: [5 => 'X-Greeting'])
            ],
            targetClass: GreetingWithErrorsOutput::class
        );
    }

    public static function hostLabelHeaderInput(): Schema
    {
        if (isset(self::$HostLabelHeaderInput)) {
            return self::$HostLabelHeaderInput;
        }

        return self::$HostLabelHeaderInput = new Schema(
            id: 'aws.protocoltests.restxml#HostLabelHeaderInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HostLabelHeaderInput$accountId', type: ShapeType::String_, memberName: 'accountId', memberIndex: 0, traits: [5 => 'X-Amz-Account-Id', 14 => true])
            ],
            targetClass: HostLabelHeaderInput::class
        );
    }

    public static function httpEmptyPrefixHeadersInput(): Schema
    {
        if (isset(self::$HttpEmptyPrefixHeadersInput)) {
            return self::$HttpEmptyPrefixHeadersInput;
        }

        return self::$HttpEmptyPrefixHeadersInput = new Schema(
            id: 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersInput$prefixHeaders', type: ShapeType::Map, memberName: 'prefixHeaders', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => '']),
                new Schema(id: 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersInput$specificHeader', type: ShapeType::String_, memberName: 'specificHeader', memberIndex: 1, traits: [5 => 'hello'])
            ],
            targetClass: HttpEmptyPrefixHeadersInput::class
        );
    }

    public static function httpEmptyPrefixHeadersOutput(): Schema
    {
        if (isset(self::$HttpEmptyPrefixHeadersOutput)) {
            return self::$HttpEmptyPrefixHeadersOutput;
        }

        return self::$HttpEmptyPrefixHeadersOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersOutput$prefixHeaders', type: ShapeType::Map, memberName: 'prefixHeaders', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => '']),
                new Schema(id: 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersOutput$specificHeader', type: ShapeType::String_, memberName: 'specificHeader', memberIndex: 1, traits: [5 => 'hello'])
            ],
            targetClass: HttpEmptyPrefixHeadersOutput::class
        );
    }

    public static function httpPayloadTraitsInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadTraitsInputOutput)) {
            return self::$HttpPayloadTraitsInputOutput;
        }

        return self::$HttpPayloadTraitsInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPayloadTraitsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadTraitsInputOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'X-Foo']),
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadTraitsInputOutput$blob', type: ShapeType::Blob, memberName: 'blob', memberIndex: 1, traits: [7 => true])
            ],
            targetClass: HttpPayloadTraitsInputOutput::class
        );
    }

    public static function httpPayloadWithMemberXmlNameInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadWithMemberXmlNameInputOutput)) {
            return self::$HttpPayloadWithMemberXmlNameInputOutput;
        }

        return self::$HttpPayloadWithMemberXmlNameInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPayloadWithMemberXmlNameInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadWithMemberXmlNameInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlName$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)], traits: [0 => 'Hola', 7 => true], targetClass: PayloadWithXmlName::class)
            ],
            targetClass: HttpPayloadWithMemberXmlNameInputOutput::class
        );
    }

    public static function httpPayloadWithStructureInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadWithStructureInputOutput)) {
            return self::$HttpPayloadWithStructureInputOutput;
        }

        return self::$HttpPayloadWithStructureInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPayloadWithStructureInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadWithStructureInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#NestedPayload$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#NestedPayload$name', type: ShapeType::String_, memberName: 'name', memberIndex: 1)], traits: [0 => 'NestedPayload', 7 => true], targetClass: NestedPayload::class)
            ],
            targetClass: HttpPayloadWithStructureInputOutput::class
        );
    }

    public static function httpPayloadWithUnionInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadWithUnionInputOutput)) {
            return self::$HttpPayloadWithUnionInputOutput;
        }

        return self::$HttpPayloadWithUnionInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPayloadWithUnionInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadWithUnionInputOutput$nested', type: ShapeType::Union, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#UnionPayload$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)], traits: [7 => true], targetClass: UnionPayload::class)
            ],
            targetClass: HttpPayloadWithUnionInputOutput::class
        );
    }

    public static function httpPayloadWithXmlNameInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadWithXmlNameInputOutput)) {
            return self::$HttpPayloadWithXmlNameInputOutput;
        }

        return self::$HttpPayloadWithXmlNameInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPayloadWithXmlNameInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadWithXmlNameInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlName$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)], traits: [7 => true], targetClass: PayloadWithXmlName::class)
            ],
            targetClass: HttpPayloadWithXmlNameInputOutput::class
        );
    }

    public static function httpPayloadWithXmlNamespaceAndPrefixInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadWithXmlNamespaceAndPrefixInputOutput)) {
            return self::$HttpPayloadWithXmlNamespaceAndPrefixInputOutput;
        }

        return self::$HttpPayloadWithXmlNamespaceAndPrefixInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceAndPrefixInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceAndPrefixInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlNamespaceAndPrefix$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)], traits: [1 => ['uri' => 'http://foo.com', 'prefix' => 'baz'], 7 => true], targetClass: PayloadWithXmlNamespaceAndPrefix::class)
            ],
            targetClass: HttpPayloadWithXmlNamespaceAndPrefixInputOutput::class
        );
    }

    public static function httpPayloadWithXmlNamespaceInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadWithXmlNamespaceInputOutput)) {
            return self::$HttpPayloadWithXmlNamespaceInputOutput;
        }

        return self::$HttpPayloadWithXmlNamespaceInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlNamespace$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)], traits: [1 => ['uri' => 'http://foo.com'], 7 => true], targetClass: PayloadWithXmlNamespace::class)
            ],
            targetClass: HttpPayloadWithXmlNamespaceInputOutput::class
        );
    }

    public static function httpPrefixHeadersInputOutput(): Schema
    {
        if (isset(self::$HttpPrefixHeadersInputOutput)) {
            return self::$HttpPrefixHeadersInputOutput;
        }

        return self::$HttpPrefixHeadersInputOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpPrefixHeadersInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpPrefixHeadersInputOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'x-foo']),
                new Schema(id: 'aws.protocoltests.restxml#HttpPrefixHeadersInputOutput$fooMap', type: ShapeType::Map, memberName: 'fooMap', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.restxml#FooPrefixHeaders$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#FooPrefixHeaders$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => 'x-foo-'])
            ],
            targetClass: HttpPrefixHeadersInputOutput::class
        );
    }

    public static function httpRequestWithFloatLabelsInput(): Schema
    {
        if (isset(self::$HttpRequestWithFloatLabelsInput)) {
            return self::$HttpRequestWithFloatLabelsInput;
        }

        return self::$HttpRequestWithFloatLabelsInput = new Schema(
            id: 'aws.protocoltests.restxml#HttpRequestWithFloatLabelsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithFloatLabelsInput$float', type: ShapeType::Float_, memberName: 'float', memberIndex: 0, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithFloatLabelsInput$double', type: ShapeType::Double, memberName: 'double', memberIndex: 1, traits: [6 => true, 14 => true])
            ],
            targetClass: HttpRequestWithFloatLabelsInput::class
        );
    }

    public static function httpRequestWithGreedyLabelInPathInput(): Schema
    {
        if (isset(self::$HttpRequestWithGreedyLabelInPathInput)) {
            return self::$HttpRequestWithGreedyLabelInPathInput;
        }

        return self::$HttpRequestWithGreedyLabelInPathInput = new Schema(
            id: 'aws.protocoltests.restxml#HttpRequestWithGreedyLabelInPathInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithGreedyLabelInPathInput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithGreedyLabelInPathInput$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 1, traits: [6 => true, 14 => true])
            ],
            targetClass: HttpRequestWithGreedyLabelInPathInput::class
        );
    }

    public static function httpRequestWithLabelsAndTimestampFormatInput(): Schema
    {
        if (isset(self::$HttpRequestWithLabelsAndTimestampFormatInput)) {
            return self::$HttpRequestWithLabelsAndTimestampFormatInput;
        }

        return self::$HttpRequestWithLabelsAndTimestampFormatInput = new Schema(
            id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput$memberEpochSeconds', type: ShapeType::Timestamp, memberName: 'memberEpochSeconds', memberIndex: 0, traits: [4 => 'epoch-seconds', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput$memberHttpDate', type: ShapeType::Timestamp, memberName: 'memberHttpDate', memberIndex: 1, traits: [4 => 'http-date', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput$memberDateTime', type: ShapeType::Timestamp, memberName: 'memberDateTime', memberIndex: 2, traits: [4 => 'date-time', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput$defaultFormat', type: ShapeType::Timestamp, memberName: 'defaultFormat', memberIndex: 3, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput$targetEpochSeconds', type: ShapeType::Timestamp, memberName: 'targetEpochSeconds', memberIndex: 4, traits: [4 => 'epoch-seconds', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput$targetHttpDate', type: ShapeType::Timestamp, memberName: 'targetHttpDate', memberIndex: 5, traits: [4 => 'http-date', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput$targetDateTime', type: ShapeType::Timestamp, memberName: 'targetDateTime', memberIndex: 6, traits: [4 => 'date-time', 6 => true, 14 => true])
            ],
            targetClass: HttpRequestWithLabelsAndTimestampFormatInput::class
        );
    }

    public static function httpRequestWithLabelsInput(): Schema
    {
        if (isset(self::$HttpRequestWithLabelsInput)) {
            return self::$HttpRequestWithLabelsInput;
        }

        return self::$HttpRequestWithLabelsInput = new Schema(
            id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$string', type: ShapeType::String_, memberName: 'string', memberIndex: 0, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$short', type: ShapeType::Integer, memberName: 'short', memberIndex: 1, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$integer', type: ShapeType::Integer, memberName: 'integer', memberIndex: 2, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$long', type: ShapeType::Long, memberName: 'long', memberIndex: 3, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$float', type: ShapeType::Float_, memberName: 'float', memberIndex: 4, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$double', type: ShapeType::Double, memberName: 'double', memberIndex: 5, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$boolean', type: ShapeType::Boolean, memberName: 'boolean', memberIndex: 6, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restxml#HttpRequestWithLabelsInput$timestamp', type: ShapeType::Timestamp, memberName: 'timestamp', memberIndex: 7, traits: [6 => true, 14 => true])
            ],
            targetClass: HttpRequestWithLabelsInput::class
        );
    }

    public static function httpResponseCodeOutput(): Schema
    {
        if (isset(self::$HttpResponseCodeOutput)) {
            return self::$HttpResponseCodeOutput;
        }

        return self::$HttpResponseCodeOutput = new Schema(
            id: 'aws.protocoltests.restxml#HttpResponseCodeOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#HttpResponseCodeOutput$Status', type: ShapeType::Integer, memberName: 'Status', memberIndex: 0, traits: [11 => true])
            ],
            targetClass: HttpResponseCodeOutput::class
        );
    }

    public static function ignoreQueryParamsInResponseOutput(): Schema
    {
        if (isset(self::$IgnoreQueryParamsInResponseOutput)) {
            return self::$IgnoreQueryParamsInResponseOutput;
        }

        return self::$IgnoreQueryParamsInResponseOutput = new Schema(
            id: 'aws.protocoltests.restxml#IgnoreQueryParamsInResponseOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#IgnoreQueryParamsInResponseOutput$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 0, traits: [9 => 'baz'])
            ],
            targetClass: IgnoreQueryParamsInResponseOutput::class
        );
    }

    public static function inputAndOutputWithHeadersIO(): Schema
    {
        if (isset(self::$InputAndOutputWithHeadersIO)) {
            return self::$InputAndOutputWithHeadersIO;
        }

        return self::$InputAndOutputWithHeadersIO = new Schema(
            id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerString', type: ShapeType::String_, memberName: 'headerString', memberIndex: 0, traits: [5 => 'X-String']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerByte', type: ShapeType::Integer, memberName: 'headerByte', memberIndex: 1, traits: [5 => 'X-Byte']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerShort', type: ShapeType::Integer, memberName: 'headerShort', memberIndex: 2, traits: [5 => 'X-Short']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerInteger', type: ShapeType::Integer, memberName: 'headerInteger', memberIndex: 3, traits: [5 => 'X-Integer']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerLong', type: ShapeType::Long, memberName: 'headerLong', memberIndex: 4, traits: [5 => 'X-Long']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerFloat', type: ShapeType::Float_, memberName: 'headerFloat', memberIndex: 5, traits: [5 => 'X-Float']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerDouble', type: ShapeType::Double, memberName: 'headerDouble', memberIndex: 6, traits: [5 => 'X-Double']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerTrueBool', type: ShapeType::Boolean, memberName: 'headerTrueBool', memberIndex: 7, traits: [5 => 'X-Boolean1']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerFalseBool', type: ShapeType::Boolean, memberName: 'headerFalseBool', memberIndex: 8, traits: [5 => 'X-Boolean2']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerStringList', type: ShapeType::List_, memberName: 'headerStringList', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [5 => 'X-StringList']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerStringSet', type: ShapeType::List_, memberName: 'headerStringSet', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [5 => 'X-StringSet']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerIntegerList', type: ShapeType::List_, memberName: 'headerIntegerList', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [5 => 'X-IntegerList']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerBooleanList', type: ShapeType::List_, memberName: 'headerBooleanList', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0), traits: [5 => 'X-BooleanList']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerTimestampList', type: ShapeType::List_, memberName: 'headerTimestampList', memberIndex: 13, listMember: new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0), traits: [5 => 'X-TimestampList']),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerEnum', type: ShapeType::Enum, memberName: 'headerEnum', memberIndex: 14, traits: [5 => 'X-Enum'], targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO$headerEnumList', type: ShapeType::List_, memberName: 'headerEnumList', memberIndex: 15, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class), traits: [5 => 'X-EnumList'])
            ],
            targetClass: InputAndOutputWithHeadersIO::class
        );
    }

    public static function invalidGreetingException(): Schema
    {
        if (isset(self::$InvalidGreetingException)) {
            return self::$InvalidGreetingException;
        }

        return self::$InvalidGreetingException = new Schema(
            id: 'aws.protocoltests.restxml#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#InvalidGreeting$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: InvalidGreetingException::class
        );
    }

    public static function nestedPayload(): Schema
    {
        if (isset(self::$NestedPayload)) {
            return self::$NestedPayload;
        }

        return self::$NestedPayload = new Schema(
            id: 'aws.protocoltests.restxml#NestedPayload',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#NestedPayload$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#NestedPayload$name', type: ShapeType::String_, memberName: 'name', memberIndex: 1)
            ],
            targetClass: NestedPayload::class
        );
    }

    public static function nestedXmlMapWithXmlNameRequest(): Schema
    {
        if (isset(self::$NestedXmlMapWithXmlNameRequest)) {
            return self::$NestedXmlMapWithXmlNameRequest;
        }

        return self::$NestedXmlMapWithXmlNameRequest = new Schema(
            id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameRequest$nestedXmlMapWithXmlNameMap', type: ShapeType::Map, memberName: 'nestedXmlMapWithXmlNameMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'OuterKey']), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameMap$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameInnerMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'InnerKey']), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameInnerMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'InnerValue'])))
            ],
            targetClass: NestedXmlMapWithXmlNameRequest::class
        );
    }

    public static function nestedXmlMapWithXmlNameResponse(): Schema
    {
        if (isset(self::$NestedXmlMapWithXmlNameResponse)) {
            return self::$NestedXmlMapWithXmlNameResponse;
        }

        return self::$NestedXmlMapWithXmlNameResponse = new Schema(
            id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameResponse$nestedXmlMapWithXmlNameMap', type: ShapeType::Map, memberName: 'nestedXmlMapWithXmlNameMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'OuterKey']), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameMap$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameInnerMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'InnerKey']), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameInnerMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'InnerValue'])))
            ],
            targetClass: NestedXmlMapWithXmlNameResponse::class
        );
    }

    public static function nestedXmlMapsRequest(): Schema
    {
        if (isset(self::$NestedXmlMapsRequest)) {
            return self::$NestedXmlMapsRequest;
        }

        return self::$NestedXmlMapsRequest = new Schema(
            id: 'aws.protocoltests.restxml#NestedXmlMapsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapsRequest$nestedMap', type: ShapeType::Map, memberName: 'nestedMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedMap$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))),
                new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapsRequest$flatNestedMap', type: ShapeType::Map, memberName: 'flatNestedMap', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedMap$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class)), traits: [3 => true])
            ],
            targetClass: NestedXmlMapsRequest::class
        );
    }

    public static function nestedXmlMapsResponse(): Schema
    {
        if (isset(self::$NestedXmlMapsResponse)) {
            return self::$NestedXmlMapsResponse;
        }

        return self::$NestedXmlMapsResponse = new Schema(
            id: 'aws.protocoltests.restxml#NestedXmlMapsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapsResponse$nestedMap', type: ShapeType::Map, memberName: 'nestedMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedMap$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))),
                new Schema(id: 'aws.protocoltests.restxml#NestedXmlMapsResponse$flatNestedMap', type: ShapeType::Map, memberName: 'flatNestedMap', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.restxml#NestedMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#NestedMap$value', type: ShapeType::Map, memberName: 'value', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class)), traits: [3 => true])
            ],
            targetClass: NestedXmlMapsResponse::class
        );
    }

    public static function noInputAndOutputOutput(): Schema
    {
        if (isset(self::$NoInputAndOutputOutput)) {
            return self::$NoInputAndOutputOutput;
        }

        return self::$NoInputAndOutputOutput = new Schema(
            id: 'aws.protocoltests.restxml#NoInputAndOutputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: NoInputAndOutputOutput::class
        );
    }

    public static function nullAndEmptyHeadersIO(): Schema
    {
        if (isset(self::$NullAndEmptyHeadersIO)) {
            return self::$NullAndEmptyHeadersIO;
        }

        return self::$NullAndEmptyHeadersIO = new Schema(
            id: 'aws.protocoltests.restxml#NullAndEmptyHeadersIO',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#NullAndEmptyHeadersIO$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [5 => 'X-A']),
                new Schema(id: 'aws.protocoltests.restxml#NullAndEmptyHeadersIO$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [5 => 'X-B']),
                new Schema(id: 'aws.protocoltests.restxml#NullAndEmptyHeadersIO$c', type: ShapeType::List_, memberName: 'c', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [5 => 'X-C'])
            ],
            targetClass: NullAndEmptyHeadersIO::class
        );
    }

    public static function omitsNullSerializesEmptyStringInput(): Schema
    {
        if (isset(self::$OmitsNullSerializesEmptyStringInput)) {
            return self::$OmitsNullSerializesEmptyStringInput;
        }

        return self::$OmitsNullSerializesEmptyStringInput = new Schema(
            id: 'aws.protocoltests.restxml#OmitsNullSerializesEmptyStringInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#OmitsNullSerializesEmptyStringInput$nullValue', type: ShapeType::String_, memberName: 'nullValue', memberIndex: 0, traits: [9 => 'Null']),
                new Schema(id: 'aws.protocoltests.restxml#OmitsNullSerializesEmptyStringInput$emptyString', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 1, traits: [9 => 'Empty'])
            ],
            targetClass: OmitsNullSerializesEmptyStringInput::class
        );
    }

    public static function payloadWithXmlName(): Schema
    {
        if (isset(self::$PayloadWithXmlName)) {
            return self::$PayloadWithXmlName;
        }

        return self::$PayloadWithXmlName = new Schema(
            id: 'aws.protocoltests.restxml#PayloadWithXmlName',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlName$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)
            ], traits: [0 => 'Hello'],
            targetClass: PayloadWithXmlName::class
        );
    }

    public static function payloadWithXmlNamespace(): Schema
    {
        if (isset(self::$PayloadWithXmlNamespace)) {
            return self::$PayloadWithXmlNamespace;
        }

        return self::$PayloadWithXmlNamespace = new Schema(
            id: 'aws.protocoltests.restxml#PayloadWithXmlNamespace',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlNamespace$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)
            ], traits: [1 => ['uri' => 'http://foo.com']],
            targetClass: PayloadWithXmlNamespace::class
        );
    }

    public static function payloadWithXmlNamespaceAndPrefix(): Schema
    {
        if (isset(self::$PayloadWithXmlNamespaceAndPrefix)) {
            return self::$PayloadWithXmlNamespaceAndPrefix;
        }

        return self::$PayloadWithXmlNamespaceAndPrefix = new Schema(
            id: 'aws.protocoltests.restxml#PayloadWithXmlNamespaceAndPrefix',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#PayloadWithXmlNamespaceAndPrefix$name', type: ShapeType::String_, memberName: 'name', memberIndex: 0)
            ], traits: [1 => ['uri' => 'http://foo.com', 'prefix' => 'baz']],
            targetClass: PayloadWithXmlNamespaceAndPrefix::class
        );
    }

    public static function putWithContentEncodingInput(): Schema
    {
        if (isset(self::$PutWithContentEncodingInput)) {
            return self::$PutWithContentEncodingInput;
        }

        return self::$PutWithContentEncodingInput = new Schema(
            id: 'aws.protocoltests.restxml#PutWithContentEncodingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#PutWithContentEncodingInput$encoding', type: ShapeType::String_, memberName: 'encoding', memberIndex: 0, traits: [5 => 'Content-Encoding']),
                new Schema(id: 'aws.protocoltests.restxml#PutWithContentEncodingInput$data', type: ShapeType::String_, memberName: 'data', memberIndex: 1)
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
            id: 'aws.protocoltests.restxml#QueryIdempotencyTokenAutoFillInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#QueryIdempotencyTokenAutoFillInput$token', type: ShapeType::String_, memberName: 'token', memberIndex: 0, traits: [9 => 'token', 19 => true])
            ],
            targetClass: QueryIdempotencyTokenAutoFillInput::class
        );
    }

    public static function queryParamsAsStringListMapInput(): Schema
    {
        if (isset(self::$QueryParamsAsStringListMapInput)) {
            return self::$QueryParamsAsStringListMapInput;
        }

        return self::$QueryParamsAsStringListMapInput = new Schema(
            id: 'aws.protocoltests.restxml#QueryParamsAsStringListMapInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#QueryParamsAsStringListMapInput$qux', type: ShapeType::String_, memberName: 'qux', memberIndex: 0, traits: [9 => 'corge']),
                new Schema(id: 'aws.protocoltests.restxml#QueryParamsAsStringListMapInput$foo', type: ShapeType::Map, memberName: 'foo', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#StringListMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringListMap$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), traits: [10 => true])
            ],
            targetClass: QueryParamsAsStringListMapInput::class
        );
    }

    public static function queryPrecedenceInput(): Schema
    {
        if (isset(self::$QueryPrecedenceInput)) {
            return self::$QueryPrecedenceInput;
        }

        return self::$QueryPrecedenceInput = new Schema(
            id: 'aws.protocoltests.restxml#QueryPrecedenceInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#QueryPrecedenceInput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [9 => 'bar']),
                new Schema(id: 'aws.protocoltests.restxml#QueryPrecedenceInput$baz', type: ShapeType::Map, memberName: 'baz', memberIndex: 1, mapKey: new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [10 => true])
            ],
            targetClass: QueryPrecedenceInput::class
        );
    }

    public static function recursiveShapesInputOutputNested1(): Schema
    {
        if (isset(self::$RecursiveShapesInputOutputNested1)) {
            return self::$RecursiveShapesInputOutputNested1;
        }

        return self::$RecursiveShapesInputOutputNested1 = new Schema(
            id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested1::class)], targetClass: RecursiveShapesInputOutputNested2::class)
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
            id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested2',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
            ],
            targetClass: RecursiveShapesInputOutputNested2::class
        );
    }

    public static function recursiveShapesRequest(): Schema
    {
        if (isset(self::$RecursiveShapesRequest)) {
            return self::$RecursiveShapesRequest;
        }

        return self::$RecursiveShapesRequest = new Schema(
            id: 'aws.protocoltests.restxml#RecursiveShapesRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesRequest$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
            ],
            targetClass: RecursiveShapesRequest::class
        );
    }

    public static function recursiveShapesResponse(): Schema
    {
        if (isset(self::$RecursiveShapesResponse)) {
            return self::$RecursiveShapesResponse;
        }

        return self::$RecursiveShapesResponse = new Schema(
            id: 'aws.protocoltests.restxml#RecursiveShapesResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesResponse$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
            ],
            targetClass: RecursiveShapesResponse::class
        );
    }

    public static function simpleScalarPropertiesRequest(): Schema
    {
        if (isset(self::$SimpleScalarPropertiesRequest)) {
            return self::$SimpleScalarPropertiesRequest;
        }

        return self::$SimpleScalarPropertiesRequest = new Schema(
            id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'X-Foo']),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$trueBooleanValue', type: ShapeType::Boolean, memberName: 'trueBooleanValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$falseBooleanValue', type: ShapeType::Boolean, memberName: 'falseBooleanValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 7),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 8),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 9, traits: [0 => 'DoubleDribble'])
            ],
            targetClass: SimpleScalarPropertiesRequest::class
        );
    }

    public static function simpleScalarPropertiesResponse(): Schema
    {
        if (isset(self::$SimpleScalarPropertiesResponse)) {
            return self::$SimpleScalarPropertiesResponse;
        }

        return self::$SimpleScalarPropertiesResponse = new Schema(
            id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'X-Foo']),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$trueBooleanValue', type: ShapeType::Boolean, memberName: 'trueBooleanValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$falseBooleanValue', type: ShapeType::Boolean, memberName: 'falseBooleanValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 7),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 8),
                new Schema(id: 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 9, traits: [0 => 'DoubleDribble'])
            ],
            targetClass: SimpleScalarPropertiesResponse::class
        );
    }

    public static function stringPayloadInput(): Schema
    {
        if (isset(self::$StringPayloadInput)) {
            return self::$StringPayloadInput;
        }

        return self::$StringPayloadInput = new Schema(
            id: 'aws.protocoltests.restxml#StringPayloadInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#StringPayloadInput$payload', type: ShapeType::String_, memberName: 'payload', memberIndex: 0, traits: [7 => true])
            ],
            targetClass: StringPayloadInput::class
        );
    }

    public static function structureListMember(): Schema
    {
        if (isset(self::$StructureListMember)) {
            return self::$StructureListMember;
        }

        return self::$StructureListMember = new Schema(
            id: 'aws.protocoltests.restxml#StructureListMember',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']),
                new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])
            ],
            targetClass: StructureListMember::class
        );
    }

    public static function timestampFormatHeadersIO(): Schema
    {
        if (isset(self::$TimestampFormatHeadersIO)) {
            return self::$TimestampFormatHeadersIO;
        }

        return self::$TimestampFormatHeadersIO = new Schema(
            id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO$memberEpochSeconds', type: ShapeType::Timestamp, memberName: 'memberEpochSeconds', memberIndex: 0, traits: [4 => 'epoch-seconds', 5 => 'X-memberEpochSeconds']),
                new Schema(id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO$memberHttpDate', type: ShapeType::Timestamp, memberName: 'memberHttpDate', memberIndex: 1, traits: [4 => 'http-date', 5 => 'X-memberHttpDate']),
                new Schema(id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO$memberDateTime', type: ShapeType::Timestamp, memberName: 'memberDateTime', memberIndex: 2, traits: [4 => 'date-time', 5 => 'X-memberDateTime']),
                new Schema(id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO$defaultFormat', type: ShapeType::Timestamp, memberName: 'defaultFormat', memberIndex: 3, traits: [5 => 'X-defaultFormat']),
                new Schema(id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO$targetEpochSeconds', type: ShapeType::Timestamp, memberName: 'targetEpochSeconds', memberIndex: 4, traits: [4 => 'epoch-seconds', 5 => 'X-targetEpochSeconds']),
                new Schema(id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO$targetHttpDate', type: ShapeType::Timestamp, memberName: 'targetHttpDate', memberIndex: 5, traits: [4 => 'http-date', 5 => 'X-targetHttpDate']),
                new Schema(id: 'aws.protocoltests.restxml#TimestampFormatHeadersIO$targetDateTime', type: ShapeType::Timestamp, memberName: 'targetDateTime', memberIndex: 6, traits: [4 => 'date-time', 5 => 'X-targetDateTime'])
            ],
            targetClass: TimestampFormatHeadersIO::class
        );
    }

    public static function xmlAttributesInMiddlePayloadRequest(): Schema
    {
        if (isset(self::$XmlAttributesInMiddlePayloadRequest)) {
            return self::$XmlAttributesInMiddlePayloadRequest;
        }

        return self::$XmlAttributesInMiddlePayloadRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 2)
            ],
            targetClass: XmlAttributesInMiddlePayloadRequest::class
        );
    }

    public static function xmlAttributesInMiddlePayloadResponse(): Schema
    {
        if (isset(self::$XmlAttributesInMiddlePayloadResponse)) {
            return self::$XmlAttributesInMiddlePayloadResponse;
        }

        return self::$XmlAttributesInMiddlePayloadResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 2)
            ],
            targetClass: XmlAttributesInMiddlePayloadResponse::class
        );
    }

    public static function xmlAttributesInMiddleRequest(): Schema
    {
        if (isset(self::$XmlAttributesInMiddleRequest)) {
            return self::$XmlAttributesInMiddleRequest;
        }

        return self::$XmlAttributesInMiddleRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesInMiddleRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddleRequest$payload', type: ShapeType::Structure, memberName: 'payload', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true]), new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 2)], traits: [7 => true], targetClass: XmlAttributesInMiddlePayloadRequest::class)
            ],
            targetClass: XmlAttributesInMiddleRequest::class
        );
    }

    public static function xmlAttributesInMiddleResponse(): Schema
    {
        if (isset(self::$XmlAttributesInMiddleResponse)) {
            return self::$XmlAttributesInMiddleResponse;
        }

        return self::$XmlAttributesInMiddleResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesInMiddleResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddleResponse$payload', type: ShapeType::Structure, memberName: 'payload', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true]), new Schema(id: 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 2)], traits: [7 => true], targetClass: XmlAttributesInMiddlePayloadResponse::class)
            ],
            targetClass: XmlAttributesInMiddleResponse::class
        );
    }

    public static function xmlAttributesOnPayloadRequest(): Schema
    {
        if (isset(self::$XmlAttributesOnPayloadRequest)) {
            return self::$XmlAttributesOnPayloadRequest;
        }

        return self::$XmlAttributesOnPayloadRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesOnPayloadRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesOnPayloadRequest$payload', type: ShapeType::Structure, memberName: 'payload', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadRequest$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadRequest$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true])], traits: [7 => true], targetClass: XmlAttributesPayloadRequest::class)
            ],
            targetClass: XmlAttributesOnPayloadRequest::class
        );
    }

    public static function xmlAttributesOnPayloadResponse(): Schema
    {
        if (isset(self::$XmlAttributesOnPayloadResponse)) {
            return self::$XmlAttributesOnPayloadResponse;
        }

        return self::$XmlAttributesOnPayloadResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesOnPayloadResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesOnPayloadResponse$payload', type: ShapeType::Structure, memberName: 'payload', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadResponse$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadResponse$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true])], traits: [7 => true], targetClass: XmlAttributesPayloadResponse::class)
            ],
            targetClass: XmlAttributesOnPayloadResponse::class
        );
    }

    public static function xmlAttributesPayloadRequest(): Schema
    {
        if (isset(self::$XmlAttributesPayloadRequest)) {
            return self::$XmlAttributesPayloadRequest;
        }

        return self::$XmlAttributesPayloadRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesPayloadRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadRequest$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadRequest$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true])
            ],
            targetClass: XmlAttributesPayloadRequest::class
        );
    }

    public static function xmlAttributesPayloadResponse(): Schema
    {
        if (isset(self::$XmlAttributesPayloadResponse)) {
            return self::$XmlAttributesPayloadResponse;
        }

        return self::$XmlAttributesPayloadResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesPayloadResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadResponse$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesPayloadResponse$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true])
            ],
            targetClass: XmlAttributesPayloadResponse::class
        );
    }

    public static function xmlAttributesRequest(): Schema
    {
        if (isset(self::$XmlAttributesRequest)) {
            return self::$XmlAttributesRequest;
        }

        return self::$XmlAttributesRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesRequest$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesRequest$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true])
            ],
            targetClass: XmlAttributesRequest::class
        );
    }

    public static function xmlAttributesResponse(): Schema
    {
        if (isset(self::$XmlAttributesResponse)) {
            return self::$XmlAttributesResponse;
        }

        return self::$XmlAttributesResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlAttributesResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesResponse$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlAttributesResponse$attr', type: ShapeType::String_, memberName: 'attr', memberIndex: 1, traits: [0 => 'test', 2 => true])
            ],
            targetClass: XmlAttributesResponse::class
        );
    }

    public static function xmlBlobsRequest(): Schema
    {
        if (isset(self::$XmlBlobsRequest)) {
            return self::$XmlBlobsRequest;
        }

        return self::$XmlBlobsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlBlobsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlBlobsRequest$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 0)
            ],
            targetClass: XmlBlobsRequest::class
        );
    }

    public static function xmlBlobsResponse(): Schema
    {
        if (isset(self::$XmlBlobsResponse)) {
            return self::$XmlBlobsResponse;
        }

        return self::$XmlBlobsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlBlobsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlBlobsResponse$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 0)
            ],
            targetClass: XmlBlobsResponse::class
        );
    }

    public static function xmlEmptyBlobsRequest(): Schema
    {
        if (isset(self::$XmlEmptyBlobsRequest)) {
            return self::$XmlEmptyBlobsRequest;
        }

        return self::$XmlEmptyBlobsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyBlobsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyBlobsRequest$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 0)
            ],
            targetClass: XmlEmptyBlobsRequest::class
        );
    }

    public static function xmlEmptyBlobsResponse(): Schema
    {
        if (isset(self::$XmlEmptyBlobsResponse)) {
            return self::$XmlEmptyBlobsResponse;
        }

        return self::$XmlEmptyBlobsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyBlobsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyBlobsResponse$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 0)
            ],
            targetClass: XmlEmptyBlobsResponse::class
        );
    }

    public static function xmlEmptyListsRequest(): Schema
    {
        if (isset(self::$XmlEmptyListsRequest)) {
            return self::$XmlEmptyListsRequest;
        }

        return self::$XmlEmptyListsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyListsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$renamedListMembers', type: ShapeType::List_, memberName: 'renamedListMembers', memberIndex: 8, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'renamed']),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$flattenedList', type: ShapeType::List_, memberName: 'flattenedList', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$flattenedList2', type: ShapeType::List_, memberName: 'flattenedList2', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'customName', 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$flattenedListWithMemberNamespace', type: ShapeType::List_, memberName: 'flattenedListWithMemberNamespace', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithMemberNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'https://xml-member.example.com']]), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$flattenedListWithNamespace', type: ShapeType::List_, memberName: 'flattenedListWithNamespace', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 13, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [0 => 'myStructureList']),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsRequest$flattenedStructureList', type: ShapeType::List_, memberName: 'flattenedStructureList', memberIndex: 14, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [3 => true])
            ],
            targetClass: XmlEmptyListsRequest::class
        );
    }

    public static function xmlEmptyListsResponse(): Schema
    {
        if (isset(self::$XmlEmptyListsResponse)) {
            return self::$XmlEmptyListsResponse;
        }

        return self::$XmlEmptyListsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyListsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$renamedListMembers', type: ShapeType::List_, memberName: 'renamedListMembers', memberIndex: 8, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'renamed']),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$flattenedList', type: ShapeType::List_, memberName: 'flattenedList', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$flattenedList2', type: ShapeType::List_, memberName: 'flattenedList2', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'customName', 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$flattenedListWithMemberNamespace', type: ShapeType::List_, memberName: 'flattenedListWithMemberNamespace', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithMemberNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'https://xml-member.example.com']]), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$flattenedListWithNamespace', type: ShapeType::List_, memberName: 'flattenedListWithNamespace', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 13, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [0 => 'myStructureList']),
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyListsResponse$flattenedStructureList', type: ShapeType::List_, memberName: 'flattenedStructureList', memberIndex: 14, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [3 => true])
            ],
            targetClass: XmlEmptyListsResponse::class
        );
    }

    public static function xmlEmptyMapsRequest(): Schema
    {
        if (isset(self::$XmlEmptyMapsRequest)) {
            return self::$XmlEmptyMapsRequest;
        }

        return self::$XmlEmptyMapsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyMapsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyMapsRequest$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlEmptyMapsRequest::class
        );
    }

    public static function xmlEmptyMapsResponse(): Schema
    {
        if (isset(self::$XmlEmptyMapsResponse)) {
            return self::$XmlEmptyMapsResponse;
        }

        return self::$XmlEmptyMapsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyMapsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyMapsResponse$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlEmptyMapsResponse::class
        );
    }

    public static function xmlEmptyStringsRequest(): Schema
    {
        if (isset(self::$XmlEmptyStringsRequest)) {
            return self::$XmlEmptyStringsRequest;
        }

        return self::$XmlEmptyStringsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyStringsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyStringsRequest$emptyString', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 0)
            ],
            targetClass: XmlEmptyStringsRequest::class
        );
    }

    public static function xmlEmptyStringsResponse(): Schema
    {
        if (isset(self::$XmlEmptyStringsResponse)) {
            return self::$XmlEmptyStringsResponse;
        }

        return self::$XmlEmptyStringsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlEmptyStringsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEmptyStringsResponse$emptyString', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 0)
            ],
            targetClass: XmlEmptyStringsResponse::class
        );
    }

    public static function xmlEnumsRequest(): Schema
    {
        if (isset(self::$XmlEnumsRequest)) {
            return self::$XmlEnumsRequest;
        }

        return self::$XmlEnumsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlEnumsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsRequest$fooEnum1', type: ShapeType::Enum, memberName: 'fooEnum1', memberIndex: 0, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsRequest$fooEnum2', type: ShapeType::Enum, memberName: 'fooEnum2', memberIndex: 1, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsRequest$fooEnum3', type: ShapeType::Enum, memberName: 'fooEnum3', memberIndex: 2, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsRequest$fooEnumList', type: ShapeType::List_, memberName: 'fooEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsRequest$fooEnumSet', type: ShapeType::List_, memberName: 'fooEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumSet$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsRequest$fooEnumMap', type: ShapeType::Map, memberName: 'fooEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))
            ],
            targetClass: XmlEnumsRequest::class
        );
    }

    public static function xmlEnumsResponse(): Schema
    {
        if (isset(self::$XmlEnumsResponse)) {
            return self::$XmlEnumsResponse;
        }

        return self::$XmlEnumsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlEnumsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsResponse$fooEnum1', type: ShapeType::Enum, memberName: 'fooEnum1', memberIndex: 0, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsResponse$fooEnum2', type: ShapeType::Enum, memberName: 'fooEnum2', memberIndex: 1, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsResponse$fooEnum3', type: ShapeType::Enum, memberName: 'fooEnum3', memberIndex: 2, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsResponse$fooEnumList', type: ShapeType::List_, memberName: 'fooEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsResponse$fooEnumSet', type: ShapeType::List_, memberName: 'fooEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumSet$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlEnumsResponse$fooEnumMap', type: ShapeType::Map, memberName: 'fooEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))
            ],
            targetClass: XmlEnumsResponse::class
        );
    }

    public static function xmlIntEnumsRequest(): Schema
    {
        if (isset(self::$XmlIntEnumsRequest)) {
            return self::$XmlIntEnumsRequest;
        }

        return self::$XmlIntEnumsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlIntEnumsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsRequest$intEnum1', type: ShapeType::IntEnum, memberName: 'intEnum1', memberIndex: 0, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsRequest$intEnum2', type: ShapeType::IntEnum, memberName: 'intEnum2', memberIndex: 1, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsRequest$intEnum3', type: ShapeType::IntEnum, memberName: 'intEnum3', memberIndex: 2, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsRequest$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsRequest$intEnumSet', type: ShapeType::List_, memberName: 'intEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumSet$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsRequest$intEnumMap', type: ShapeType::Map, memberName: 'intEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$value', type: ShapeType::IntEnum, memberName: 'value', memberIndex: 1, targetClass: IntegerEnum::class))
            ],
            targetClass: XmlIntEnumsRequest::class
        );
    }

    public static function xmlIntEnumsResponse(): Schema
    {
        if (isset(self::$XmlIntEnumsResponse)) {
            return self::$XmlIntEnumsResponse;
        }

        return self::$XmlIntEnumsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlIntEnumsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsResponse$intEnum1', type: ShapeType::IntEnum, memberName: 'intEnum1', memberIndex: 0, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsResponse$intEnum2', type: ShapeType::IntEnum, memberName: 'intEnum2', memberIndex: 1, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsResponse$intEnum3', type: ShapeType::IntEnum, memberName: 'intEnum3', memberIndex: 2, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsResponse$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsResponse$intEnumSet', type: ShapeType::List_, memberName: 'intEnumSet', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumSet$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlIntEnumsResponse$intEnumMap', type: ShapeType::Map, memberName: 'intEnumMap', memberIndex: 5, mapKey: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$value', type: ShapeType::IntEnum, memberName: 'value', memberIndex: 1, targetClass: IntegerEnum::class))
            ],
            targetClass: XmlIntEnumsResponse::class
        );
    }

    public static function xmlListsRequest(): Schema
    {
        if (isset(self::$XmlListsRequest)) {
            return self::$XmlListsRequest;
        }

        return self::$XmlListsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlListsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$renamedListMembers', type: ShapeType::List_, memberName: 'renamedListMembers', memberIndex: 8, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'renamed']),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$flattenedList', type: ShapeType::List_, memberName: 'flattenedList', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$flattenedList2', type: ShapeType::List_, memberName: 'flattenedList2', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'customName', 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$flattenedListWithMemberNamespace', type: ShapeType::List_, memberName: 'flattenedListWithMemberNamespace', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithMemberNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'https://xml-member.example.com']]), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$flattenedListWithNamespace', type: ShapeType::List_, memberName: 'flattenedListWithNamespace', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 13, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [0 => 'myStructureList']),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsRequest$flattenedStructureList', type: ShapeType::List_, memberName: 'flattenedStructureList', memberIndex: 14, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [3 => true])
            ],
            targetClass: XmlListsRequest::class
        );
    }

    public static function xmlListsResponse(): Schema
    {
        if (isset(self::$XmlListsResponse)) {
            return self::$XmlListsResponse;
        }

        return self::$XmlListsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlListsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: new Schema(id: 'aws.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0))),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$renamedListMembers', type: ShapeType::List_, memberName: 'renamedListMembers', memberIndex: 8, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'renamed']),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$flattenedList', type: ShapeType::List_, memberName: 'flattenedList', memberIndex: 9, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$flattenedList2', type: ShapeType::List_, memberName: 'flattenedList2', memberIndex: 10, listMember: new Schema(id: 'aws.protocoltests.restxml#RenamedListMembers$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [0 => 'item']), traits: [0 => 'customName', 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$flattenedListWithMemberNamespace', type: ShapeType::List_, memberName: 'flattenedListWithMemberNamespace', memberIndex: 11, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithMemberNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'https://xml-member.example.com']]), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$flattenedListWithNamespace', type: ShapeType::List_, memberName: 'flattenedListWithNamespace', memberIndex: 12, listMember: new Schema(id: 'aws.protocoltests.restxml#ListWithNamespace$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [1 => ['uri' => 'https://xml-list.example.com'], 3 => true]),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 13, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [0 => 'myStructureList']),
                new Schema(id: 'aws.protocoltests.restxml#XmlListsResponse$flattenedStructureList', type: ShapeType::List_, memberName: 'flattenedStructureList', memberIndex: 14, listMember: new Schema(id: 'aws.protocoltests.restxml#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [0 => 'value']), new Schema(id: 'aws.protocoltests.restxml#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [0 => 'other'])], traits: [0 => 'item'], targetClass: StructureListMember::class), traits: [3 => true])
            ],
            targetClass: XmlListsResponse::class
        );
    }

    public static function xmlMapWithXmlNamespaceRequest(): Schema
    {
        if (isset(self::$XmlMapWithXmlNamespaceRequest)) {
            return self::$XmlMapWithXmlNamespaceRequest;
        }

        return self::$XmlMapWithXmlNamespaceRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceRequest$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K', 1 => ['uri' => 'https://the-key.example.com']]), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceInputOutputMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V', 1 => ['uri' => 'https://the-value.example.com']]), traits: [0 => 'KVP', 1 => ['uri' => 'https://the-member.example.com']])
            ],
            targetClass: XmlMapWithXmlNamespaceRequest::class
        );
    }

    public static function xmlMapWithXmlNamespaceResponse(): Schema
    {
        if (isset(self::$XmlMapWithXmlNamespaceResponse)) {
            return self::$XmlMapWithXmlNamespaceResponse;
        }

        return self::$XmlMapWithXmlNamespaceResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceResponse$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'K', 1 => ['uri' => 'https://the-key.example.com']]), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceInputOutputMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1, traits: [0 => 'V', 1 => ['uri' => 'https://the-value.example.com']]), traits: [0 => 'KVP', 1 => ['uri' => 'https://the-member.example.com']])
            ],
            targetClass: XmlMapWithXmlNamespaceResponse::class
        );
    }

    public static function xmlMapsRequest(): Schema
    {
        if (isset(self::$XmlMapsRequest)) {
            return self::$XmlMapsRequest;
        }

        return self::$XmlMapsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlMapsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlMapsRequest$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlMapsRequest::class
        );
    }

    public static function xmlMapsResponse(): Schema
    {
        if (isset(self::$XmlMapsResponse)) {
            return self::$XmlMapsResponse;
        }

        return self::$XmlMapsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlMapsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlMapsResponse$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapsInputOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlMapsResponse::class
        );
    }

    public static function xmlMapsXmlNameRequest(): Schema
    {
        if (isset(self::$XmlMapsXmlNameRequest)) {
            return self::$XmlMapsXmlNameRequest;
        }

        return self::$XmlMapsXmlNameRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlMapsXmlNameRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlMapsXmlNameRequest$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapsXmlNameInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'Attribute']), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapsXmlNameInputOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], traits: [0 => 'Setting'], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlMapsXmlNameRequest::class
        );
    }

    public static function xmlMapsXmlNameResponse(): Schema
    {
        if (isset(self::$XmlMapsXmlNameResponse)) {
            return self::$XmlMapsXmlNameResponse;
        }

        return self::$XmlMapsXmlNameResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlMapsXmlNameResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlMapsXmlNameResponse$myMap', type: ShapeType::Map, memberName: 'myMap', memberIndex: 0, mapKey: new Schema(id: 'aws.protocoltests.restxml#XmlMapsXmlNameInputOutputMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0, traits: [0 => 'Attribute']), mapValue: new Schema(id: 'aws.protocoltests.restxml#XmlMapsXmlNameInputOutputMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)], traits: [0 => 'Setting'], targetClass: GreetingStruct::class))
            ],
            targetClass: XmlMapsXmlNameResponse::class
        );
    }

    public static function xmlNamespaceNested(): Schema
    {
        if (isset(self::$XmlNamespaceNested)) {
            return self::$XmlNamespaceNested;
        }

        return self::$XmlNamespaceNested = new Schema(
            id: 'aws.protocoltests.restxml#XmlNamespaceNested',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlNamespaceNested$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [1 => ['uri' => 'http://baz.com', 'prefix' => 'baz']]),
                new Schema(id: 'aws.protocoltests.restxml#XmlNamespaceNested$values', type: ShapeType::List_, memberName: 'values', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.restxml#XmlNamespacedList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'http://bux.com']]), traits: [1 => ['uri' => 'http://qux.com']])
            ], traits: [1 => ['uri' => 'http://foo.com']],
            targetClass: XmlNamespaceNested::class
        );
    }

    public static function xmlNamespacesRequest(): Schema
    {
        if (isset(self::$XmlNamespacesRequest)) {
            return self::$XmlNamespacesRequest;
        }

        return self::$XmlNamespacesRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlNamespacesRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlNamespacesRequest$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlNamespaceNested$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [1 => ['uri' => 'http://baz.com', 'prefix' => 'baz']]), new Schema(id: 'aws.protocoltests.restxml#XmlNamespaceNested$values', type: ShapeType::List_, memberName: 'values', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.restxml#XmlNamespacedList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'http://bux.com']]), traits: [1 => ['uri' => 'http://qux.com']])], traits: [1 => ['uri' => 'http://foo.com']], targetClass: XmlNamespaceNested::class)
            ], traits: [1 => ['uri' => 'http://foo.com']],
            targetClass: XmlNamespacesRequest::class
        );
    }

    public static function xmlNamespacesResponse(): Schema
    {
        if (isset(self::$XmlNamespacesResponse)) {
            return self::$XmlNamespacesResponse;
        }

        return self::$XmlNamespacesResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlNamespacesResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlNamespacesResponse$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlNamespaceNested$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [1 => ['uri' => 'http://baz.com', 'prefix' => 'baz']]), new Schema(id: 'aws.protocoltests.restxml#XmlNamespaceNested$values', type: ShapeType::List_, memberName: 'values', memberIndex: 1, listMember: new Schema(id: 'aws.protocoltests.restxml#XmlNamespacedList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0, traits: [1 => ['uri' => 'http://bux.com']]), traits: [1 => ['uri' => 'http://qux.com']])], traits: [1 => ['uri' => 'http://foo.com']], targetClass: XmlNamespaceNested::class)
            ], traits: [1 => ['uri' => 'http://foo.com']],
            targetClass: XmlNamespacesResponse::class
        );
    }

    public static function xmlNestedUnionStruct(): Schema
    {
        if (isset(self::$XmlNestedUnionStruct)) {
            return self::$XmlNestedUnionStruct;
        }

        return self::$XmlNestedUnionStruct = new Schema(
            id: 'aws.protocoltests.restxml#XmlNestedUnionStruct',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 7)
            ],
            targetClass: XmlNestedUnionStruct::class
        );
    }

    public static function xmlTimestampsRequest(): Schema
    {
        if (isset(self::$XmlTimestampsRequest)) {
            return self::$XmlTimestampsRequest;
        }

        return self::$XmlTimestampsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlTimestampsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsRequest$normal', type: ShapeType::Timestamp, memberName: 'normal', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsRequest$dateTime', type: ShapeType::Timestamp, memberName: 'dateTime', memberIndex: 1, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsRequest$dateTimeOnTarget', type: ShapeType::Timestamp, memberName: 'dateTimeOnTarget', memberIndex: 2, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsRequest$epochSeconds', type: ShapeType::Timestamp, memberName: 'epochSeconds', memberIndex: 3, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsRequest$epochSecondsOnTarget', type: ShapeType::Timestamp, memberName: 'epochSecondsOnTarget', memberIndex: 4, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsRequest$httpDate', type: ShapeType::Timestamp, memberName: 'httpDate', memberIndex: 5, traits: [4 => 'http-date']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsRequest$httpDateOnTarget', type: ShapeType::Timestamp, memberName: 'httpDateOnTarget', memberIndex: 6, traits: [4 => 'http-date'])
            ],
            targetClass: XmlTimestampsRequest::class
        );
    }

    public static function xmlTimestampsResponse(): Schema
    {
        if (isset(self::$XmlTimestampsResponse)) {
            return self::$XmlTimestampsResponse;
        }

        return self::$XmlTimestampsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlTimestampsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsResponse$normal', type: ShapeType::Timestamp, memberName: 'normal', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsResponse$dateTime', type: ShapeType::Timestamp, memberName: 'dateTime', memberIndex: 1, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsResponse$dateTimeOnTarget', type: ShapeType::Timestamp, memberName: 'dateTimeOnTarget', memberIndex: 2, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsResponse$epochSeconds', type: ShapeType::Timestamp, memberName: 'epochSeconds', memberIndex: 3, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsResponse$epochSecondsOnTarget', type: ShapeType::Timestamp, memberName: 'epochSecondsOnTarget', memberIndex: 4, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsResponse$httpDate', type: ShapeType::Timestamp, memberName: 'httpDate', memberIndex: 5, traits: [4 => 'http-date']),
                new Schema(id: 'aws.protocoltests.restxml#XmlTimestampsResponse$httpDateOnTarget', type: ShapeType::Timestamp, memberName: 'httpDateOnTarget', memberIndex: 6, traits: [4 => 'http-date'])
            ],
            targetClass: XmlTimestampsResponse::class
        );
    }

    public static function xmlUnionsRequest(): Schema
    {
        if (isset(self::$XmlUnionsRequest)) {
            return self::$XmlUnionsRequest;
        }

        return self::$XmlUnionsRequest = new Schema(
            id: 'aws.protocoltests.restxml#XmlUnionsRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionsRequest$unionValue', type: ShapeType::Union, memberName: 'unionValue', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 2), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 3), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 4), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 5), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 6), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 7), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$unionValue', type: ShapeType::Union, memberName: 'unionValue', memberIndex: 8, targetClass: XmlUnionShape::class), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$structValue', type: ShapeType::Structure, memberName: 'structValue', memberIndex: 9, targetClass: XmlNestedUnionStruct::class)], targetClass: XmlUnionShape::class)
            ],
            targetClass: XmlUnionsRequest::class
        );
    }

    public static function xmlUnionsResponse(): Schema
    {
        if (isset(self::$XmlUnionsResponse)) {
            return self::$XmlUnionsResponse;
        }

        return self::$XmlUnionsResponse = new Schema(
            id: 'aws.protocoltests.restxml#XmlUnionsResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionsResponse$unionValue', type: ShapeType::Union, memberName: 'unionValue', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 2), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 3), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 4), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 5), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 6), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 7), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$unionValue', type: ShapeType::Union, memberName: 'unionValue', memberIndex: 8, targetClass: XmlUnionShape::class), new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$structValue', type: ShapeType::Structure, memberName: 'structValue', memberIndex: 9, targetClass: XmlNestedUnionStruct::class)], targetClass: XmlUnionShape::class)
            ],
            targetClass: XmlUnionsResponse::class
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

    public static function unionPayload(): Schema
    {
        if (isset(self::$UnionPayload)) {
            return self::$UnionPayload;
        }

        return self::$UnionPayload = new Schema(
            id: 'aws.protocoltests.restxml#UnionPayload',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#UnionPayload$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
            ],
            targetClass: UnionPayload::class
        );
    }

    public static function xmlUnionShape(): Schema
    {
        if (isset(self::$XmlUnionShape)) {
            return self::$XmlUnionShape;
        }

        return self::$XmlUnionShape = new Schema(
            id: 'aws.protocoltests.restxml#XmlUnionShape',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 7),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$unionValue', type: ShapeType::Union, memberName: 'unionValue', memberIndex: 8, targetClass: XmlUnionShape::class),
                new Schema(id: 'aws.protocoltests.restxml#XmlUnionShape$structValue', type: ShapeType::Structure, memberName: 'structValue', memberIndex: 9, members: [new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0), new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1), new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 2), new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 3), new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 4), new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 5), new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 6), new Schema(id: 'aws.protocoltests.restxml#XmlNestedUnionStruct$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 7)], targetClass: XmlNestedUnionStruct::class)
            ],
            targetClass: XmlUnionShape::class
        );
    }

    public static function stringEnum(): Schema
    {
        if (isset(self::$StringEnum)) {
            return self::$StringEnum;
        }

        return self::$StringEnum = new Schema(
            id: 'aws.protocoltests.restxml#StringEnum',
            type: ShapeType::Enum,
            targetClass: StringEnum::class
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
