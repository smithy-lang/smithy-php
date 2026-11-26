<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Exception\ComplexErrorException;
use Smithy\ProtocolTests\RestJson\Exception\ErrorEventException;
use Smithy\ProtocolTests\RestJson\Exception\FooErrorException;
use Smithy\ProtocolTests\RestJson\Exception\InvalidGreetingException;
use Smithy\ProtocolTests\RestJson\Exception\ServiceUnavailableErrorException;
use Smithy\ProtocolTests\RestJson\Input\AllQueryStringTypesInput;
use Smithy\ProtocolTests\RestJson\Input\ConstantAndVariableQueryStringInput;
use Smithy\ProtocolTests\RestJson\Input\ConstantQueryStringInput;
use Smithy\ProtocolTests\RestJson\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\RestJson\Input\DuplexStreamInput;
use Smithy\ProtocolTests\RestJson\Input\DuplexStreamWithInitialMessagesInput;
use Smithy\ProtocolTests\RestJson\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\RestJson\Input\HostLabelInput;
use Smithy\ProtocolTests\RestJson\Input\HttpEmptyPrefixHeadersInput;
use Smithy\ProtocolTests\RestJson\Input\HttpPrefixHeadersInResponseInput;
use Smithy\ProtocolTests\RestJson\Input\HttpPrefixHeadersInput;
use Smithy\ProtocolTests\RestJson\Input\HttpQueryParamsOnlyInput;
use Smithy\ProtocolTests\RestJson\Input\HttpRequestWithFloatLabelsInput;
use Smithy\ProtocolTests\RestJson\Input\HttpRequestWithGreedyLabelInPathInput;
use Smithy\ProtocolTests\RestJson\Input\HttpRequestWithLabelsAndTimestampFormatInput;
use Smithy\ProtocolTests\RestJson\Input\HttpRequestWithLabelsInput;
use Smithy\ProtocolTests\RestJson\Input\HttpRequestWithRegexLiteralInput;
use Smithy\ProtocolTests\RestJson\Input\InputStreamInput;
use Smithy\ProtocolTests\RestJson\Input\InputStreamWithInitialRequestInput;
use Smithy\ProtocolTests\RestJson\Input\MediaTypeHeaderInput;
use Smithy\ProtocolTests\RestJson\Input\OmitsNullSerializesEmptyStringInput;
use Smithy\ProtocolTests\RestJson\Input\OmitsSerializingEmptyListsInput;
use Smithy\ProtocolTests\RestJson\Input\PostUnionWithJsonNameInput;
use Smithy\ProtocolTests\RestJson\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\RestJson\Input\QueryIdempotencyTokenAutoFillInput;
use Smithy\ProtocolTests\RestJson\Input\QueryParamsAsStringListMapInput;
use Smithy\ProtocolTests\RestJson\Input\QueryPrecedenceInput;
use Smithy\ProtocolTests\RestJson\Output\ContentTypeParametersOutput;
use Smithy\ProtocolTests\RestJson\Output\DatetimeOffsetsOutput;
use Smithy\ProtocolTests\RestJson\Output\DuplexStreamOutput;
use Smithy\ProtocolTests\RestJson\Output\DuplexStreamWithInitialMessagesOutput;
use Smithy\ProtocolTests\RestJson\Output\EmptyInputAndEmptyOutputOutput;
use Smithy\ProtocolTests\RestJson\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\RestJson\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\RestJson\Output\HttpEmptyPrefixHeadersOutput;
use Smithy\ProtocolTests\RestJson\Output\HttpPrefixHeadersInResponseOutput;
use Smithy\ProtocolTests\RestJson\Output\HttpPrefixHeadersOutput;
use Smithy\ProtocolTests\RestJson\Output\HttpResponseCodeOutput;
use Smithy\ProtocolTests\RestJson\Output\IgnoreQueryParamsInResponseOutput;
use Smithy\ProtocolTests\RestJson\Output\MediaTypeHeaderOutput;
use Smithy\ProtocolTests\RestJson\Output\NoInputAndOutputOutput;
use Smithy\ProtocolTests\RestJson\Output\OutputStreamOutput;
use Smithy\ProtocolTests\RestJson\Output\OutputStreamWithInitialResponseOutput;
use Smithy\ProtocolTests\RestJson\Output\PostUnionWithJsonNameOutput;
use Smithy\ProtocolTests\RestJson\Output\ResponseCodeRequiredOutput;
use Smithy\ProtocolTests\RestJson\Shape\BlobPayloadEvent;
use Smithy\ProtocolTests\RestJson\Shape\ComplexNestedErrorData;
use Smithy\ProtocolTests\RestJson\Shape\DocumentTypeAsMapValueInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\DocumentTypeAsPayloadInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\DocumentTypeInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\EnumPayloadInput;
use Smithy\ProtocolTests\RestJson\Shape\EventStream;
use Smithy\ProtocolTests\RestJson\Shape\FooEnum;
use Smithy\ProtocolTests\RestJson\Shape\GreetingStruct;
use Smithy\ProtocolTests\RestJson\Shape\HeadersAndExplicitPayloadEvent;
use Smithy\ProtocolTests\RestJson\Shape\HeadersAndImplicitPayloadEvent;
use Smithy\ProtocolTests\RestJson\Shape\HeadersEvent;
use Smithy\ProtocolTests\RestJson\Shape\HttpChecksumRequiredInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\HttpPayloadTraitsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\HttpPayloadWithStructureInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\HttpPayloadWithUnionInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\InputAndOutputWithHeadersIO;
use Smithy\ProtocolTests\RestJson\Shape\IntegerEnum;
use Smithy\ProtocolTests\RestJson\Shape\JsonBlobsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonEnumsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonIntEnumsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonListsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonMapsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonTimestampsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\MyUnion;
use Smithy\ProtocolTests\RestJson\Shape\NestedPayload;
use Smithy\ProtocolTests\RestJson\Shape\NullAndEmptyHeadersIO;
use Smithy\ProtocolTests\RestJson\Shape\PayloadConfig;
use Smithy\ProtocolTests\RestJson\Shape\PayloadStructure;
use Smithy\ProtocolTests\RestJson\Shape\PayloadUnion;
use Smithy\ProtocolTests\RestJson\Shape\RecursiveShapesInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\RecursiveShapesInputOutputNested1;
use Smithy\ProtocolTests\RestJson\Shape\RecursiveShapesInputOutputNested2;
use Smithy\ProtocolTests\RestJson\Shape\ResponseCodeHttpFallbackInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\SharedGreetingStruct;
use Smithy\ProtocolTests\RestJson\Shape\SimpleScalarPropertiesInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\SparseJsonListsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\SparseJsonMapsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\StringEnum;
use Smithy\ProtocolTests\RestJson\Shape\StringPayloadEvent;
use Smithy\ProtocolTests\RestJson\Shape\StringPayloadInput;
use Smithy\ProtocolTests\RestJson\Shape\StructureListMember;
use Smithy\ProtocolTests\RestJson\Shape\StructurePayloadEvent;
use Smithy\ProtocolTests\RestJson\Shape\TestBodyStructureInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TestConfig;
use Smithy\ProtocolTests\RestJson\Shape\TestNoPayloadInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TestPayloadBlobInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TestPayloadStructureInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TimestampFormatHeadersIO;
use Smithy\ProtocolTests\RestJson\Shape\UnionInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\UnionPayload;
use Smithy\ProtocolTests\RestJson\Shape\UnionPayloadEvent;
use Smithy\ProtocolTests\RestJson\Shape\UnionWithJsonName;

/**
 * Centralized schema registry for all shapes in this service.
 *
 * Stores Schema objects as lazy public static properties.
 * Each structure has a dedicated member initializer.
 */
final class Schemas
{
    public static Schema $AllQueryStringTypesInput;
    public static Schema $BlobPayloadEvent;
    public static Schema $ComplexErrorException;
    public static Schema $ComplexNestedErrorData;
    public static Schema $ConstantAndVariableQueryStringInput;
    public static Schema $ConstantQueryStringInput;
    public static Schema $ContentTypeParametersInput;
    public static Schema $ContentTypeParametersOutput;
    public static Schema $DatetimeOffsetsOutput;
    public static Schema $DocumentTypeAsMapValueInputOutput;
    public static Schema $DocumentTypeAsPayloadInputOutput;
    public static Schema $DocumentTypeInputOutput;
    public static Schema $DuplexStreamInput;
    public static Schema $DuplexStreamOutput;
    public static Schema $DuplexStreamWithInitialMessagesInput;
    public static Schema $DuplexStreamWithInitialMessagesOutput;
    public static Schema $EmptyInputAndEmptyOutputInput;
    public static Schema $EmptyInputAndEmptyOutputOutput;
    public static Schema $EnumPayloadInput;
    public static Schema $ErrorEventException;
    public static Schema $FooErrorException;
    public static Schema $FractionalSecondsOutput;
    public static Schema $GreetingWithErrorsOutput;
    public static Schema $HeadersAndExplicitPayloadEvent;
    public static Schema $HeadersAndImplicitPayloadEvent;
    public static Schema $HeadersEvent;
    public static Schema $HostLabelInput;
    public static Schema $HttpChecksumRequiredInputOutput;
    public static Schema $HttpEmptyPrefixHeadersInput;
    public static Schema $HttpEmptyPrefixHeadersOutput;
    public static Schema $HttpPayloadTraitsInputOutput;
    public static Schema $HttpPayloadWithStructureInputOutput;
    public static Schema $HttpPayloadWithUnionInputOutput;
    public static Schema $HttpPrefixHeadersInResponseInput;
    public static Schema $HttpPrefixHeadersInResponseOutput;
    public static Schema $HttpPrefixHeadersInput;
    public static Schema $HttpPrefixHeadersOutput;
    public static Schema $HttpQueryParamsOnlyInput;
    public static Schema $HttpRequestWithFloatLabelsInput;
    public static Schema $HttpRequestWithGreedyLabelInPathInput;
    public static Schema $HttpRequestWithLabelsAndTimestampFormatInput;
    public static Schema $HttpRequestWithLabelsInput;
    public static Schema $HttpRequestWithRegexLiteralInput;
    public static Schema $HttpResponseCodeOutput;
    public static Schema $IgnoreQueryParamsInResponseOutput;
    public static Schema $InputAndOutputWithHeadersIO;
    public static Schema $InputStreamInput;
    public static Schema $InputStreamWithInitialRequestInput;
    public static Schema $InvalidGreetingException;
    public static Schema $JsonBlobsInputOutput;
    public static Schema $JsonEnumsInputOutput;
    public static Schema $JsonIntEnumsInputOutput;
    public static Schema $JsonListsInputOutput;
    public static Schema $JsonMapsInputOutput;
    public static Schema $JsonTimestampsInputOutput;
    public static Schema $MediaTypeHeaderInput;
    public static Schema $MediaTypeHeaderOutput;
    public static Schema $NestedPayload;
    public static Schema $NoInputAndOutputOutput;
    public static Schema $NullAndEmptyHeadersIO;
    public static Schema $OmitsNullSerializesEmptyStringInput;
    public static Schema $OmitsSerializingEmptyListsInput;
    public static Schema $OutputStreamOutput;
    public static Schema $OutputStreamWithInitialResponseOutput;
    public static Schema $PayloadConfig;
    public static Schema $PayloadStructure;
    public static Schema $PostUnionWithJsonNameInput;
    public static Schema $PostUnionWithJsonNameOutput;
    public static Schema $PutWithContentEncodingInput;
    public static Schema $QueryIdempotencyTokenAutoFillInput;
    public static Schema $QueryParamsAsStringListMapInput;
    public static Schema $QueryPrecedenceInput;
    public static Schema $RecursiveShapesInputOutput;
    public static Schema $RecursiveShapesInputOutputNested1;
    public static Schema $RecursiveShapesInputOutputNested2;
    public static Schema $ResponseCodeHttpFallbackInputOutput;
    public static Schema $ResponseCodeRequiredOutput;
    public static Schema $ServiceUnavailableErrorException;
    public static Schema $SimpleScalarPropertiesInputOutput;
    public static Schema $SparseJsonListsInputOutput;
    public static Schema $SparseJsonMapsInputOutput;
    public static Schema $StringPayloadEvent;
    public static Schema $StringPayloadInput;
    public static Schema $StructureListMember;
    public static Schema $StructurePayloadEvent;
    public static Schema $TestBodyStructureInputOutput;
    public static Schema $TestConfig;
    public static Schema $TestNoPayloadInputOutput;
    public static Schema $TestPayloadBlobInputOutput;
    public static Schema $TestPayloadStructureInputOutput;
    public static Schema $TimestampFormatHeadersIO;
    public static Schema $UnionInputOutput;
    public static Schema $UnionPayloadEvent;
    public static Schema $GreetingStruct;
    public static Schema $SharedGreetingStruct;
    public static Schema $EventStream;
    public static Schema $MyUnion;
    public static Schema $PayloadUnion;
    public static Schema $UnionPayload;
    public static Schema $UnionWithJsonName;
    public static Schema $StringEnum;
    public static Schema $FooEnum;
    public static Schema $IntegerEnum;

    public static function allQueryStringTypesInput(): Schema
    {
        if (isset(self::$AllQueryStringTypesInput)) {
            return self::$AllQueryStringTypesInput;
        }

        return self::$AllQueryStringTypesInput = new Schema(
            id: 'aws.protocoltests.restjson#AllQueryStringTypesInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryString', type: ShapeType::String_, memberName: 'queryString', memberIndex: 0, traits: [9 => 'String']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryStringList', type: ShapeType::List_, memberName: 'queryStringList', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [9 => 'StringList']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryStringSet', type: ShapeType::List_, memberName: 'queryStringSet', memberIndex: 2, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [9 => 'StringSet']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryByte', type: ShapeType::Integer, memberName: 'queryByte', memberIndex: 3, traits: [9 => 'Byte']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryShort', type: ShapeType::Integer, memberName: 'queryShort', memberIndex: 4, traits: [9 => 'Short']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryInteger', type: ShapeType::Integer, memberName: 'queryInteger', memberIndex: 5, traits: [9 => 'Integer']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryIntegerList', type: ShapeType::List_, memberName: 'queryIntegerList', memberIndex: 6, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [9 => 'IntegerList']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryIntegerSet', type: ShapeType::List_, memberName: 'queryIntegerSet', memberIndex: 7, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerSet$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [9 => 'IntegerSet']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryLong', type: ShapeType::Long, memberName: 'queryLong', memberIndex: 8, traits: [9 => 'Long']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryFloat', type: ShapeType::Float_, memberName: 'queryFloat', memberIndex: 9, traits: [9 => 'Float']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryDouble', type: ShapeType::Double, memberName: 'queryDouble', memberIndex: 10, traits: [9 => 'Double']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryDoubleList', type: ShapeType::List_, memberName: 'queryDoubleList', memberIndex: 11, listMember: SchemasMembers::$shared['aws.protocoltests.shared#DoubleList$member'] ??= new Schema(id: 'aws.protocoltests.shared#DoubleList$member', type: ShapeType::Double, memberName: 'member', memberIndex: 0), traits: [9 => 'DoubleList']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryBoolean', type: ShapeType::Boolean, memberName: 'queryBoolean', memberIndex: 12, traits: [9 => 'Boolean']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryBooleanList', type: ShapeType::List_, memberName: 'queryBooleanList', memberIndex: 13, listMember: SchemasMembers::$shared['aws.protocoltests.shared#BooleanList$member'] ??= new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0), traits: [9 => 'BooleanList']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryTimestamp', type: ShapeType::Timestamp, memberName: 'queryTimestamp', memberIndex: 14, traits: [9 => 'Timestamp']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryTimestampList', type: ShapeType::List_, memberName: 'queryTimestampList', memberIndex: 15, listMember: SchemasMembers::$shared['aws.protocoltests.shared#TimestampList$member'] ??= new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0), traits: [9 => 'TimestampList']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryEnum', type: ShapeType::Enum, memberName: 'queryEnum', memberIndex: 16, traits: [9 => 'Enum'], targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryEnumList', type: ShapeType::List_, memberName: 'queryEnumList', memberIndex: 17, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class), traits: [9 => 'EnumList']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryIntegerEnum', type: ShapeType::IntEnum, memberName: 'queryIntegerEnum', memberIndex: 18, traits: [9 => 'IntegerEnum'], targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryIntegerEnumList', type: ShapeType::List_, memberName: 'queryIntegerEnumList', memberIndex: 19, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class), traits: [9 => 'IntegerEnumList']),
                new Schema(id: 'aws.protocoltests.restjson#AllQueryStringTypesInput$queryParamsMapOfStringList', type: ShapeType::Map, memberName: 'queryParamsMapOfStringList', memberIndex: 20, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringListMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringListMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringListMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringListMap$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member']), traits: [10 => true])
            ],
            targetClass: AllQueryStringTypesInput::class
        );
    }

    public static function blobPayloadEvent(): Schema
    {
        if (isset(self::$BlobPayloadEvent)) {
            return self::$BlobPayloadEvent;
        }

        return self::$BlobPayloadEvent = new Schema(
            id: 'aws.protocoltests.restjson#BlobPayloadEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#BlobPayloadEvent$payload', type: ShapeType::Blob, memberName: 'payload', memberIndex: 0)
            ],
            targetClass: BlobPayloadEvent::class
        );
    }

    public static function complexErrorException(): Schema
    {
        if (isset(self::$ComplexErrorException)) {
            return self::$ComplexErrorException;
        }

        return self::$ComplexErrorException = new Schema(
            id: 'aws.protocoltests.restjson#ComplexError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ComplexError$Header', type: ShapeType::String_, memberName: 'Header', memberIndex: 0, traits: [5 => 'X-Header']),
                new Schema(id: 'aws.protocoltests.restjson#ComplexError$TopLevel', type: ShapeType::String_, memberName: 'TopLevel', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restjson#ComplexError$Nested', type: ShapeType::Structure, memberName: 'Nested', memberIndex: 2, members: self::complexNestedErrorData()->getMembers(), targetClass: ComplexNestedErrorData::class)
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
            id: 'aws.protocoltests.restjson#ComplexNestedErrorData',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ComplexNestedErrorData$Foo', type: ShapeType::String_, memberName: 'Foo', memberIndex: 0, traits: [12 => 'Fooooo'])
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
            id: 'aws.protocoltests.restjson#ConstantAndVariableQueryStringInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ConstantAndVariableQueryStringInput$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 0, traits: [9 => 'baz']),
                new Schema(id: 'aws.protocoltests.restjson#ConstantAndVariableQueryStringInput$maybeSet', type: ShapeType::String_, memberName: 'maybeSet', memberIndex: 1, traits: [9 => 'maybeSet'])
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
            id: 'aws.protocoltests.restjson#ConstantQueryStringInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ConstantQueryStringInput$hello', type: ShapeType::String_, memberName: 'hello', memberIndex: 0, traits: [6 => true, 14 => true])
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
            id: 'aws.protocoltests.restjson#ContentTypeParametersInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ContentTypeParametersInput$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 0)
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
            id: 'aws.protocoltests.restjson#ContentTypeParametersOutput',
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
            id: 'aws.protocoltests.restjson#DatetimeOffsetsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DatetimeOffsetsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
            ],
            targetClass: DatetimeOffsetsOutput::class
        );
    }

    public static function documentTypeAsMapValueInputOutput(): Schema
    {
        if (isset(self::$DocumentTypeAsMapValueInputOutput)) {
            return self::$DocumentTypeAsMapValueInputOutput;
        }

        return self::$DocumentTypeAsMapValueInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#DocumentTypeAsMapValueInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DocumentTypeAsMapValueInputOutput$docValuedMap', type: ShapeType::Map, memberName: 'docValuedMap', memberIndex: 0, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#DocumentValuedMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#DocumentValuedMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#DocumentValuedMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#DocumentValuedMap$value', type: ShapeType::Document, memberName: 'value', memberIndex: 1))
            ],
            targetClass: DocumentTypeAsMapValueInputOutput::class
        );
    }

    public static function documentTypeAsPayloadInputOutput(): Schema
    {
        if (isset(self::$DocumentTypeAsPayloadInputOutput)) {
            return self::$DocumentTypeAsPayloadInputOutput;
        }

        return self::$DocumentTypeAsPayloadInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#DocumentTypeAsPayloadInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DocumentTypeAsPayloadInputOutput$documentValue', type: ShapeType::Document, memberName: 'documentValue', memberIndex: 0, traits: [7 => true])
            ],
            targetClass: DocumentTypeAsPayloadInputOutput::class
        );
    }

    public static function documentTypeInputOutput(): Schema
    {
        if (isset(self::$DocumentTypeInputOutput)) {
            return self::$DocumentTypeInputOutput;
        }

        return self::$DocumentTypeInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#DocumentTypeInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DocumentTypeInputOutput$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#DocumentTypeInputOutput$documentValue', type: ShapeType::Document, memberName: 'documentValue', memberIndex: 1)
            ],
            targetClass: DocumentTypeInputOutput::class
        );
    }

    public static function duplexStreamInput(): Schema
    {
        if (isset(self::$DuplexStreamInput)) {
            return self::$DuplexStreamInput;
        }

        return self::$DuplexStreamInput = new Schema(
            id: 'aws.protocoltests.restjson#DuplexStreamInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DuplexStreamInput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: DuplexStreamInput::class
        );
    }

    public static function duplexStreamOutput(): Schema
    {
        if (isset(self::$DuplexStreamOutput)) {
            return self::$DuplexStreamOutput;
        }

        return self::$DuplexStreamOutput = new Schema(
            id: 'aws.protocoltests.restjson#DuplexStreamOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DuplexStreamOutput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: DuplexStreamOutput::class
        );
    }

    public static function duplexStreamWithInitialMessagesInput(): Schema
    {
        if (isset(self::$DuplexStreamWithInitialMessagesInput)) {
            return self::$DuplexStreamWithInitialMessagesInput;
        }

        return self::$DuplexStreamWithInitialMessagesInput = new Schema(
            id: 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesInput$initialRequestMember', type: ShapeType::String_, memberName: 'initialRequestMember', memberIndex: 0, traits: [5 => 'initial-request-member', 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesInput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: DuplexStreamWithInitialMessagesInput::class
        );
    }

    public static function duplexStreamWithInitialMessagesOutput(): Schema
    {
        if (isset(self::$DuplexStreamWithInitialMessagesOutput)) {
            return self::$DuplexStreamWithInitialMessagesOutput;
        }

        return self::$DuplexStreamWithInitialMessagesOutput = new Schema(
            id: 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesOutput$initialResponseMember', type: ShapeType::String_, memberName: 'initialResponseMember', memberIndex: 0, traits: [5 => 'initial-response-member', 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesOutput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: DuplexStreamWithInitialMessagesOutput::class
        );
    }

    public static function emptyInputAndEmptyOutputInput(): Schema
    {
        if (isset(self::$EmptyInputAndEmptyOutputInput)) {
            return self::$EmptyInputAndEmptyOutputInput;
        }

        return self::$EmptyInputAndEmptyOutputInput = new Schema(
            id: 'aws.protocoltests.restjson#EmptyInputAndEmptyOutputInput',
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
            id: 'aws.protocoltests.restjson#EmptyInputAndEmptyOutputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: EmptyInputAndEmptyOutputOutput::class
        );
    }

    public static function enumPayloadInput(): Schema
    {
        if (isset(self::$EnumPayloadInput)) {
            return self::$EnumPayloadInput;
        }

        return self::$EnumPayloadInput = new Schema(
            id: 'aws.protocoltests.restjson#EnumPayloadInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#EnumPayloadInput$payload', type: ShapeType::Enum, memberName: 'payload', memberIndex: 0, traits: [7 => true], targetClass: StringEnum::class)
            ],
            targetClass: EnumPayloadInput::class
        );
    }

    public static function errorEventException(): Schema
    {
        if (isset(self::$ErrorEventException)) {
            return self::$ErrorEventException;
        }

        return self::$ErrorEventException = new Schema(
            id: 'aws.protocoltests.restjson#ErrorEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ErrorEvent$message', type: ShapeType::String_, memberName: 'message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: ErrorEventException::class
        );
    }

    public static function fooErrorException(): Schema
    {
        if (isset(self::$FooErrorException)) {
            return self::$FooErrorException;
        }

        return self::$FooErrorException = new Schema(
            id: 'aws.protocoltests.restjson#FooError',
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
            id: 'aws.protocoltests.restjson#FractionalSecondsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#FractionalSecondsOutput$datetime', type: ShapeType::Timestamp, memberName: 'datetime', memberIndex: 0, traits: [4 => 'date-time'])
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
            id: 'aws.protocoltests.restjson#GreetingWithErrorsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#GreetingWithErrorsOutput$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0, traits: [5 => 'X-Greeting'])
            ],
            targetClass: GreetingWithErrorsOutput::class
        );
    }

    public static function headersAndExplicitPayloadEvent(): Schema
    {
        if (isset(self::$HeadersAndExplicitPayloadEvent)) {
            return self::$HeadersAndExplicitPayloadEvent;
        }

        return self::$HeadersAndExplicitPayloadEvent = new Schema(
            id: 'aws.protocoltests.restjson#HeadersAndExplicitPayloadEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HeadersAndExplicitPayloadEvent$header', type: ShapeType::String_, memberName: 'header', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#HeadersAndExplicitPayloadEvent$payload', type: ShapeType::Structure, memberName: 'payload', memberIndex: 1, members: self::payloadStructure()->getMembers(), targetClass: PayloadStructure::class)
            ],
            targetClass: HeadersAndExplicitPayloadEvent::class
        );
    }

    public static function headersAndImplicitPayloadEvent(): Schema
    {
        if (isset(self::$HeadersAndImplicitPayloadEvent)) {
            return self::$HeadersAndImplicitPayloadEvent;
        }

        return self::$HeadersAndImplicitPayloadEvent = new Schema(
            id: 'aws.protocoltests.restjson#HeadersAndImplicitPayloadEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HeadersAndImplicitPayloadEvent$header', type: ShapeType::String_, memberName: 'header', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#HeadersAndImplicitPayloadEvent$payload', type: ShapeType::String_, memberName: 'payload', memberIndex: 1)
            ],
            targetClass: HeadersAndImplicitPayloadEvent::class
        );
    }

    public static function headersEvent(): Schema
    {
        if (isset(self::$HeadersEvent)) {
            return self::$HeadersEvent;
        }

        return self::$HeadersEvent = new Schema(
            id: 'aws.protocoltests.restjson#HeadersEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$booleanHeader', type: ShapeType::Boolean, memberName: 'booleanHeader', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$byteHeader', type: ShapeType::Integer, memberName: 'byteHeader', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$shortHeader', type: ShapeType::Integer, memberName: 'shortHeader', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$intHeader', type: ShapeType::Integer, memberName: 'intHeader', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$longHeader', type: ShapeType::Long, memberName: 'longHeader', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$blobHeader', type: ShapeType::Blob, memberName: 'blobHeader', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$stringHeader', type: ShapeType::String_, memberName: 'stringHeader', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.restjson#HeadersEvent$timestampHeader', type: ShapeType::Timestamp, memberName: 'timestampHeader', memberIndex: 7, traits: [4 => 'date-time'])
            ],
            targetClass: HeadersEvent::class
        );
    }

    public static function hostLabelInput(): Schema
    {
        if (isset(self::$HostLabelInput)) {
            return self::$HostLabelInput;
        }

        return self::$HostLabelInput = new Schema(
            id: 'aws.protocoltests.restjson#HostLabelInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HostLabelInput$label', type: ShapeType::String_, memberName: 'label', memberIndex: 0, traits: [14 => true])
            ],
            targetClass: HostLabelInput::class
        );
    }

    public static function httpChecksumRequiredInputOutput(): Schema
    {
        if (isset(self::$HttpChecksumRequiredInputOutput)) {
            return self::$HttpChecksumRequiredInputOutput;
        }

        return self::$HttpChecksumRequiredInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#HttpChecksumRequiredInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpChecksumRequiredInputOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0)
            ],
            targetClass: HttpChecksumRequiredInputOutput::class
        );
    }

    public static function httpEmptyPrefixHeadersInput(): Schema
    {
        if (isset(self::$HttpEmptyPrefixHeadersInput)) {
            return self::$HttpEmptyPrefixHeadersInput;
        }

        return self::$HttpEmptyPrefixHeadersInput = new Schema(
            id: 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersInput$prefixHeaders', type: ShapeType::Map, memberName: 'prefixHeaders', memberIndex: 0, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => '']),
                new Schema(id: 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersInput$specificHeader', type: ShapeType::String_, memberName: 'specificHeader', memberIndex: 1, traits: [5 => 'hello'])
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
            id: 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersOutput$prefixHeaders', type: ShapeType::Map, memberName: 'prefixHeaders', memberIndex: 0, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => '']),
                new Schema(id: 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersOutput$specificHeader', type: ShapeType::String_, memberName: 'specificHeader', memberIndex: 1, traits: [5 => 'hello'])
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
            id: 'aws.protocoltests.restjson#HttpPayloadTraitsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpPayloadTraitsInputOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'X-Foo']),
                new Schema(id: 'aws.protocoltests.restjson#HttpPayloadTraitsInputOutput$blob', type: ShapeType::Blob, memberName: 'blob', memberIndex: 1, traits: [7 => true])
            ],
            targetClass: HttpPayloadTraitsInputOutput::class
        );
    }

    public static function httpPayloadWithStructureInputOutput(): Schema
    {
        if (isset(self::$HttpPayloadWithStructureInputOutput)) {
            return self::$HttpPayloadWithStructureInputOutput;
        }

        return self::$HttpPayloadWithStructureInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#HttpPayloadWithStructureInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpPayloadWithStructureInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: self::nestedPayload()->getMembers(), traits: [7 => true], targetClass: NestedPayload::class)
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
            id: 'aws.protocoltests.restjson#HttpPayloadWithUnionInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpPayloadWithUnionInputOutput$nested', type: ShapeType::Union, memberName: 'nested', memberIndex: 0, members: self::unionPayload()->getMembers(), traits: [7 => true], targetClass: UnionPayload::class)
            ],
            targetClass: HttpPayloadWithUnionInputOutput::class
        );
    }

    public static function httpPrefixHeadersInResponseInput(): Schema
    {
        if (isset(self::$HttpPrefixHeadersInResponseInput)) {
            return self::$HttpPrefixHeadersInResponseInput;
        }

        return self::$HttpPrefixHeadersInResponseInput = new Schema(
            id: 'aws.protocoltests.restjson#HttpPrefixHeadersInResponseInput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: HttpPrefixHeadersInResponseInput::class
        );
    }

    public static function httpPrefixHeadersInResponseOutput(): Schema
    {
        if (isset(self::$HttpPrefixHeadersInResponseOutput)) {
            return self::$HttpPrefixHeadersInResponseOutput;
        }

        return self::$HttpPrefixHeadersInResponseOutput = new Schema(
            id: 'aws.protocoltests.restjson#HttpPrefixHeadersInResponseOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpPrefixHeadersInResponseOutput$prefixHeaders', type: ShapeType::Map, memberName: 'prefixHeaders', memberIndex: 0, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => ''])
            ],
            targetClass: HttpPrefixHeadersInResponseOutput::class
        );
    }

    public static function httpPrefixHeadersInput(): Schema
    {
        if (isset(self::$HttpPrefixHeadersInput)) {
            return self::$HttpPrefixHeadersInput;
        }

        return self::$HttpPrefixHeadersInput = new Schema(
            id: 'aws.protocoltests.restjson#HttpPrefixHeadersInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpPrefixHeadersInput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'x-foo']),
                new Schema(id: 'aws.protocoltests.restjson#HttpPrefixHeadersInput$fooMap', type: ShapeType::Map, memberName: 'fooMap', memberIndex: 1, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => 'x-foo-'])
            ],
            targetClass: HttpPrefixHeadersInput::class
        );
    }

    public static function httpPrefixHeadersOutput(): Schema
    {
        if (isset(self::$HttpPrefixHeadersOutput)) {
            return self::$HttpPrefixHeadersOutput;
        }

        return self::$HttpPrefixHeadersOutput = new Schema(
            id: 'aws.protocoltests.restjson#HttpPrefixHeadersOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpPrefixHeadersOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'x-foo']),
                new Schema(id: 'aws.protocoltests.restjson#HttpPrefixHeadersOutput$fooMap', type: ShapeType::Map, memberName: 'fooMap', memberIndex: 1, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [8 => 'x-foo-'])
            ],
            targetClass: HttpPrefixHeadersOutput::class
        );
    }

    public static function httpQueryParamsOnlyInput(): Schema
    {
        if (isset(self::$HttpQueryParamsOnlyInput)) {
            return self::$HttpQueryParamsOnlyInput;
        }

        return self::$HttpQueryParamsOnlyInput = new Schema(
            id: 'aws.protocoltests.restjson#HttpQueryParamsOnlyInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpQueryParamsOnlyInput$queryMap', type: ShapeType::Map, memberName: 'queryMap', memberIndex: 0, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#QueryMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#QueryMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#QueryMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#QueryMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [10 => true])
            ],
            targetClass: HttpQueryParamsOnlyInput::class
        );
    }

    public static function httpRequestWithFloatLabelsInput(): Schema
    {
        if (isset(self::$HttpRequestWithFloatLabelsInput)) {
            return self::$HttpRequestWithFloatLabelsInput;
        }

        return self::$HttpRequestWithFloatLabelsInput = new Schema(
            id: 'aws.protocoltests.restjson#HttpRequestWithFloatLabelsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithFloatLabelsInput$float', type: ShapeType::Float_, memberName: 'float', memberIndex: 0, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithFloatLabelsInput$double', type: ShapeType::Double, memberName: 'double', memberIndex: 1, traits: [6 => true, 14 => true])
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
            id: 'aws.protocoltests.restjson#HttpRequestWithGreedyLabelInPathInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithGreedyLabelInPathInput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithGreedyLabelInPathInput$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 1, traits: [6 => true, 14 => true])
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
            id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput$memberEpochSeconds', type: ShapeType::Timestamp, memberName: 'memberEpochSeconds', memberIndex: 0, traits: [4 => 'epoch-seconds', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput$memberHttpDate', type: ShapeType::Timestamp, memberName: 'memberHttpDate', memberIndex: 1, traits: [4 => 'http-date', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput$memberDateTime', type: ShapeType::Timestamp, memberName: 'memberDateTime', memberIndex: 2, traits: [4 => 'date-time', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput$defaultFormat', type: ShapeType::Timestamp, memberName: 'defaultFormat', memberIndex: 3, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput$targetEpochSeconds', type: ShapeType::Timestamp, memberName: 'targetEpochSeconds', memberIndex: 4, traits: [4 => 'epoch-seconds', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput$targetHttpDate', type: ShapeType::Timestamp, memberName: 'targetHttpDate', memberIndex: 5, traits: [4 => 'http-date', 6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput$targetDateTime', type: ShapeType::Timestamp, memberName: 'targetDateTime', memberIndex: 6, traits: [4 => 'date-time', 6 => true, 14 => true])
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
            id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$string', type: ShapeType::String_, memberName: 'string', memberIndex: 0, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$short', type: ShapeType::Integer, memberName: 'short', memberIndex: 1, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$integer', type: ShapeType::Integer, memberName: 'integer', memberIndex: 2, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$long', type: ShapeType::Long, memberName: 'long', memberIndex: 3, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$float', type: ShapeType::Float_, memberName: 'float', memberIndex: 4, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$double', type: ShapeType::Double, memberName: 'double', memberIndex: 5, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$boolean', type: ShapeType::Boolean, memberName: 'boolean', memberIndex: 6, traits: [6 => true, 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithLabelsInput$timestamp', type: ShapeType::Timestamp, memberName: 'timestamp', memberIndex: 7, traits: [6 => true, 14 => true])
            ],
            targetClass: HttpRequestWithLabelsInput::class
        );
    }

    public static function httpRequestWithRegexLiteralInput(): Schema
    {
        if (isset(self::$HttpRequestWithRegexLiteralInput)) {
            return self::$HttpRequestWithRegexLiteralInput;
        }

        return self::$HttpRequestWithRegexLiteralInput = new Schema(
            id: 'aws.protocoltests.restjson#HttpRequestWithRegexLiteralInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpRequestWithRegexLiteralInput$str', type: ShapeType::String_, memberName: 'str', memberIndex: 0, traits: [6 => true, 14 => true])
            ],
            targetClass: HttpRequestWithRegexLiteralInput::class
        );
    }

    public static function httpResponseCodeOutput(): Schema
    {
        if (isset(self::$HttpResponseCodeOutput)) {
            return self::$HttpResponseCodeOutput;
        }

        return self::$HttpResponseCodeOutput = new Schema(
            id: 'aws.protocoltests.restjson#HttpResponseCodeOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#HttpResponseCodeOutput$Status', type: ShapeType::Integer, memberName: 'Status', memberIndex: 0, traits: [11 => true])
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
            id: 'aws.protocoltests.restjson#IgnoreQueryParamsInResponseOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#IgnoreQueryParamsInResponseOutput$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 0, traits: [9 => 'baz'])
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
            id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerString', type: ShapeType::String_, memberName: 'headerString', memberIndex: 0, traits: [5 => 'X-String']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerByte', type: ShapeType::Integer, memberName: 'headerByte', memberIndex: 1, traits: [5 => 'X-Byte']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerShort', type: ShapeType::Integer, memberName: 'headerShort', memberIndex: 2, traits: [5 => 'X-Short']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerInteger', type: ShapeType::Integer, memberName: 'headerInteger', memberIndex: 3, traits: [5 => 'X-Integer']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerLong', type: ShapeType::Long, memberName: 'headerLong', memberIndex: 4, traits: [5 => 'X-Long']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerFloat', type: ShapeType::Float_, memberName: 'headerFloat', memberIndex: 5, traits: [5 => 'X-Float']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerDouble', type: ShapeType::Double, memberName: 'headerDouble', memberIndex: 6, traits: [5 => 'X-Double']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerTrueBool', type: ShapeType::Boolean, memberName: 'headerTrueBool', memberIndex: 7, traits: [5 => 'X-Boolean1']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerFalseBool', type: ShapeType::Boolean, memberName: 'headerFalseBool', memberIndex: 8, traits: [5 => 'X-Boolean2']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerStringList', type: ShapeType::List_, memberName: 'headerStringList', memberIndex: 9, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [5 => 'X-StringList']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerStringSet', type: ShapeType::List_, memberName: 'headerStringSet', memberIndex: 10, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [5 => 'X-StringSet']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerIntegerList', type: ShapeType::List_, memberName: 'headerIntegerList', memberIndex: 11, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [5 => 'X-IntegerList']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerBooleanList', type: ShapeType::List_, memberName: 'headerBooleanList', memberIndex: 12, listMember: SchemasMembers::$shared['aws.protocoltests.shared#BooleanList$member'] ??= new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0), traits: [5 => 'X-BooleanList']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerTimestampList', type: ShapeType::List_, memberName: 'headerTimestampList', memberIndex: 13, listMember: SchemasMembers::$shared['aws.protocoltests.shared#TimestampList$member'] ??= new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0), traits: [5 => 'X-TimestampList']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerEnum', type: ShapeType::Enum, memberName: 'headerEnum', memberIndex: 14, traits: [5 => 'X-Enum'], targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerEnumList', type: ShapeType::List_, memberName: 'headerEnumList', memberIndex: 15, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class), traits: [5 => 'X-EnumList']),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerIntegerEnum', type: ShapeType::IntEnum, memberName: 'headerIntegerEnum', memberIndex: 16, traits: [5 => 'X-IntegerEnum'], targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO$headerIntegerEnumList', type: ShapeType::List_, memberName: 'headerIntegerEnumList', memberIndex: 17, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class), traits: [5 => 'X-IntegerEnumList'])
            ],
            targetClass: InputAndOutputWithHeadersIO::class
        );
    }

    public static function inputStreamInput(): Schema
    {
        if (isset(self::$InputStreamInput)) {
            return self::$InputStreamInput;
        }

        return self::$InputStreamInput = new Schema(
            id: 'aws.protocoltests.restjson#InputStreamInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#InputStreamInput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: InputStreamInput::class
        );
    }

    public static function inputStreamWithInitialRequestInput(): Schema
    {
        if (isset(self::$InputStreamWithInitialRequestInput)) {
            return self::$InputStreamWithInitialRequestInput;
        }

        return self::$InputStreamWithInitialRequestInput = new Schema(
            id: 'aws.protocoltests.restjson#InputStreamWithInitialRequestInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#InputStreamWithInitialRequestInput$initialRequestMember', type: ShapeType::String_, memberName: 'initialRequestMember', memberIndex: 0, traits: [5 => 'initial-request-member', 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#InputStreamWithInitialRequestInput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: InputStreamWithInitialRequestInput::class
        );
    }

    public static function invalidGreetingException(): Schema
    {
        if (isset(self::$InvalidGreetingException)) {
            return self::$InvalidGreetingException;
        }

        return self::$InvalidGreetingException = new Schema(
            id: 'aws.protocoltests.restjson#InvalidGreeting',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#InvalidGreeting$Message', type: ShapeType::String_, memberName: 'Message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: InvalidGreetingException::class
        );
    }

    public static function jsonBlobsInputOutput(): Schema
    {
        if (isset(self::$JsonBlobsInputOutput)) {
            return self::$JsonBlobsInputOutput;
        }

        return self::$JsonBlobsInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#JsonBlobsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#JsonBlobsInputOutput$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 0)
            ],
            targetClass: JsonBlobsInputOutput::class
        );
    }

    public static function jsonEnumsInputOutput(): Schema
    {
        if (isset(self::$JsonEnumsInputOutput)) {
            return self::$JsonEnumsInputOutput;
        }

        return self::$JsonEnumsInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#JsonEnumsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#JsonEnumsInputOutput$fooEnum1', type: ShapeType::Enum, memberName: 'fooEnum1', memberIndex: 0, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#JsonEnumsInputOutput$fooEnum2', type: ShapeType::Enum, memberName: 'fooEnum2', memberIndex: 1, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#JsonEnumsInputOutput$fooEnum3', type: ShapeType::Enum, memberName: 'fooEnum3', memberIndex: 2, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#JsonEnumsInputOutput$fooEnumList', type: ShapeType::List_, memberName: 'fooEnumList', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restjson#JsonEnumsInputOutput$fooEnumSet', type: ShapeType::List_, memberName: 'fooEnumSet', memberIndex: 4, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumSet$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restjson#JsonEnumsInputOutput$fooEnumMap', type: ShapeType::Map, memberName: 'fooEnumMap', memberIndex: 5, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumMap$value', type: ShapeType::Enum, memberName: 'value', memberIndex: 1, targetClass: FooEnum::class))
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
            id: 'aws.protocoltests.restjson#JsonIntEnumsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#JsonIntEnumsInputOutput$integerEnum1', type: ShapeType::IntEnum, memberName: 'integerEnum1', memberIndex: 0, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#JsonIntEnumsInputOutput$integerEnum2', type: ShapeType::IntEnum, memberName: 'integerEnum2', memberIndex: 1, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#JsonIntEnumsInputOutput$integerEnum3', type: ShapeType::IntEnum, memberName: 'integerEnum3', memberIndex: 2, targetClass: IntegerEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#JsonIntEnumsInputOutput$integerEnumList', type: ShapeType::List_, memberName: 'integerEnumList', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restjson#JsonIntEnumsInputOutput$integerEnumSet', type: ShapeType::List_, memberName: 'integerEnumSet', memberIndex: 4, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumSet$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restjson#JsonIntEnumsInputOutput$integerEnumMap', type: ShapeType::Map, memberName: 'integerEnumMap', memberIndex: 5, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumMap$value', type: ShapeType::IntEnum, memberName: 'value', memberIndex: 1, targetClass: IntegerEnum::class))
            ],
            targetClass: JsonIntEnumsInputOutput::class
        );
    }

    public static function jsonListsInputOutput(): Schema
    {
        if (isset(self::$JsonListsInputOutput)) {
            return self::$JsonListsInputOutput;
        }

        return self::$JsonListsInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#JsonListsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$stringList', type: ShapeType::List_, memberName: 'stringList', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$stringSet', type: ShapeType::List_, memberName: 'stringSet', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$integerList', type: ShapeType::List_, memberName: 'integerList', memberIndex: 2, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$booleanList', type: ShapeType::List_, memberName: 'booleanList', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.shared#BooleanList$member'] ??= new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$timestampList', type: ShapeType::List_, memberName: 'timestampList', memberIndex: 4, listMember: SchemasMembers::$shared['aws.protocoltests.shared#TimestampList$member'] ??= new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$enumList', type: ShapeType::List_, memberName: 'enumList', memberIndex: 5, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class)),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$intEnumList', type: ShapeType::List_, memberName: 'intEnumList', memberIndex: 6, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class)),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$nestedStringList', type: ShapeType::List_, memberName: 'nestedStringList', memberIndex: 7, listMember: SchemasMembers::$shared['aws.protocoltests.shared#NestedStringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#NestedStringList$member', type: ShapeType::List_, memberName: 'member', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'])),
                new Schema(id: 'aws.protocoltests.restjson#JsonListsInputOutput$structureList', type: ShapeType::List_, memberName: 'structureList', memberIndex: 8, listMember: SchemasMembers::$shared['aws.protocoltests.restjson#StructureList$member'] ??= new Schema(id: 'aws.protocoltests.restjson#StructureList$member', type: ShapeType::Structure, memberName: 'member', memberIndex: 0, members: self::structureListMember()->getMembers(), targetClass: StructureListMember::class), traits: [12 => 'myStructureList'])
            ],
            targetClass: JsonListsInputOutput::class
        );
    }

    public static function jsonMapsInputOutput(): Schema
    {
        if (isset(self::$JsonMapsInputOutput)) {
            return self::$JsonMapsInputOutput;
        }

        return self::$JsonMapsInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#JsonMapsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#JsonMapsInputOutput$denseStructMap', type: ShapeType::Map, memberName: 'denseStructMap', memberIndex: 0, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#DenseStructMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseStructMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#DenseStructMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseStructMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: self::sharedGreetingStruct()->getMembers(), targetClass: SharedGreetingStruct::class)),
                new Schema(id: 'aws.protocoltests.restjson#JsonMapsInputOutput$denseNumberMap', type: ShapeType::Map, memberName: 'denseNumberMap', memberIndex: 1, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#DenseNumberMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseNumberMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#DenseNumberMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseNumberMap$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.restjson#JsonMapsInputOutput$denseBooleanMap', type: ShapeType::Map, memberName: 'denseBooleanMap', memberIndex: 2, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#DenseBooleanMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseBooleanMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#DenseBooleanMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseBooleanMap$value', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.restjson#JsonMapsInputOutput$denseStringMap', type: ShapeType::Map, memberName: 'denseStringMap', memberIndex: 3, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#DenseStringMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#DenseStringMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.restjson#JsonMapsInputOutput$denseSetMap', type: ShapeType::Map, memberName: 'denseSetMap', memberIndex: 4, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#DenseSetMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseSetMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#DenseSetMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#DenseSetMap$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)))
            ],
            targetClass: JsonMapsInputOutput::class
        );
    }

    public static function jsonTimestampsInputOutput(): Schema
    {
        if (isset(self::$JsonTimestampsInputOutput)) {
            return self::$JsonTimestampsInputOutput;
        }

        return self::$JsonTimestampsInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput$normal', type: ShapeType::Timestamp, memberName: 'normal', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput$dateTime', type: ShapeType::Timestamp, memberName: 'dateTime', memberIndex: 1, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput$dateTimeOnTarget', type: ShapeType::Timestamp, memberName: 'dateTimeOnTarget', memberIndex: 2, traits: [4 => 'date-time']),
                new Schema(id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput$epochSeconds', type: ShapeType::Timestamp, memberName: 'epochSeconds', memberIndex: 3, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput$epochSecondsOnTarget', type: ShapeType::Timestamp, memberName: 'epochSecondsOnTarget', memberIndex: 4, traits: [4 => 'epoch-seconds']),
                new Schema(id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput$httpDate', type: ShapeType::Timestamp, memberName: 'httpDate', memberIndex: 5, traits: [4 => 'http-date']),
                new Schema(id: 'aws.protocoltests.restjson#JsonTimestampsInputOutput$httpDateOnTarget', type: ShapeType::Timestamp, memberName: 'httpDateOnTarget', memberIndex: 6, traits: [4 => 'http-date'])
            ],
            targetClass: JsonTimestampsInputOutput::class
        );
    }

    public static function mediaTypeHeaderInput(): Schema
    {
        if (isset(self::$MediaTypeHeaderInput)) {
            return self::$MediaTypeHeaderInput;
        }

        return self::$MediaTypeHeaderInput = new Schema(
            id: 'aws.protocoltests.restjson#MediaTypeHeaderInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#MediaTypeHeaderInput$json', type: ShapeType::String_, memberName: 'json', memberIndex: 0, traits: [5 => 'X-Json', 13 => 'application/json'])
            ],
            targetClass: MediaTypeHeaderInput::class
        );
    }

    public static function mediaTypeHeaderOutput(): Schema
    {
        if (isset(self::$MediaTypeHeaderOutput)) {
            return self::$MediaTypeHeaderOutput;
        }

        return self::$MediaTypeHeaderOutput = new Schema(
            id: 'aws.protocoltests.restjson#MediaTypeHeaderOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#MediaTypeHeaderOutput$json', type: ShapeType::String_, memberName: 'json', memberIndex: 0, traits: [5 => 'X-Json', 13 => 'application/json'])
            ],
            targetClass: MediaTypeHeaderOutput::class
        );
    }

    public static function nestedPayload(): Schema
    {
        if (isset(self::$NestedPayload)) {
            return self::$NestedPayload;
        }

        return self::$NestedPayload = new Schema(
            id: 'aws.protocoltests.restjson#NestedPayload',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#NestedPayload$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#NestedPayload$name', type: ShapeType::String_, memberName: 'name', memberIndex: 1)
            ],
            targetClass: NestedPayload::class
        );
    }

    public static function noInputAndOutputOutput(): Schema
    {
        if (isset(self::$NoInputAndOutputOutput)) {
            return self::$NoInputAndOutputOutput;
        }

        return self::$NoInputAndOutputOutput = new Schema(
            id: 'aws.protocoltests.restjson#NoInputAndOutputOutput',
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
            id: 'aws.protocoltests.restjson#NullAndEmptyHeadersIO',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#NullAndEmptyHeadersIO$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [5 => 'X-A']),
                new Schema(id: 'aws.protocoltests.restjson#NullAndEmptyHeadersIO$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [5 => 'X-B']),
                new Schema(id: 'aws.protocoltests.restjson#NullAndEmptyHeadersIO$c', type: ShapeType::List_, memberName: 'c', memberIndex: 2, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [5 => 'X-C'])
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
            id: 'aws.protocoltests.restjson#OmitsNullSerializesEmptyStringInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#OmitsNullSerializesEmptyStringInput$nullValue', type: ShapeType::String_, memberName: 'nullValue', memberIndex: 0, traits: [9 => 'Null']),
                new Schema(id: 'aws.protocoltests.restjson#OmitsNullSerializesEmptyStringInput$emptyString', type: ShapeType::String_, memberName: 'emptyString', memberIndex: 1, traits: [9 => 'Empty'])
            ],
            targetClass: OmitsNullSerializesEmptyStringInput::class
        );
    }

    public static function omitsSerializingEmptyListsInput(): Schema
    {
        if (isset(self::$OmitsSerializingEmptyListsInput)) {
            return self::$OmitsSerializingEmptyListsInput;
        }

        return self::$OmitsSerializingEmptyListsInput = new Schema(
            id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput$queryStringList', type: ShapeType::List_, memberName: 'queryStringList', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [9 => 'StringList']),
                new Schema(id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput$queryIntegerList', type: ShapeType::List_, memberName: 'queryIntegerList', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [9 => 'IntegerList']),
                new Schema(id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput$queryDoubleList', type: ShapeType::List_, memberName: 'queryDoubleList', memberIndex: 2, listMember: SchemasMembers::$shared['aws.protocoltests.shared#DoubleList$member'] ??= new Schema(id: 'aws.protocoltests.shared#DoubleList$member', type: ShapeType::Double, memberName: 'member', memberIndex: 0), traits: [9 => 'DoubleList']),
                new Schema(id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput$queryBooleanList', type: ShapeType::List_, memberName: 'queryBooleanList', memberIndex: 3, listMember: SchemasMembers::$shared['aws.protocoltests.shared#BooleanList$member'] ??= new Schema(id: 'aws.protocoltests.shared#BooleanList$member', type: ShapeType::Boolean, memberName: 'member', memberIndex: 0), traits: [9 => 'BooleanList']),
                new Schema(id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput$queryTimestampList', type: ShapeType::List_, memberName: 'queryTimestampList', memberIndex: 4, listMember: SchemasMembers::$shared['aws.protocoltests.shared#TimestampList$member'] ??= new Schema(id: 'aws.protocoltests.shared#TimestampList$member', type: ShapeType::Timestamp, memberName: 'member', memberIndex: 0), traits: [9 => 'TimestampList']),
                new Schema(id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput$queryEnumList', type: ShapeType::List_, memberName: 'queryEnumList', memberIndex: 5, listMember: SchemasMembers::$shared['aws.protocoltests.shared#FooEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#FooEnumList$member', type: ShapeType::Enum, memberName: 'member', memberIndex: 0, targetClass: FooEnum::class), traits: [9 => 'EnumList']),
                new Schema(id: 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput$queryIntegerEnumList', type: ShapeType::List_, memberName: 'queryIntegerEnumList', memberIndex: 6, listMember: SchemasMembers::$shared['aws.protocoltests.shared#IntegerEnumList$member'] ??= new Schema(id: 'aws.protocoltests.shared#IntegerEnumList$member', type: ShapeType::IntEnum, memberName: 'member', memberIndex: 0, targetClass: IntegerEnum::class), traits: [9 => 'IntegerEnumList'])
            ],
            targetClass: OmitsSerializingEmptyListsInput::class
        );
    }

    public static function outputStreamOutput(): Schema
    {
        if (isset(self::$OutputStreamOutput)) {
            return self::$OutputStreamOutput;
        }

        return self::$OutputStreamOutput = new Schema(
            id: 'aws.protocoltests.restjson#OutputStreamOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#OutputStreamOutput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: OutputStreamOutput::class
        );
    }

    public static function outputStreamWithInitialResponseOutput(): Schema
    {
        if (isset(self::$OutputStreamWithInitialResponseOutput)) {
            return self::$OutputStreamWithInitialResponseOutput;
        }

        return self::$OutputStreamWithInitialResponseOutput = new Schema(
            id: 'aws.protocoltests.restjson#OutputStreamWithInitialResponseOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#OutputStreamWithInitialResponseOutput$initialResponseMember', type: ShapeType::String_, memberName: 'initialResponseMember', memberIndex: 0, traits: [5 => 'initial-response-member', 14 => true]),
                new Schema(id: 'aws.protocoltests.restjson#OutputStreamWithInitialResponseOutput$stream', type: ShapeType::Union, memberName: 'stream', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, targetClass: HeadersEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, targetClass: BlobPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, targetClass: StringPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, targetClass: StructurePayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, targetClass: UnionPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, targetClass: HeadersAndExplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, targetClass: HeadersAndImplicitPayloadEvent::class), new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, targetClass: ErrorEventException::class)], traits: [7 => true, 16 => true], targetClass: EventStream::class)
            ],
            targetClass: OutputStreamWithInitialResponseOutput::class
        );
    }

    public static function payloadConfig(): Schema
    {
        if (isset(self::$PayloadConfig)) {
            return self::$PayloadConfig;
        }

        return self::$PayloadConfig = new Schema(
            id: 'aws.protocoltests.restjson#PayloadConfig',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#PayloadConfig$data', type: ShapeType::Integer, memberName: 'data', memberIndex: 0)
            ],
            targetClass: PayloadConfig::class
        );
    }

    public static function payloadStructure(): Schema
    {
        if (isset(self::$PayloadStructure)) {
            return self::$PayloadStructure;
        }

        return self::$PayloadStructure = new Schema(
            id: 'aws.protocoltests.restjson#PayloadStructure',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#PayloadStructure$structureMember', type: ShapeType::String_, memberName: 'structureMember', memberIndex: 0)
            ],
            targetClass: PayloadStructure::class
        );
    }

    public static function postUnionWithJsonNameInput(): Schema
    {
        if (isset(self::$PostUnionWithJsonNameInput)) {
            return self::$PostUnionWithJsonNameInput;
        }

        return self::$PostUnionWithJsonNameInput = new Schema(
            id: 'aws.protocoltests.restjson#PostUnionWithJsonNameInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#PostUnionWithJsonNameInput$value', type: ShapeType::Union, memberName: 'value', memberIndex: 0, members: self::unionWithJsonName()->getMembers(), targetClass: UnionWithJsonName::class)
            ],
            targetClass: PostUnionWithJsonNameInput::class
        );
    }

    public static function postUnionWithJsonNameOutput(): Schema
    {
        if (isset(self::$PostUnionWithJsonNameOutput)) {
            return self::$PostUnionWithJsonNameOutput;
        }

        return self::$PostUnionWithJsonNameOutput = new Schema(
            id: 'aws.protocoltests.restjson#PostUnionWithJsonNameOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#PostUnionWithJsonNameOutput$value', type: ShapeType::Union, memberName: 'value', memberIndex: 0, members: self::unionWithJsonName()->getMembers(), traits: [14 => true], targetClass: UnionWithJsonName::class)
            ],
            targetClass: PostUnionWithJsonNameOutput::class
        );
    }

    public static function putWithContentEncodingInput(): Schema
    {
        if (isset(self::$PutWithContentEncodingInput)) {
            return self::$PutWithContentEncodingInput;
        }

        return self::$PutWithContentEncodingInput = new Schema(
            id: 'aws.protocoltests.restjson#PutWithContentEncodingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#PutWithContentEncodingInput$encoding', type: ShapeType::String_, memberName: 'encoding', memberIndex: 0, traits: [5 => 'Content-Encoding']),
                new Schema(id: 'aws.protocoltests.restjson#PutWithContentEncodingInput$data', type: ShapeType::String_, memberName: 'data', memberIndex: 1)
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
            id: 'aws.protocoltests.restjson#QueryIdempotencyTokenAutoFillInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#QueryIdempotencyTokenAutoFillInput$token', type: ShapeType::String_, memberName: 'token', memberIndex: 0, traits: [9 => 'token', 19 => true])
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
            id: 'aws.protocoltests.restjson#QueryParamsAsStringListMapInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#QueryParamsAsStringListMapInput$qux', type: ShapeType::String_, memberName: 'qux', memberIndex: 0, traits: [9 => 'corge']),
                new Schema(id: 'aws.protocoltests.restjson#QueryParamsAsStringListMapInput$foo', type: ShapeType::Map, memberName: 'foo', memberIndex: 1, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringListMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringListMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringListMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringListMap$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), traits: [10 => true])
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
            id: 'aws.protocoltests.restjson#QueryPrecedenceInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#QueryPrecedenceInput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [9 => 'bar']),
                new Schema(id: 'aws.protocoltests.restjson#QueryPrecedenceInput$baz', type: ShapeType::Map, memberName: 'baz', memberIndex: 1, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [10 => true])
            ],
            targetClass: QueryPrecedenceInput::class
        );
    }

    public static function recursiveShapesInputOutput(): Schema
    {
        if (isset(self::$RecursiveShapesInputOutput)) {
            return self::$RecursiveShapesInputOutput;
        }

        return self::$RecursiveShapesInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#RecursiveShapesInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutput$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 0, members: [new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
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
            id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0), new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested1::class)], targetClass: RecursiveShapesInputOutputNested2::class)
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
            id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested2',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested2$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested2$recursiveMember', type: ShapeType::Structure, memberName: 'recursiveMember', memberIndex: 1, members: [new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0), new Schema(id: 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1$nested', type: ShapeType::Structure, memberName: 'nested', memberIndex: 1, targetClass: RecursiveShapesInputOutputNested2::class)], targetClass: RecursiveShapesInputOutputNested1::class)
            ],
            targetClass: RecursiveShapesInputOutputNested2::class
        );
    }

    public static function responseCodeHttpFallbackInputOutput(): Schema
    {
        if (isset(self::$ResponseCodeHttpFallbackInputOutput)) {
            return self::$ResponseCodeHttpFallbackInputOutput;
        }

        return self::$ResponseCodeHttpFallbackInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#ResponseCodeHttpFallbackInputOutput',
            type: ShapeType::Structure,
            members: [
            ],
            targetClass: ResponseCodeHttpFallbackInputOutput::class
        );
    }

    public static function responseCodeRequiredOutput(): Schema
    {
        if (isset(self::$ResponseCodeRequiredOutput)) {
            return self::$ResponseCodeRequiredOutput;
        }

        return self::$ResponseCodeRequiredOutput = new Schema(
            id: 'aws.protocoltests.restjson#ResponseCodeRequiredOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ResponseCodeRequiredOutput$responseCode', type: ShapeType::Integer, memberName: 'responseCode', memberIndex: 0, traits: [11 => true, 14 => true])
            ],
            targetClass: ResponseCodeRequiredOutput::class
        );
    }

    public static function serviceUnavailableErrorException(): Schema
    {
        if (isset(self::$ServiceUnavailableErrorException)) {
            return self::$ServiceUnavailableErrorException;
        }

        return self::$ServiceUnavailableErrorException = new Schema(
            id: 'aws.protocoltests.restjson#ServiceUnavailableError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#ServiceUnavailableError$message', type: ShapeType::String_, memberName: 'message', memberIndex: 0)
            ], traits: [23 => 'server'],
            targetClass: ServiceUnavailableErrorException::class
        );
    }

    public static function simpleScalarPropertiesInputOutput(): Schema
    {
        if (isset(self::$SimpleScalarPropertiesInputOutput)) {
            return self::$SimpleScalarPropertiesInputOutput;
        }

        return self::$SimpleScalarPropertiesInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [5 => 'X-Foo']),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$trueBooleanValue', type: ShapeType::Boolean, memberName: 'trueBooleanValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$falseBooleanValue', type: ShapeType::Boolean, memberName: 'falseBooleanValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$byteValue', type: ShapeType::Integer, memberName: 'byteValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$shortValue', type: ShapeType::Integer, memberName: 'shortValue', memberIndex: 5),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$integerValue', type: ShapeType::Integer, memberName: 'integerValue', memberIndex: 6),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$longValue', type: ShapeType::Long, memberName: 'longValue', memberIndex: 7),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$floatValue', type: ShapeType::Float_, memberName: 'floatValue', memberIndex: 8),
                new Schema(id: 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput$doubleValue', type: ShapeType::Double, memberName: 'doubleValue', memberIndex: 9, traits: [12 => 'DoubleDribble'])
            ],
            targetClass: SimpleScalarPropertiesInputOutput::class
        );
    }

    public static function sparseJsonListsInputOutput(): Schema
    {
        if (isset(self::$SparseJsonListsInputOutput)) {
            return self::$SparseJsonListsInputOutput;
        }

        return self::$SparseJsonListsInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#SparseJsonListsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#SparseJsonListsInputOutput$sparseStringList', type: ShapeType::List_, memberName: 'sparseStringList', memberIndex: 0, listMember: SchemasMembers::$shared['aws.protocoltests.shared#SparseStringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#SparseStringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0), traits: [15 => true]),
                new Schema(id: 'aws.protocoltests.restjson#SparseJsonListsInputOutput$sparseShortList', type: ShapeType::List_, memberName: 'sparseShortList', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#SparseShortList$member'] ??= new Schema(id: 'aws.protocoltests.shared#SparseShortList$member', type: ShapeType::Integer, memberName: 'member', memberIndex: 0), traits: [15 => true])
            ],
            targetClass: SparseJsonListsInputOutput::class
        );
    }

    public static function sparseJsonMapsInputOutput(): Schema
    {
        if (isset(self::$SparseJsonMapsInputOutput)) {
            return self::$SparseJsonMapsInputOutput;
        }

        return self::$SparseJsonMapsInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#SparseJsonMapsInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#SparseJsonMapsInputOutput$sparseStructMap', type: ShapeType::Map, memberName: 'sparseStructMap', memberIndex: 0, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#SparseStructMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseStructMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#SparseStructMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseStructMap$value', type: ShapeType::Structure, memberName: 'value', memberIndex: 1, members: self::sharedGreetingStruct()->getMembers(), targetClass: SharedGreetingStruct::class), traits: [15 => true]),
                new Schema(id: 'aws.protocoltests.restjson#SparseJsonMapsInputOutput$sparseNumberMap', type: ShapeType::Map, memberName: 'sparseNumberMap', memberIndex: 1, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#SparseNumberMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseNumberMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#SparseNumberMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseNumberMap$value', type: ShapeType::Integer, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'aws.protocoltests.restjson#SparseJsonMapsInputOutput$sparseBooleanMap', type: ShapeType::Map, memberName: 'sparseBooleanMap', memberIndex: 2, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#SparseBooleanMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseBooleanMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#SparseBooleanMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseBooleanMap$value', type: ShapeType::Boolean, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'aws.protocoltests.restjson#SparseJsonMapsInputOutput$sparseStringMap', type: ShapeType::Map, memberName: 'sparseStringMap', memberIndex: 3, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#SparseStringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#SparseStringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#SparseStringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#SparseStringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1), traits: [15 => true]),
                new Schema(id: 'aws.protocoltests.restjson#SparseJsonMapsInputOutput$sparseSetMap', type: ShapeType::Map, memberName: 'sparseSetMap', memberIndex: 4, mapKey: SchemasMembers::$shared['aws.protocoltests.restjson#SparseSetMap$key'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseSetMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.restjson#SparseSetMap$value'] ??= new Schema(id: 'aws.protocoltests.restjson#SparseSetMap$value', type: ShapeType::List_, memberName: 'value', memberIndex: 1, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringSet$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringSet$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)), traits: [15 => true])
            ],
            targetClass: SparseJsonMapsInputOutput::class
        );
    }

    public static function stringPayloadEvent(): Schema
    {
        if (isset(self::$StringPayloadEvent)) {
            return self::$StringPayloadEvent;
        }

        return self::$StringPayloadEvent = new Schema(
            id: 'aws.protocoltests.restjson#StringPayloadEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#StringPayloadEvent$payload', type: ShapeType::String_, memberName: 'payload', memberIndex: 0)
            ],
            targetClass: StringPayloadEvent::class
        );
    }

    public static function stringPayloadInput(): Schema
    {
        if (isset(self::$StringPayloadInput)) {
            return self::$StringPayloadInput;
        }

        return self::$StringPayloadInput = new Schema(
            id: 'aws.protocoltests.restjson#StringPayloadInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#StringPayloadInput$payload', type: ShapeType::String_, memberName: 'payload', memberIndex: 0, traits: [7 => true])
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
            id: 'aws.protocoltests.restjson#StructureListMember',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#StructureListMember$a', type: ShapeType::String_, memberName: 'a', memberIndex: 0, traits: [12 => 'value']),
                new Schema(id: 'aws.protocoltests.restjson#StructureListMember$b', type: ShapeType::String_, memberName: 'b', memberIndex: 1, traits: [12 => 'other'])
            ],
            targetClass: StructureListMember::class
        );
    }

    public static function structurePayloadEvent(): Schema
    {
        if (isset(self::$StructurePayloadEvent)) {
            return self::$StructurePayloadEvent;
        }

        return self::$StructurePayloadEvent = new Schema(
            id: 'aws.protocoltests.restjson#StructurePayloadEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#StructurePayloadEvent$payload', type: ShapeType::Structure, memberName: 'payload', memberIndex: 0, members: self::payloadStructure()->getMembers(), targetClass: PayloadStructure::class)
            ],
            targetClass: StructurePayloadEvent::class
        );
    }

    public static function testBodyStructureInputOutput(): Schema
    {
        if (isset(self::$TestBodyStructureInputOutput)) {
            return self::$TestBodyStructureInputOutput;
        }

        return self::$TestBodyStructureInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#TestBodyStructureInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#TestBodyStructureInputOutput$testId', type: ShapeType::String_, memberName: 'testId', memberIndex: 0, traits: [5 => 'x-amz-test-id']),
                new Schema(id: 'aws.protocoltests.restjson#TestBodyStructureInputOutput$testConfig', type: ShapeType::Structure, memberName: 'testConfig', memberIndex: 1, members: self::testConfig()->getMembers(), targetClass: TestConfig::class)
            ],
            targetClass: TestBodyStructureInputOutput::class
        );
    }

    public static function testConfig(): Schema
    {
        if (isset(self::$TestConfig)) {
            return self::$TestConfig;
        }

        return self::$TestConfig = new Schema(
            id: 'aws.protocoltests.restjson#TestConfig',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#TestConfig$timeout', type: ShapeType::Integer, memberName: 'timeout', memberIndex: 0)
            ],
            targetClass: TestConfig::class
        );
    }

    public static function testNoPayloadInputOutput(): Schema
    {
        if (isset(self::$TestNoPayloadInputOutput)) {
            return self::$TestNoPayloadInputOutput;
        }

        return self::$TestNoPayloadInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#TestNoPayloadInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#TestNoPayloadInputOutput$testId', type: ShapeType::String_, memberName: 'testId', memberIndex: 0, traits: [5 => 'X-Amz-Test-Id'])
            ],
            targetClass: TestNoPayloadInputOutput::class
        );
    }

    public static function testPayloadBlobInputOutput(): Schema
    {
        if (isset(self::$TestPayloadBlobInputOutput)) {
            return self::$TestPayloadBlobInputOutput;
        }

        return self::$TestPayloadBlobInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#TestPayloadBlobInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#TestPayloadBlobInputOutput$contentType', type: ShapeType::String_, memberName: 'contentType', memberIndex: 0, traits: [5 => 'Content-Type']),
                new Schema(id: 'aws.protocoltests.restjson#TestPayloadBlobInputOutput$data', type: ShapeType::Blob, memberName: 'data', memberIndex: 1, traits: [7 => true])
            ],
            targetClass: TestPayloadBlobInputOutput::class
        );
    }

    public static function testPayloadStructureInputOutput(): Schema
    {
        if (isset(self::$TestPayloadStructureInputOutput)) {
            return self::$TestPayloadStructureInputOutput;
        }

        return self::$TestPayloadStructureInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#TestPayloadStructureInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#TestPayloadStructureInputOutput$testId', type: ShapeType::String_, memberName: 'testId', memberIndex: 0, traits: [5 => 'x-amz-test-id']),
                new Schema(id: 'aws.protocoltests.restjson#TestPayloadStructureInputOutput$payloadConfig', type: ShapeType::Structure, memberName: 'payloadConfig', memberIndex: 1, members: self::payloadConfig()->getMembers(), traits: [7 => true], targetClass: PayloadConfig::class)
            ],
            targetClass: TestPayloadStructureInputOutput::class
        );
    }

    public static function timestampFormatHeadersIO(): Schema
    {
        if (isset(self::$TimestampFormatHeadersIO)) {
            return self::$TimestampFormatHeadersIO;
        }

        return self::$TimestampFormatHeadersIO = new Schema(
            id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO$memberEpochSeconds', type: ShapeType::Timestamp, memberName: 'memberEpochSeconds', memberIndex: 0, traits: [4 => 'epoch-seconds', 5 => 'X-memberEpochSeconds']),
                new Schema(id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO$memberHttpDate', type: ShapeType::Timestamp, memberName: 'memberHttpDate', memberIndex: 1, traits: [4 => 'http-date', 5 => 'X-memberHttpDate']),
                new Schema(id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO$memberDateTime', type: ShapeType::Timestamp, memberName: 'memberDateTime', memberIndex: 2, traits: [4 => 'date-time', 5 => 'X-memberDateTime']),
                new Schema(id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO$defaultFormat', type: ShapeType::Timestamp, memberName: 'defaultFormat', memberIndex: 3, traits: [5 => 'X-defaultFormat']),
                new Schema(id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO$targetEpochSeconds', type: ShapeType::Timestamp, memberName: 'targetEpochSeconds', memberIndex: 4, traits: [4 => 'epoch-seconds', 5 => 'X-targetEpochSeconds']),
                new Schema(id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO$targetHttpDate', type: ShapeType::Timestamp, memberName: 'targetHttpDate', memberIndex: 5, traits: [4 => 'http-date', 5 => 'X-targetHttpDate']),
                new Schema(id: 'aws.protocoltests.restjson#TimestampFormatHeadersIO$targetDateTime', type: ShapeType::Timestamp, memberName: 'targetDateTime', memberIndex: 6, traits: [4 => 'date-time', 5 => 'X-targetDateTime'])
            ],
            targetClass: TimestampFormatHeadersIO::class
        );
    }

    public static function unionInputOutput(): Schema
    {
        if (isset(self::$UnionInputOutput)) {
            return self::$UnionInputOutput;
        }

        return self::$UnionInputOutput = new Schema(
            id: 'aws.protocoltests.restjson#UnionInputOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#UnionInputOutput$contents', type: ShapeType::Union, memberName: 'contents', memberIndex: 0, members: self::myUnion()->getMembers(), targetClass: MyUnion::class)
            ],
            targetClass: UnionInputOutput::class
        );
    }

    public static function unionPayloadEvent(): Schema
    {
        if (isset(self::$UnionPayloadEvent)) {
            return self::$UnionPayloadEvent;
        }

        return self::$UnionPayloadEvent = new Schema(
            id: 'aws.protocoltests.restjson#UnionPayloadEvent',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#UnionPayloadEvent$payload', type: ShapeType::Union, memberName: 'payload', memberIndex: 0, members: self::payloadUnion()->getMembers(), targetClass: PayloadUnion::class)
            ],
            targetClass: UnionPayloadEvent::class
        );
    }

    public static function greetingStruct(): Schema
    {
        if (isset(self::$GreetingStruct)) {
            return self::$GreetingStruct;
        }

        return self::$GreetingStruct = new Schema(
            id: 'aws.protocoltests.restjson.nested#GreetingStruct',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.restjson.nested#GreetingStruct$salutation', type: ShapeType::String_, memberName: 'salutation', memberIndex: 0)
            ],
            targetClass: GreetingStruct::class
        );
    }

    public static function sharedGreetingStruct(): Schema
    {
        if (isset(self::$SharedGreetingStruct)) {
            return self::$SharedGreetingStruct;
        }

        return self::$SharedGreetingStruct = new Schema(
            id: 'aws.protocoltests.shared#GreetingStruct',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'aws.protocoltests.shared#GreetingStruct$hi', type: ShapeType::String_, memberName: 'hi', memberIndex: 0)
            ],
            targetClass: SharedGreetingStruct::class
        );
    }

    public static function eventStream(): Schema
    {
        if (isset(self::$EventStream)) {
            return self::$EventStream;
        }

        return self::$EventStream = new Schema(
            id: 'aws.protocoltests.restjson#EventStream',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#EventStream$headers', type: ShapeType::Structure, memberName: 'headers', memberIndex: 0, members: self::headersEvent()->getMembers(), targetClass: HeadersEvent::class),
                new Schema(id: 'aws.protocoltests.restjson#EventStream$blobPayload', type: ShapeType::Structure, memberName: 'blobPayload', memberIndex: 1, members: self::blobPayloadEvent()->getMembers(), targetClass: BlobPayloadEvent::class),
                new Schema(id: 'aws.protocoltests.restjson#EventStream$stringPayload', type: ShapeType::Structure, memberName: 'stringPayload', memberIndex: 2, members: self::stringPayloadEvent()->getMembers(), targetClass: StringPayloadEvent::class),
                new Schema(id: 'aws.protocoltests.restjson#EventStream$structurePayload', type: ShapeType::Structure, memberName: 'structurePayload', memberIndex: 3, members: [new Schema(id: 'aws.protocoltests.restjson#StructurePayloadEvent$payload', type: ShapeType::Structure, memberName: 'payload', memberIndex: 0, targetClass: PayloadStructure::class)], targetClass: StructurePayloadEvent::class),
                new Schema(id: 'aws.protocoltests.restjson#EventStream$unionPayload', type: ShapeType::Structure, memberName: 'unionPayload', memberIndex: 4, members: [new Schema(id: 'aws.protocoltests.restjson#UnionPayloadEvent$payload', type: ShapeType::Union, memberName: 'payload', memberIndex: 0, targetClass: PayloadUnion::class)], targetClass: UnionPayloadEvent::class),
                new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndExplicitPayload', type: ShapeType::Structure, memberName: 'headersAndExplicitPayload', memberIndex: 5, members: self::headersAndExplicitPayloadEvent()->getMembers(), targetClass: HeadersAndExplicitPayloadEvent::class),
                new Schema(id: 'aws.protocoltests.restjson#EventStream$headersAndImplicitPayload', type: ShapeType::Structure, memberName: 'headersAndImplicitPayload', memberIndex: 6, members: self::headersAndImplicitPayloadEvent()->getMembers(), targetClass: HeadersAndImplicitPayloadEvent::class),
                new Schema(id: 'aws.protocoltests.restjson#EventStream$error', type: ShapeType::Structure, memberName: 'error', memberIndex: 7, members: self::errorEventException()->getMembers(), targetClass: ErrorEventException::class)
            ],
            targetClass: EventStream::class
        );
    }

    public static function myUnion(): Schema
    {
        if (isset(self::$MyUnion)) {
            return self::$MyUnion;
        }

        return self::$MyUnion = new Schema(
            id: 'aws.protocoltests.restjson#MyUnion',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$stringValue', type: ShapeType::String_, memberName: 'stringValue', memberIndex: 0),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$booleanValue', type: ShapeType::Boolean, memberName: 'booleanValue', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$numberValue', type: ShapeType::Integer, memberName: 'numberValue', memberIndex: 2),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$blobValue', type: ShapeType::Blob, memberName: 'blobValue', memberIndex: 3),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$timestampValue', type: ShapeType::Timestamp, memberName: 'timestampValue', memberIndex: 4),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$enumValue', type: ShapeType::Enum, memberName: 'enumValue', memberIndex: 5, targetClass: FooEnum::class),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$listValue', type: ShapeType::List_, memberName: 'listValue', memberIndex: 6, listMember: SchemasMembers::$shared['aws.protocoltests.shared#StringList$member'] ??= new Schema(id: 'aws.protocoltests.shared#StringList$member', type: ShapeType::String_, memberName: 'member', memberIndex: 0)),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$mapValue', type: ShapeType::Map, memberName: 'mapValue', memberIndex: 7, mapKey: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$key'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$key', type: ShapeType::String_, memberName: 'key', memberIndex: 0), mapValue: SchemasMembers::$shared['aws.protocoltests.shared#StringMap$value'] ??= new Schema(id: 'aws.protocoltests.shared#StringMap$value', type: ShapeType::String_, memberName: 'value', memberIndex: 1)),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$structureValue', type: ShapeType::Structure, memberName: 'structureValue', memberIndex: 8, members: self::sharedGreetingStruct()->getMembers(), targetClass: SharedGreetingStruct::class),
                new Schema(id: 'aws.protocoltests.restjson#MyUnion$renamedStructureValue', type: ShapeType::Structure, memberName: 'renamedStructureValue', memberIndex: 9, members: self::greetingStruct()->getMembers(), targetClass: GreetingStruct::class)
            ],
            targetClass: MyUnion::class
        );
    }

    public static function payloadUnion(): Schema
    {
        if (isset(self::$PayloadUnion)) {
            return self::$PayloadUnion;
        }

        return self::$PayloadUnion = new Schema(
            id: 'aws.protocoltests.restjson#PayloadUnion',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#PayloadUnion$unionMember', type: ShapeType::String_, memberName: 'unionMember', memberIndex: 0)
            ],
            targetClass: PayloadUnion::class
        );
    }

    public static function unionPayload(): Schema
    {
        if (isset(self::$UnionPayload)) {
            return self::$UnionPayload;
        }

        return self::$UnionPayload = new Schema(
            id: 'aws.protocoltests.restjson#UnionPayload',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#UnionPayload$greeting', type: ShapeType::String_, memberName: 'greeting', memberIndex: 0)
            ],
            targetClass: UnionPayload::class
        );
    }

    public static function unionWithJsonName(): Schema
    {
        if (isset(self::$UnionWithJsonName)) {
            return self::$UnionWithJsonName;
        }

        return self::$UnionWithJsonName = new Schema(
            id: 'aws.protocoltests.restjson#UnionWithJsonName',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'aws.protocoltests.restjson#UnionWithJsonName$foo', type: ShapeType::String_, memberName: 'foo', memberIndex: 0, traits: [12 => 'FOO']),
                new Schema(id: 'aws.protocoltests.restjson#UnionWithJsonName$bar', type: ShapeType::String_, memberName: 'bar', memberIndex: 1),
                new Schema(id: 'aws.protocoltests.restjson#UnionWithJsonName$baz', type: ShapeType::String_, memberName: 'baz', memberIndex: 2, traits: [12 => '_baz'])
            ],
            targetClass: UnionWithJsonName::class
        );
    }

    public static function stringEnum(): Schema
    {
        if (isset(self::$StringEnum)) {
            return self::$StringEnum;
        }

        return self::$StringEnum = new Schema(
            id: 'aws.protocoltests.restjson#StringEnum',
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
