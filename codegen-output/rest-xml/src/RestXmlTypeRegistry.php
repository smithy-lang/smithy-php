<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml;

/**
 * Shape-ID registry for the RestXml service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for a raw
 * class-string when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class RestXmlTypeRegistry implements \Smithy\Core\Serde\TypeRegistryInterface
{
    use \Smithy\Core\Serde\TypeRegistryDeserializeTrait;

    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'aws.protocoltests.restxml#AllQueryStringTypesInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\AllQueryStringTypesInput',
        'aws.protocoltests.restxml#BodyWithXmlNameInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\BodyWithXmlNameInputOutput',
        'aws.protocoltests.restxml#ComplexError' => 'Smithy\\ProtocolTests\\RestXml\\Exception\\ComplexErrorException',
        'aws.protocoltests.restxml#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\ComplexNestedErrorData',
        'aws.protocoltests.restxml#ConstantAndVariableQueryStringInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\ConstantAndVariableQueryStringInput',
        'aws.protocoltests.restxml#ConstantQueryStringInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\ConstantQueryStringInput',
        'aws.protocoltests.restxml#ContentTypeParametersInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\ContentTypeParametersInput',
        'aws.protocoltests.restxml#ContentTypeParametersOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\ContentTypeParametersOutput',
        'aws.protocoltests.restxml#DatetimeOffsetsOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\DatetimeOffsetsOutput',
        'aws.protocoltests.restxml#EmptyInputAndEmptyOutputInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\EmptyInputAndEmptyOutputInput',
        'aws.protocoltests.restxml#EmptyInputAndEmptyOutputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\EmptyInputAndEmptyOutputOutput',
        'aws.protocoltests.restxml#EndpointWithHostLabelOperationRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\EndpointWithHostLabelOperationRequest',
        'aws.protocoltests.restxml#EnumPayloadInput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\EnumPayloadInput',
        'aws.protocoltests.restxml#FlattenedXmlMapRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\FlattenedXmlMapRequest',
        'aws.protocoltests.restxml#FlattenedXmlMapResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\FlattenedXmlMapResponse',
        'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\FlattenedXmlMapWithXmlNameRequest',
        'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\FlattenedXmlMapWithXmlNameResponse',
        'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNamespaceOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\FlattenedXmlMapWithXmlNamespaceOutput',
        'aws.protocoltests.restxml#FractionalSecondsOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\FractionalSecondsOutput',
        'aws.protocoltests.restxml#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\GreetingWithErrorsOutput',
        'aws.protocoltests.restxml#HostLabelHeaderInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\HostLabelHeaderInput',
        'aws.protocoltests.restxml#HttpEmptyPrefixHeadersInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\HttpEmptyPrefixHeadersInput',
        'aws.protocoltests.restxml#HttpEmptyPrefixHeadersOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\HttpEmptyPrefixHeadersOutput',
        'aws.protocoltests.restxml#HttpPayloadTraitsInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadTraitsInputOutput',
        'aws.protocoltests.restxml#HttpPayloadWithMemberXmlNameInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithMemberXmlNameInputOutput',
        'aws.protocoltests.restxml#HttpPayloadWithStructureInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithStructureInputOutput',
        'aws.protocoltests.restxml#HttpPayloadWithUnionInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithUnionInputOutput',
        'aws.protocoltests.restxml#HttpPayloadWithXmlNameInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithXmlNameInputOutput',
        'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceAndPrefixInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithXmlNamespaceAndPrefixInputOutput',
        'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithXmlNamespaceInputOutput',
        'aws.protocoltests.restxml#HttpPrefixHeadersInputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPrefixHeadersInputOutput',
        'aws.protocoltests.restxml#HttpRequestWithFloatLabelsInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithFloatLabelsInput',
        'aws.protocoltests.restxml#HttpRequestWithGreedyLabelInPathInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithGreedyLabelInPathInput',
        'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithLabelsAndTimestampFormatInput',
        'aws.protocoltests.restxml#HttpRequestWithLabelsInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithLabelsInput',
        'aws.protocoltests.restxml#HttpResponseCodeOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\HttpResponseCodeOutput',
        'aws.protocoltests.restxml#IgnoreQueryParamsInResponseOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\IgnoreQueryParamsInResponseOutput',
        'aws.protocoltests.restxml#InputAndOutputWithHeadersIO' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\InputAndOutputWithHeadersIO',
        'aws.protocoltests.restxml#InvalidGreeting' => 'Smithy\\ProtocolTests\\RestXml\\Exception\\InvalidGreetingException',
        'aws.protocoltests.restxml#NestedPayload' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\NestedPayload',
        'aws.protocoltests.restxml#NestedXmlMapWithXmlNameRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\NestedXmlMapWithXmlNameRequest',
        'aws.protocoltests.restxml#NestedXmlMapWithXmlNameResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\NestedXmlMapWithXmlNameResponse',
        'aws.protocoltests.restxml#NestedXmlMapsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\NestedXmlMapsRequest',
        'aws.protocoltests.restxml#NestedXmlMapsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\NestedXmlMapsResponse',
        'aws.protocoltests.restxml#NoInputAndOutputOutput' => 'Smithy\\ProtocolTests\\RestXml\\Output\\NoInputAndOutputOutput',
        'aws.protocoltests.restxml#NullAndEmptyHeadersIO' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\NullAndEmptyHeadersIO',
        'aws.protocoltests.restxml#OmitsNullSerializesEmptyStringInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\OmitsNullSerializesEmptyStringInput',
        'aws.protocoltests.restxml#PayloadWithXmlName' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\PayloadWithXmlName',
        'aws.protocoltests.restxml#PayloadWithXmlNamespace' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\PayloadWithXmlNamespace',
        'aws.protocoltests.restxml#PayloadWithXmlNamespaceAndPrefix' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\PayloadWithXmlNamespaceAndPrefix',
        'aws.protocoltests.restxml#PutWithContentEncodingInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\PutWithContentEncodingInput',
        'aws.protocoltests.restxml#QueryIdempotencyTokenAutoFillInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\QueryIdempotencyTokenAutoFillInput',
        'aws.protocoltests.restxml#QueryParamsAsStringListMapInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\QueryParamsAsStringListMapInput',
        'aws.protocoltests.restxml#QueryPrecedenceInput' => 'Smithy\\ProtocolTests\\RestXml\\Input\\QueryPrecedenceInput',
        'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\RecursiveShapesInputOutputNested1',
        'aws.protocoltests.restxml#RecursiveShapesInputOutputNested2' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\RecursiveShapesInputOutputNested2',
        'aws.protocoltests.restxml#RecursiveShapesRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\RecursiveShapesRequest',
        'aws.protocoltests.restxml#RecursiveShapesResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\RecursiveShapesResponse',
        'aws.protocoltests.restxml#SimpleScalarPropertiesRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\SimpleScalarPropertiesRequest',
        'aws.protocoltests.restxml#SimpleScalarPropertiesResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\SimpleScalarPropertiesResponse',
        'aws.protocoltests.restxml#StringEnum' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\StringEnum',
        'aws.protocoltests.restxml#StringPayloadInput' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\StringPayloadInput',
        'aws.protocoltests.restxml#StructureListMember' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\StructureListMember',
        'aws.protocoltests.restxml#TimestampFormatHeadersIO' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\TimestampFormatHeadersIO',
        'aws.protocoltests.restxml#UnionPayload' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\UnionPayload',
        'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesInMiddlePayloadRequest',
        'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesInMiddlePayloadResponse',
        'aws.protocoltests.restxml#XmlAttributesInMiddleRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlAttributesInMiddleRequest',
        'aws.protocoltests.restxml#XmlAttributesInMiddleResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlAttributesInMiddleResponse',
        'aws.protocoltests.restxml#XmlAttributesOnPayloadRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlAttributesOnPayloadRequest',
        'aws.protocoltests.restxml#XmlAttributesOnPayloadResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlAttributesOnPayloadResponse',
        'aws.protocoltests.restxml#XmlAttributesPayloadRequest' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesPayloadRequest',
        'aws.protocoltests.restxml#XmlAttributesPayloadResponse' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesPayloadResponse',
        'aws.protocoltests.restxml#XmlAttributesRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlAttributesRequest',
        'aws.protocoltests.restxml#XmlAttributesResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlAttributesResponse',
        'aws.protocoltests.restxml#XmlBlobsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlBlobsRequest',
        'aws.protocoltests.restxml#XmlBlobsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlBlobsResponse',
        'aws.protocoltests.restxml#XmlEmptyBlobsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyBlobsRequest',
        'aws.protocoltests.restxml#XmlEmptyBlobsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyBlobsResponse',
        'aws.protocoltests.restxml#XmlEmptyListsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyListsRequest',
        'aws.protocoltests.restxml#XmlEmptyListsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyListsResponse',
        'aws.protocoltests.restxml#XmlEmptyMapsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyMapsRequest',
        'aws.protocoltests.restxml#XmlEmptyMapsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyMapsResponse',
        'aws.protocoltests.restxml#XmlEmptyStringsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyStringsRequest',
        'aws.protocoltests.restxml#XmlEmptyStringsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyStringsResponse',
        'aws.protocoltests.restxml#XmlEnumsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEnumsRequest',
        'aws.protocoltests.restxml#XmlEnumsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEnumsResponse',
        'aws.protocoltests.restxml#XmlIntEnumsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlIntEnumsRequest',
        'aws.protocoltests.restxml#XmlIntEnumsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlIntEnumsResponse',
        'aws.protocoltests.restxml#XmlListsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlListsRequest',
        'aws.protocoltests.restxml#XmlListsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlListsResponse',
        'aws.protocoltests.restxml#XmlMapWithXmlNamespaceRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlMapWithXmlNamespaceRequest',
        'aws.protocoltests.restxml#XmlMapWithXmlNamespaceResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlMapWithXmlNamespaceResponse',
        'aws.protocoltests.restxml#XmlMapsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlMapsRequest',
        'aws.protocoltests.restxml#XmlMapsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlMapsResponse',
        'aws.protocoltests.restxml#XmlMapsXmlNameRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlMapsXmlNameRequest',
        'aws.protocoltests.restxml#XmlMapsXmlNameResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlMapsXmlNameResponse',
        'aws.protocoltests.restxml#XmlNamespaceNested' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlNamespaceNested',
        'aws.protocoltests.restxml#XmlNamespacesRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlNamespacesRequest',
        'aws.protocoltests.restxml#XmlNamespacesResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlNamespacesResponse',
        'aws.protocoltests.restxml#XmlNestedUnionStruct' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlNestedUnionStruct',
        'aws.protocoltests.restxml#XmlTimestampsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlTimestampsRequest',
        'aws.protocoltests.restxml#XmlTimestampsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlTimestampsResponse',
        'aws.protocoltests.restxml#XmlUnionShape' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlUnionShape',
        'aws.protocoltests.restxml#XmlUnionsRequest' => 'Smithy\\ProtocolTests\\RestXml\\Input\\XmlUnionsRequest',
        'aws.protocoltests.restxml#XmlUnionsResponse' => 'Smithy\\ProtocolTests\\RestXml\\Output\\XmlUnionsResponse',
        'aws.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\FooEnum',
        'aws.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\GreetingStruct',
        'aws.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\RestXml\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\RestXml\\Input\\AllQueryStringTypesInput' => 'aws.protocoltests.restxml#AllQueryStringTypesInput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\BodyWithXmlNameInputOutput' => 'aws.protocoltests.restxml#BodyWithXmlNameInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Exception\\ComplexErrorException' => 'aws.protocoltests.restxml#ComplexError',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\ComplexNestedErrorData' => 'aws.protocoltests.restxml#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\RestXml\\Input\\ConstantAndVariableQueryStringInput' => 'aws.protocoltests.restxml#ConstantAndVariableQueryStringInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\ConstantQueryStringInput' => 'aws.protocoltests.restxml#ConstantQueryStringInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\ContentTypeParametersInput' => 'aws.protocoltests.restxml#ContentTypeParametersInput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\ContentTypeParametersOutput' => 'aws.protocoltests.restxml#ContentTypeParametersOutput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\DatetimeOffsetsOutput' => 'aws.protocoltests.restxml#DatetimeOffsetsOutput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\EmptyInputAndEmptyOutputInput' => 'aws.protocoltests.restxml#EmptyInputAndEmptyOutputInput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\EmptyInputAndEmptyOutputOutput' => 'aws.protocoltests.restxml#EmptyInputAndEmptyOutputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\EndpointWithHostLabelOperationRequest' => 'aws.protocoltests.restxml#EndpointWithHostLabelOperationRequest',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\EnumPayloadInput' => 'aws.protocoltests.restxml#EnumPayloadInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\FlattenedXmlMapRequest' => 'aws.protocoltests.restxml#FlattenedXmlMapRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\FlattenedXmlMapResponse' => 'aws.protocoltests.restxml#FlattenedXmlMapResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\FlattenedXmlMapWithXmlNameRequest' => 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\FlattenedXmlMapWithXmlNameResponse' => 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNameResponse',
        'Smithy\\ProtocolTests\\RestXml\\Output\\FlattenedXmlMapWithXmlNamespaceOutput' => 'aws.protocoltests.restxml#FlattenedXmlMapWithXmlNamespaceOutput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\FractionalSecondsOutput' => 'aws.protocoltests.restxml#FractionalSecondsOutput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\GreetingWithErrorsOutput' => 'aws.protocoltests.restxml#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\HostLabelHeaderInput' => 'aws.protocoltests.restxml#HostLabelHeaderInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\HttpEmptyPrefixHeadersInput' => 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersInput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\HttpEmptyPrefixHeadersOutput' => 'aws.protocoltests.restxml#HttpEmptyPrefixHeadersOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadTraitsInputOutput' => 'aws.protocoltests.restxml#HttpPayloadTraitsInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithMemberXmlNameInputOutput' => 'aws.protocoltests.restxml#HttpPayloadWithMemberXmlNameInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithStructureInputOutput' => 'aws.protocoltests.restxml#HttpPayloadWithStructureInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithUnionInputOutput' => 'aws.protocoltests.restxml#HttpPayloadWithUnionInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithXmlNameInputOutput' => 'aws.protocoltests.restxml#HttpPayloadWithXmlNameInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithXmlNamespaceAndPrefixInputOutput' => 'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceAndPrefixInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPayloadWithXmlNamespaceInputOutput' => 'aws.protocoltests.restxml#HttpPayloadWithXmlNamespaceInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\HttpPrefixHeadersInputOutput' => 'aws.protocoltests.restxml#HttpPrefixHeadersInputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithFloatLabelsInput' => 'aws.protocoltests.restxml#HttpRequestWithFloatLabelsInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithGreedyLabelInPathInput' => 'aws.protocoltests.restxml#HttpRequestWithGreedyLabelInPathInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithLabelsAndTimestampFormatInput' => 'aws.protocoltests.restxml#HttpRequestWithLabelsAndTimestampFormatInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\HttpRequestWithLabelsInput' => 'aws.protocoltests.restxml#HttpRequestWithLabelsInput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\HttpResponseCodeOutput' => 'aws.protocoltests.restxml#HttpResponseCodeOutput',
        'Smithy\\ProtocolTests\\RestXml\\Output\\IgnoreQueryParamsInResponseOutput' => 'aws.protocoltests.restxml#IgnoreQueryParamsInResponseOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\InputAndOutputWithHeadersIO' => 'aws.protocoltests.restxml#InputAndOutputWithHeadersIO',
        'Smithy\\ProtocolTests\\RestXml\\Exception\\InvalidGreetingException' => 'aws.protocoltests.restxml#InvalidGreeting',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\NestedPayload' => 'aws.protocoltests.restxml#NestedPayload',
        'Smithy\\ProtocolTests\\RestXml\\Input\\NestedXmlMapWithXmlNameRequest' => 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\NestedXmlMapWithXmlNameResponse' => 'aws.protocoltests.restxml#NestedXmlMapWithXmlNameResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\NestedXmlMapsRequest' => 'aws.protocoltests.restxml#NestedXmlMapsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\NestedXmlMapsResponse' => 'aws.protocoltests.restxml#NestedXmlMapsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Output\\NoInputAndOutputOutput' => 'aws.protocoltests.restxml#NoInputAndOutputOutput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\NullAndEmptyHeadersIO' => 'aws.protocoltests.restxml#NullAndEmptyHeadersIO',
        'Smithy\\ProtocolTests\\RestXml\\Input\\OmitsNullSerializesEmptyStringInput' => 'aws.protocoltests.restxml#OmitsNullSerializesEmptyStringInput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\PayloadWithXmlName' => 'aws.protocoltests.restxml#PayloadWithXmlName',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\PayloadWithXmlNamespace' => 'aws.protocoltests.restxml#PayloadWithXmlNamespace',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\PayloadWithXmlNamespaceAndPrefix' => 'aws.protocoltests.restxml#PayloadWithXmlNamespaceAndPrefix',
        'Smithy\\ProtocolTests\\RestXml\\Input\\PutWithContentEncodingInput' => 'aws.protocoltests.restxml#PutWithContentEncodingInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\QueryIdempotencyTokenAutoFillInput' => 'aws.protocoltests.restxml#QueryIdempotencyTokenAutoFillInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\QueryParamsAsStringListMapInput' => 'aws.protocoltests.restxml#QueryParamsAsStringListMapInput',
        'Smithy\\ProtocolTests\\RestXml\\Input\\QueryPrecedenceInput' => 'aws.protocoltests.restxml#QueryPrecedenceInput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\RecursiveShapesInputOutputNested1' => 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested1',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\RecursiveShapesInputOutputNested2' => 'aws.protocoltests.restxml#RecursiveShapesInputOutputNested2',
        'Smithy\\ProtocolTests\\RestXml\\Input\\RecursiveShapesRequest' => 'aws.protocoltests.restxml#RecursiveShapesRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\RecursiveShapesResponse' => 'aws.protocoltests.restxml#RecursiveShapesResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\SimpleScalarPropertiesRequest' => 'aws.protocoltests.restxml#SimpleScalarPropertiesRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\SimpleScalarPropertiesResponse' => 'aws.protocoltests.restxml#SimpleScalarPropertiesResponse',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\StringEnum' => 'aws.protocoltests.restxml#StringEnum',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\StringPayloadInput' => 'aws.protocoltests.restxml#StringPayloadInput',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\StructureListMember' => 'aws.protocoltests.restxml#StructureListMember',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\TimestampFormatHeadersIO' => 'aws.protocoltests.restxml#TimestampFormatHeadersIO',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\UnionPayload' => 'aws.protocoltests.restxml#UnionPayload',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesInMiddlePayloadRequest' => 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadRequest',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesInMiddlePayloadResponse' => 'aws.protocoltests.restxml#XmlAttributesInMiddlePayloadResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlAttributesInMiddleRequest' => 'aws.protocoltests.restxml#XmlAttributesInMiddleRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlAttributesInMiddleResponse' => 'aws.protocoltests.restxml#XmlAttributesInMiddleResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlAttributesOnPayloadRequest' => 'aws.protocoltests.restxml#XmlAttributesOnPayloadRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlAttributesOnPayloadResponse' => 'aws.protocoltests.restxml#XmlAttributesOnPayloadResponse',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesPayloadRequest' => 'aws.protocoltests.restxml#XmlAttributesPayloadRequest',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlAttributesPayloadResponse' => 'aws.protocoltests.restxml#XmlAttributesPayloadResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlAttributesRequest' => 'aws.protocoltests.restxml#XmlAttributesRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlAttributesResponse' => 'aws.protocoltests.restxml#XmlAttributesResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlBlobsRequest' => 'aws.protocoltests.restxml#XmlBlobsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlBlobsResponse' => 'aws.protocoltests.restxml#XmlBlobsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyBlobsRequest' => 'aws.protocoltests.restxml#XmlEmptyBlobsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyBlobsResponse' => 'aws.protocoltests.restxml#XmlEmptyBlobsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyListsRequest' => 'aws.protocoltests.restxml#XmlEmptyListsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyListsResponse' => 'aws.protocoltests.restxml#XmlEmptyListsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyMapsRequest' => 'aws.protocoltests.restxml#XmlEmptyMapsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyMapsResponse' => 'aws.protocoltests.restxml#XmlEmptyMapsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEmptyStringsRequest' => 'aws.protocoltests.restxml#XmlEmptyStringsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEmptyStringsResponse' => 'aws.protocoltests.restxml#XmlEmptyStringsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlEnumsRequest' => 'aws.protocoltests.restxml#XmlEnumsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlEnumsResponse' => 'aws.protocoltests.restxml#XmlEnumsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlIntEnumsRequest' => 'aws.protocoltests.restxml#XmlIntEnumsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlIntEnumsResponse' => 'aws.protocoltests.restxml#XmlIntEnumsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlListsRequest' => 'aws.protocoltests.restxml#XmlListsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlListsResponse' => 'aws.protocoltests.restxml#XmlListsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlMapWithXmlNamespaceRequest' => 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlMapWithXmlNamespaceResponse' => 'aws.protocoltests.restxml#XmlMapWithXmlNamespaceResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlMapsRequest' => 'aws.protocoltests.restxml#XmlMapsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlMapsResponse' => 'aws.protocoltests.restxml#XmlMapsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlMapsXmlNameRequest' => 'aws.protocoltests.restxml#XmlMapsXmlNameRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlMapsXmlNameResponse' => 'aws.protocoltests.restxml#XmlMapsXmlNameResponse',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlNamespaceNested' => 'aws.protocoltests.restxml#XmlNamespaceNested',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlNamespacesRequest' => 'aws.protocoltests.restxml#XmlNamespacesRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlNamespacesResponse' => 'aws.protocoltests.restxml#XmlNamespacesResponse',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlNestedUnionStruct' => 'aws.protocoltests.restxml#XmlNestedUnionStruct',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlTimestampsRequest' => 'aws.protocoltests.restxml#XmlTimestampsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlTimestampsResponse' => 'aws.protocoltests.restxml#XmlTimestampsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\XmlUnionShape' => 'aws.protocoltests.restxml#XmlUnionShape',
        'Smithy\\ProtocolTests\\RestXml\\Input\\XmlUnionsRequest' => 'aws.protocoltests.restxml#XmlUnionsRequest',
        'Smithy\\ProtocolTests\\RestXml\\Output\\XmlUnionsResponse' => 'aws.protocoltests.restxml#XmlUnionsResponse',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\FooEnum' => 'aws.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\GreetingStruct' => 'aws.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\RestXml\\Shape\\IntegerEnum' => 'aws.protocoltests.shared#IntegerEnum'
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
    public function schemaFor(string $shapeId): ?\Smithy\Core\Serde\Schema
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
