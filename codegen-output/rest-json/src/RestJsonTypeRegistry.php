<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;

/**
 * Shape-ID registry for the RestJson service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for a raw
 * class-string when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class RestJsonTypeRegistry implements TypeRegistryInterface
{
    use TypeRegistryDeserializeTrait;

    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'aws.protocoltests.restjson#AllQueryStringTypesInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\AllQueryStringTypesInput',
        'aws.protocoltests.restjson#BlobPayloadEvent' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\BlobPayloadEvent',
        'aws.protocoltests.restjson#ComplexError' => 'Smithy\\ProtocolTests\\RestJson\\Exception\\ComplexErrorException',
        'aws.protocoltests.restjson#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\ComplexNestedErrorData',
        'aws.protocoltests.restjson#ConstantAndVariableQueryStringInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\ConstantAndVariableQueryStringInput',
        'aws.protocoltests.restjson#ConstantQueryStringInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\ConstantQueryStringInput',
        'aws.protocoltests.restjson#ContentTypeParametersInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\ContentTypeParametersInput',
        'aws.protocoltests.restjson#ContentTypeParametersOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\ContentTypeParametersOutput',
        'aws.protocoltests.restjson#DatetimeOffsetsOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\DatetimeOffsetsOutput',
        'aws.protocoltests.restjson#DocumentTypeAsMapValueInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\DocumentTypeAsMapValueInputOutput',
        'aws.protocoltests.restjson#DocumentTypeAsPayloadInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\DocumentTypeAsPayloadInputOutput',
        'aws.protocoltests.restjson#DocumentTypeInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\DocumentTypeInputOutput',
        'aws.protocoltests.restjson#DuplexStreamInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\DuplexStreamInput',
        'aws.protocoltests.restjson#DuplexStreamOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\DuplexStreamOutput',
        'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\DuplexStreamWithInitialMessagesInput',
        'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\DuplexStreamWithInitialMessagesOutput',
        'aws.protocoltests.restjson#EmptyInputAndEmptyOutputInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\EmptyInputAndEmptyOutputInput',
        'aws.protocoltests.restjson#EmptyInputAndEmptyOutputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\EmptyInputAndEmptyOutputOutput',
        'aws.protocoltests.restjson#EnumPayloadInput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\EnumPayloadInput',
        'aws.protocoltests.restjson#ErrorEvent' => 'Smithy\\ProtocolTests\\RestJson\\Exception\\ErrorEventException',
        'aws.protocoltests.restjson#EventStream' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\EventStream',
        'aws.protocoltests.restjson#FooError' => 'Smithy\\ProtocolTests\\RestJson\\Exception\\FooErrorException',
        'aws.protocoltests.restjson#FractionalSecondsOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\FractionalSecondsOutput',
        'aws.protocoltests.restjson#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\GreetingWithErrorsOutput',
        'aws.protocoltests.restjson#HeadersAndExplicitPayloadEvent' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\HeadersAndExplicitPayloadEvent',
        'aws.protocoltests.restjson#HeadersAndImplicitPayloadEvent' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\HeadersAndImplicitPayloadEvent',
        'aws.protocoltests.restjson#HeadersEvent' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\HeadersEvent',
        'aws.protocoltests.restjson#HostLabelInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HostLabelInput',
        'aws.protocoltests.restjson#HttpChecksumRequiredInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpChecksumRequiredInputOutput',
        'aws.protocoltests.restjson#HttpEmptyPrefixHeadersInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpEmptyPrefixHeadersInput',
        'aws.protocoltests.restjson#HttpEmptyPrefixHeadersOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\HttpEmptyPrefixHeadersOutput',
        'aws.protocoltests.restjson#HttpPayloadTraitsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpPayloadTraitsInputOutput',
        'aws.protocoltests.restjson#HttpPayloadWithStructureInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpPayloadWithStructureInputOutput',
        'aws.protocoltests.restjson#HttpPayloadWithUnionInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpPayloadWithUnionInputOutput',
        'aws.protocoltests.restjson#HttpPrefixHeadersInResponseInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpPrefixHeadersInResponseInput',
        'aws.protocoltests.restjson#HttpPrefixHeadersInResponseOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\HttpPrefixHeadersInResponseOutput',
        'aws.protocoltests.restjson#HttpPrefixHeadersInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpPrefixHeadersInput',
        'aws.protocoltests.restjson#HttpPrefixHeadersOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\HttpPrefixHeadersOutput',
        'aws.protocoltests.restjson#HttpQueryParamsOnlyInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpQueryParamsOnlyInput',
        'aws.protocoltests.restjson#HttpRequestWithFloatLabelsInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithFloatLabelsInput',
        'aws.protocoltests.restjson#HttpRequestWithGreedyLabelInPathInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithGreedyLabelInPathInput',
        'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithLabelsAndTimestampFormatInput',
        'aws.protocoltests.restjson#HttpRequestWithLabelsInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithLabelsInput',
        'aws.protocoltests.restjson#HttpRequestWithRegexLiteralInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithRegexLiteralInput',
        'aws.protocoltests.restjson#HttpResponseCodeOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\HttpResponseCodeOutput',
        'aws.protocoltests.restjson#IgnoreQueryParamsInResponseOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\IgnoreQueryParamsInResponseOutput',
        'aws.protocoltests.restjson#InputAndOutputWithHeadersIO' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\InputAndOutputWithHeadersIO',
        'aws.protocoltests.restjson#InputStreamInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\InputStreamInput',
        'aws.protocoltests.restjson#InputStreamWithInitialRequestInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\InputStreamWithInitialRequestInput',
        'aws.protocoltests.restjson#InvalidGreeting' => 'Smithy\\ProtocolTests\\RestJson\\Exception\\InvalidGreetingException',
        'aws.protocoltests.restjson#JsonBlobsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonBlobsInputOutput',
        'aws.protocoltests.restjson#JsonEnumsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonEnumsInputOutput',
        'aws.protocoltests.restjson#JsonIntEnumsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonIntEnumsInputOutput',
        'aws.protocoltests.restjson#JsonListsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonListsInputOutput',
        'aws.protocoltests.restjson#JsonMapsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonMapsInputOutput',
        'aws.protocoltests.restjson#JsonTimestampsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonTimestampsInputOutput',
        'aws.protocoltests.restjson#MediaTypeHeaderInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\MediaTypeHeaderInput',
        'aws.protocoltests.restjson#MediaTypeHeaderOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\MediaTypeHeaderOutput',
        'aws.protocoltests.restjson#MyUnion' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\MyUnion',
        'aws.protocoltests.restjson#NestedPayload' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\NestedPayload',
        'aws.protocoltests.restjson#NoInputAndOutputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\NoInputAndOutputOutput',
        'aws.protocoltests.restjson#NullAndEmptyHeadersIO' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\NullAndEmptyHeadersIO',
        'aws.protocoltests.restjson#OmitsNullSerializesEmptyStringInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\OmitsNullSerializesEmptyStringInput',
        'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\OmitsSerializingEmptyListsInput',
        'aws.protocoltests.restjson#OutputStreamOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\OutputStreamOutput',
        'aws.protocoltests.restjson#OutputStreamWithInitialResponseOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\OutputStreamWithInitialResponseOutput',
        'aws.protocoltests.restjson#PayloadConfig' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\PayloadConfig',
        'aws.protocoltests.restjson#PayloadStructure' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\PayloadStructure',
        'aws.protocoltests.restjson#PayloadUnion' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\PayloadUnion',
        'aws.protocoltests.restjson#PostUnionWithJsonNameInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\PostUnionWithJsonNameInput',
        'aws.protocoltests.restjson#PostUnionWithJsonNameOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\PostUnionWithJsonNameOutput',
        'aws.protocoltests.restjson#PutWithContentEncodingInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\PutWithContentEncodingInput',
        'aws.protocoltests.restjson#QueryIdempotencyTokenAutoFillInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\QueryIdempotencyTokenAutoFillInput',
        'aws.protocoltests.restjson#QueryParamsAsStringListMapInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\QueryParamsAsStringListMapInput',
        'aws.protocoltests.restjson#QueryPrecedenceInput' => 'Smithy\\ProtocolTests\\RestJson\\Input\\QueryPrecedenceInput',
        'aws.protocoltests.restjson#RecursiveShapesInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\RecursiveShapesInputOutput',
        'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\RecursiveShapesInputOutputNested1',
        'aws.protocoltests.restjson#RecursiveShapesInputOutputNested2' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\RecursiveShapesInputOutputNested2',
        'aws.protocoltests.restjson#ResponseCodeHttpFallbackInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\ResponseCodeHttpFallbackInputOutput',
        'aws.protocoltests.restjson#ResponseCodeRequiredOutput' => 'Smithy\\ProtocolTests\\RestJson\\Output\\ResponseCodeRequiredOutput',
        'aws.protocoltests.restjson#ServiceUnavailableError' => 'Smithy\\ProtocolTests\\RestJson\\Exception\\ServiceUnavailableErrorException',
        'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\SimpleScalarPropertiesInputOutput',
        'aws.protocoltests.restjson#SparseJsonListsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\SparseJsonListsInputOutput',
        'aws.protocoltests.restjson#SparseJsonMapsInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\SparseJsonMapsInputOutput',
        'aws.protocoltests.restjson#StringEnum' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\StringEnum',
        'aws.protocoltests.restjson#StringPayloadEvent' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\StringPayloadEvent',
        'aws.protocoltests.restjson#StringPayloadInput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\StringPayloadInput',
        'aws.protocoltests.restjson#StructureListMember' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\StructureListMember',
        'aws.protocoltests.restjson#StructurePayloadEvent' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\StructurePayloadEvent',
        'aws.protocoltests.restjson#TestBodyStructureInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\TestBodyStructureInputOutput',
        'aws.protocoltests.restjson#TestConfig' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\TestConfig',
        'aws.protocoltests.restjson#TestNoPayloadInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\TestNoPayloadInputOutput',
        'aws.protocoltests.restjson#TestPayloadBlobInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\TestPayloadBlobInputOutput',
        'aws.protocoltests.restjson#TestPayloadStructureInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\TestPayloadStructureInputOutput',
        'aws.protocoltests.restjson#TimestampFormatHeadersIO' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\TimestampFormatHeadersIO',
        'aws.protocoltests.restjson#UnionInputOutput' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionInputOutput',
        'aws.protocoltests.restjson#UnionPayload' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionPayload',
        'aws.protocoltests.restjson#UnionPayloadEvent' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionPayloadEvent',
        'aws.protocoltests.restjson#UnionWithJsonName' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionWithJsonName',
        'aws.protocoltests.restjson.nested#GreetingStruct' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\GreetingStruct',
        'aws.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\FooEnum',
        'aws.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\SharedGreetingStruct',
        'aws.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\RestJson\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\RestJson\\Input\\AllQueryStringTypesInput' => 'aws.protocoltests.restjson#AllQueryStringTypesInput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\BlobPayloadEvent' => 'aws.protocoltests.restjson#BlobPayloadEvent',
        'Smithy\\ProtocolTests\\RestJson\\Exception\\ComplexErrorException' => 'aws.protocoltests.restjson#ComplexError',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\ComplexNestedErrorData' => 'aws.protocoltests.restjson#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\RestJson\\Input\\ConstantAndVariableQueryStringInput' => 'aws.protocoltests.restjson#ConstantAndVariableQueryStringInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\ConstantQueryStringInput' => 'aws.protocoltests.restjson#ConstantQueryStringInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\ContentTypeParametersInput' => 'aws.protocoltests.restjson#ContentTypeParametersInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\ContentTypeParametersOutput' => 'aws.protocoltests.restjson#ContentTypeParametersOutput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\DatetimeOffsetsOutput' => 'aws.protocoltests.restjson#DatetimeOffsetsOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\DocumentTypeAsMapValueInputOutput' => 'aws.protocoltests.restjson#DocumentTypeAsMapValueInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\DocumentTypeAsPayloadInputOutput' => 'aws.protocoltests.restjson#DocumentTypeAsPayloadInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\DocumentTypeInputOutput' => 'aws.protocoltests.restjson#DocumentTypeInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\DuplexStreamInput' => 'aws.protocoltests.restjson#DuplexStreamInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\DuplexStreamOutput' => 'aws.protocoltests.restjson#DuplexStreamOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\DuplexStreamWithInitialMessagesInput' => 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\DuplexStreamWithInitialMessagesOutput' => 'aws.protocoltests.restjson#DuplexStreamWithInitialMessagesOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\EmptyInputAndEmptyOutputInput' => 'aws.protocoltests.restjson#EmptyInputAndEmptyOutputInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\EmptyInputAndEmptyOutputOutput' => 'aws.protocoltests.restjson#EmptyInputAndEmptyOutputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\EnumPayloadInput' => 'aws.protocoltests.restjson#EnumPayloadInput',
        'Smithy\\ProtocolTests\\RestJson\\Exception\\ErrorEventException' => 'aws.protocoltests.restjson#ErrorEvent',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\EventStream' => 'aws.protocoltests.restjson#EventStream',
        'Smithy\\ProtocolTests\\RestJson\\Exception\\FooErrorException' => 'aws.protocoltests.restjson#FooError',
        'Smithy\\ProtocolTests\\RestJson\\Output\\FractionalSecondsOutput' => 'aws.protocoltests.restjson#FractionalSecondsOutput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\GreetingWithErrorsOutput' => 'aws.protocoltests.restjson#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\HeadersAndExplicitPayloadEvent' => 'aws.protocoltests.restjson#HeadersAndExplicitPayloadEvent',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\HeadersAndImplicitPayloadEvent' => 'aws.protocoltests.restjson#HeadersAndImplicitPayloadEvent',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\HeadersEvent' => 'aws.protocoltests.restjson#HeadersEvent',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HostLabelInput' => 'aws.protocoltests.restjson#HostLabelInput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpChecksumRequiredInputOutput' => 'aws.protocoltests.restjson#HttpChecksumRequiredInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpEmptyPrefixHeadersInput' => 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\HttpEmptyPrefixHeadersOutput' => 'aws.protocoltests.restjson#HttpEmptyPrefixHeadersOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpPayloadTraitsInputOutput' => 'aws.protocoltests.restjson#HttpPayloadTraitsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpPayloadWithStructureInputOutput' => 'aws.protocoltests.restjson#HttpPayloadWithStructureInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\HttpPayloadWithUnionInputOutput' => 'aws.protocoltests.restjson#HttpPayloadWithUnionInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpPrefixHeadersInResponseInput' => 'aws.protocoltests.restjson#HttpPrefixHeadersInResponseInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\HttpPrefixHeadersInResponseOutput' => 'aws.protocoltests.restjson#HttpPrefixHeadersInResponseOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpPrefixHeadersInput' => 'aws.protocoltests.restjson#HttpPrefixHeadersInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\HttpPrefixHeadersOutput' => 'aws.protocoltests.restjson#HttpPrefixHeadersOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpQueryParamsOnlyInput' => 'aws.protocoltests.restjson#HttpQueryParamsOnlyInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithFloatLabelsInput' => 'aws.protocoltests.restjson#HttpRequestWithFloatLabelsInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithGreedyLabelInPathInput' => 'aws.protocoltests.restjson#HttpRequestWithGreedyLabelInPathInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithLabelsAndTimestampFormatInput' => 'aws.protocoltests.restjson#HttpRequestWithLabelsAndTimestampFormatInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithLabelsInput' => 'aws.protocoltests.restjson#HttpRequestWithLabelsInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\HttpRequestWithRegexLiteralInput' => 'aws.protocoltests.restjson#HttpRequestWithRegexLiteralInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\HttpResponseCodeOutput' => 'aws.protocoltests.restjson#HttpResponseCodeOutput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\IgnoreQueryParamsInResponseOutput' => 'aws.protocoltests.restjson#IgnoreQueryParamsInResponseOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\InputAndOutputWithHeadersIO' => 'aws.protocoltests.restjson#InputAndOutputWithHeadersIO',
        'Smithy\\ProtocolTests\\RestJson\\Input\\InputStreamInput' => 'aws.protocoltests.restjson#InputStreamInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\InputStreamWithInitialRequestInput' => 'aws.protocoltests.restjson#InputStreamWithInitialRequestInput',
        'Smithy\\ProtocolTests\\RestJson\\Exception\\InvalidGreetingException' => 'aws.protocoltests.restjson#InvalidGreeting',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonBlobsInputOutput' => 'aws.protocoltests.restjson#JsonBlobsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonEnumsInputOutput' => 'aws.protocoltests.restjson#JsonEnumsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonIntEnumsInputOutput' => 'aws.protocoltests.restjson#JsonIntEnumsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonListsInputOutput' => 'aws.protocoltests.restjson#JsonListsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonMapsInputOutput' => 'aws.protocoltests.restjson#JsonMapsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\JsonTimestampsInputOutput' => 'aws.protocoltests.restjson#JsonTimestampsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\MediaTypeHeaderInput' => 'aws.protocoltests.restjson#MediaTypeHeaderInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\MediaTypeHeaderOutput' => 'aws.protocoltests.restjson#MediaTypeHeaderOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\MyUnion' => 'aws.protocoltests.restjson#MyUnion',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\NestedPayload' => 'aws.protocoltests.restjson#NestedPayload',
        'Smithy\\ProtocolTests\\RestJson\\Output\\NoInputAndOutputOutput' => 'aws.protocoltests.restjson#NoInputAndOutputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\NullAndEmptyHeadersIO' => 'aws.protocoltests.restjson#NullAndEmptyHeadersIO',
        'Smithy\\ProtocolTests\\RestJson\\Input\\OmitsNullSerializesEmptyStringInput' => 'aws.protocoltests.restjson#OmitsNullSerializesEmptyStringInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\OmitsSerializingEmptyListsInput' => 'aws.protocoltests.restjson#OmitsSerializingEmptyListsInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\OutputStreamOutput' => 'aws.protocoltests.restjson#OutputStreamOutput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\OutputStreamWithInitialResponseOutput' => 'aws.protocoltests.restjson#OutputStreamWithInitialResponseOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\PayloadConfig' => 'aws.protocoltests.restjson#PayloadConfig',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\PayloadStructure' => 'aws.protocoltests.restjson#PayloadStructure',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\PayloadUnion' => 'aws.protocoltests.restjson#PayloadUnion',
        'Smithy\\ProtocolTests\\RestJson\\Input\\PostUnionWithJsonNameInput' => 'aws.protocoltests.restjson#PostUnionWithJsonNameInput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\PostUnionWithJsonNameOutput' => 'aws.protocoltests.restjson#PostUnionWithJsonNameOutput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\PutWithContentEncodingInput' => 'aws.protocoltests.restjson#PutWithContentEncodingInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\QueryIdempotencyTokenAutoFillInput' => 'aws.protocoltests.restjson#QueryIdempotencyTokenAutoFillInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\QueryParamsAsStringListMapInput' => 'aws.protocoltests.restjson#QueryParamsAsStringListMapInput',
        'Smithy\\ProtocolTests\\RestJson\\Input\\QueryPrecedenceInput' => 'aws.protocoltests.restjson#QueryPrecedenceInput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\RecursiveShapesInputOutput' => 'aws.protocoltests.restjson#RecursiveShapesInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\RecursiveShapesInputOutputNested1' => 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested1',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\RecursiveShapesInputOutputNested2' => 'aws.protocoltests.restjson#RecursiveShapesInputOutputNested2',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\ResponseCodeHttpFallbackInputOutput' => 'aws.protocoltests.restjson#ResponseCodeHttpFallbackInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Output\\ResponseCodeRequiredOutput' => 'aws.protocoltests.restjson#ResponseCodeRequiredOutput',
        'Smithy\\ProtocolTests\\RestJson\\Exception\\ServiceUnavailableErrorException' => 'aws.protocoltests.restjson#ServiceUnavailableError',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\SimpleScalarPropertiesInputOutput' => 'aws.protocoltests.restjson#SimpleScalarPropertiesInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\SparseJsonListsInputOutput' => 'aws.protocoltests.restjson#SparseJsonListsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\SparseJsonMapsInputOutput' => 'aws.protocoltests.restjson#SparseJsonMapsInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\StringEnum' => 'aws.protocoltests.restjson#StringEnum',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\StringPayloadEvent' => 'aws.protocoltests.restjson#StringPayloadEvent',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\StringPayloadInput' => 'aws.protocoltests.restjson#StringPayloadInput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\StructureListMember' => 'aws.protocoltests.restjson#StructureListMember',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\StructurePayloadEvent' => 'aws.protocoltests.restjson#StructurePayloadEvent',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\TestBodyStructureInputOutput' => 'aws.protocoltests.restjson#TestBodyStructureInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\TestConfig' => 'aws.protocoltests.restjson#TestConfig',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\TestNoPayloadInputOutput' => 'aws.protocoltests.restjson#TestNoPayloadInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\TestPayloadBlobInputOutput' => 'aws.protocoltests.restjson#TestPayloadBlobInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\TestPayloadStructureInputOutput' => 'aws.protocoltests.restjson#TestPayloadStructureInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\TimestampFormatHeadersIO' => 'aws.protocoltests.restjson#TimestampFormatHeadersIO',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionInputOutput' => 'aws.protocoltests.restjson#UnionInputOutput',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionPayload' => 'aws.protocoltests.restjson#UnionPayload',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionPayloadEvent' => 'aws.protocoltests.restjson#UnionPayloadEvent',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\UnionWithJsonName' => 'aws.protocoltests.restjson#UnionWithJsonName',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\GreetingStruct' => 'aws.protocoltests.restjson.nested#GreetingStruct',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\FooEnum' => 'aws.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\SharedGreetingStruct' => 'aws.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\RestJson\\Shape\\IntegerEnum' => 'aws.protocoltests.shared#IntegerEnum'
    ];

    /**
     * Return the generated PHP class-string for a Smithy shape ID,
     * or null when the ID is unknown to this service.
     *
     * @return class-string|null
     */
    public function classFor(string $shapeId): ?string
    {
        return self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
    }

    /**
     * Return the Smithy shape ID for a generated class.
     * Returns null when this codegen run did not produce the class.
     *
     * @param class-string $class
     */
    public function shapeIdFor(string $class): ?string
    {
        return self::SHAPE_IDS_BY_CLASS[$class] ?? null;
    }

    /**
     * Resolve a shape ID to its `Schema` object. Uses the generated
     * class's lazy `schema()` static, so schemas are only built the
     * first time they are accessed.
     *
     * Returns null when the shape ID is not registered.
     */
    public function schemaFor(string $shapeId): ?Schema
    {
        $class = self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
        if ($class === null) {
            return null;
        }
        return \method_exists($class, 'schema') ? $class::schema() : null;
    }

    /**
     * Iterate every registered (shape ID, class-string) pair. Useful
     * for building higher-level indexes on top of the registry (a
     * category-scoped subset, a namespace filter, etc.).
     *
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return self::CLASSES_BY_SHAPE_ID;
    }
}
