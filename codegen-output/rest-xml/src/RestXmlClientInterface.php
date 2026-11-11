<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
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
use Smithy\ProtocolTests\RestXml\Shape\EnumPayloadInput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadTraitsInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithMemberXmlNameInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithStructureInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithUnionInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithXmlNameInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithXmlNamespaceAndPrefixInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPayloadWithXmlNamespaceInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\HttpPrefixHeadersInputOutput;
use Smithy\ProtocolTests\RestXml\Shape\InputAndOutputWithHeadersIO;
use Smithy\ProtocolTests\RestXml\Shape\NullAndEmptyHeadersIO;
use Smithy\ProtocolTests\RestXml\Shape\StringPayloadInput;
use Smithy\ProtocolTests\RestXml\Shape\TimestampFormatHeadersIO;
use Smithy\ProtocolTests\RestXml\Shape\Unit;

/**
 * Public contract for the RestXml service client.
 */
interface RestXmlClientInterface extends ClientInterface
{
    /**
     * This example uses all query string types.
     */
    public function allQueryStringTypes(
        AllQueryStringTypesInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * The following example serializes a body that uses an XML name, changing the wrapper name.
     */
    public function bodyWithXmlName(
        BodyWithXmlNameInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): BodyWithXmlNameInputOutput;

    /**
     * This example uses fixed query string params and variable query string params. The fixed query string parameters and
     * variable parameters must both be serialized (implementations may need to merge them together).
     */
    public function constantAndVariableQueryString(
        ConstantAndVariableQueryStringInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * This example uses a constant query string parameters and a label. This simply tests that labels and query string
     * parameters are compatible. The fixed query string parameter named "hello" should in no way conflict with the label,
     * `{hello}`.
     */
    public function constantQueryString(
        ConstantQueryStringInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * The example tests how servers must support requests containing a `Content-Type` header with parameters.
     */
    public function contentTypeParameters(
        ContentTypeParametersInput $input,
        ?RequestOverrideConfig $overrides = null
    ): ContentTypeParametersOutput;

    public function datetimeOffsets(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DatetimeOffsetsOutput;

    /**
     * The example tests how requests and responses are serialized when there's no request or response payload because the
     * operation has an empty input and empty output structure that reuses the same shape. While this should be rare, code
     * generators must support this.
     */
    public function emptyInputAndEmptyOutput(
        ?EmptyInputAndEmptyOutputInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): EmptyInputAndEmptyOutputOutput;

    public function endpointOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    public function endpointWithHostLabelHeaderOperation(
        HostLabelHeaderInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function endpointWithHostLabelOperation(
        EndpointWithHostLabelOperationRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * Flattened maps
     */
    public function flattenedXmlMap(
        FlattenedXmlMapRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapResponse;

    /**
     * Flattened maps with @xmlName
     */
    public function flattenedXmlMapWithXmlName(
        FlattenedXmlMapWithXmlNameRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapWithXmlNameResponse;

    /**
     * Flattened maps with @xmlNamespace and @xmlName
     */
    public function flattenedXmlMapWithXmlNamespace(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapWithXmlNamespaceOutput;

    public function fractionalSeconds(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FractionalSecondsOutput;

    /**
     * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
     * An InvalidGreeting error. 3. A BadRequest error. Implementations must be able to successfully take a response and
     * properly (de)serialize successful and error responses based on the the presence of the
     */
    public function greetingWithErrors(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput;

    /**
     * Clients that perform this test extract all headers from the response.
     */
    public function httpEmptyPrefixHeaders(
        HttpEmptyPrefixHeadersInput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpEmptyPrefixHeadersOutput;

    public function httpEnumPayload(
        EnumPayloadInput $input,
        ?RequestOverrideConfig $overrides = null
    ): EnumPayloadInput;

    /**
     * This example serializes a blob shape in the payload. In this example, no XML document is synthesized because the
     * payload is not a structure or a union type.
     */
    public function httpPayloadTraits(
        HttpPayloadTraitsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadTraitsInputOutput;

    /**
     * The following example serializes a payload that uses an XML name on the member, changing the wrapper name.
     */
    public function httpPayloadWithMemberXmlName(
        HttpPayloadWithMemberXmlNameInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithMemberXmlNameInputOutput;

    /**
     * This example serializes a structure in the payload. Note that serializing a structure changes the wrapper element
     * name to match the targeted structure.
     */
    public function httpPayloadWithStructure(
        HttpPayloadWithStructureInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithStructureInputOutput;

    /**
     * This example serializes a union in the payload.
     */
    public function httpPayloadWithUnion(
        HttpPayloadWithUnionInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithUnionInputOutput;

    /**
     * The following example serializes a payload that uses an XML name, changing the wrapper name.
     */
    public function httpPayloadWithXmlName(
        HttpPayloadWithXmlNameInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithXmlNameInputOutput;

    /**
     * The following example serializes a payload that uses an XML namespace.
     */
    public function httpPayloadWithXmlNamespace(
        HttpPayloadWithXmlNamespaceInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithXmlNamespaceInputOutput;

    /**
     * The following example serializes a payload that uses an XML namespace.
     */
    public function httpPayloadWithXmlNamespaceAndPrefix(
        HttpPayloadWithXmlNamespaceAndPrefixInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithXmlNamespaceAndPrefixInputOutput;

    /**
     * This examples adds headers to the input of a request and response by prefix.
     */
    public function httpPrefixHeaders(
        HttpPrefixHeadersInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPrefixHeadersInputOutput;

    public function httpRequestWithFloatLabels(
        HttpRequestWithFloatLabelsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function httpRequestWithGreedyLabelInPath(
        HttpRequestWithGreedyLabelInPathInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * The example tests how requests are serialized when there's no input payload but there are HTTP labels.
     */
    public function httpRequestWithLabels(
        HttpRequestWithLabelsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * The example tests how requests serialize different timestamp formats in the URI path.
     */
    public function httpRequestWithLabelsAndTimestampFormat(
        HttpRequestWithLabelsAndTimestampFormatInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function httpResponseCode(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpResponseCodeOutput;

    public function httpStringPayload(
        StringPayloadInput $input,
        ?RequestOverrideConfig $overrides = null
    ): StringPayloadInput;

    /**
     * This example ensures that query string bound request parameters are serialized in the body of responses if the
     * structure is used in both the request and response.
     */
    public function ignoreQueryParamsInResponse(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): IgnoreQueryParamsInResponseOutput;

    /**
     * The example tests how requests and responses are serialized when there is no input or output payload but there are
     * HTTP header bindings.
     */
    public function inputAndOutputWithHeaders(
        InputAndOutputWithHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): InputAndOutputWithHeadersIO;

    /**
     * Nested Xml Maps with key/values with @xmlName
     */
    public function nestedXmlMapWithXmlName(
        ?NestedXmlMapWithXmlNameRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NestedXmlMapWithXmlNameResponse;

    public function nestedXmlMaps(
        ?NestedXmlMapsRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NestedXmlMapsResponse;

    /**
     * The example tests how requests and responses are serialized when there's no request or response payload because the
     * operation has no input or output. While this should be rare, code generators must support this.
     */
    public function noInputAndNoOutput(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * The example tests how requests and responses are serialized when there's no request or response payload because the
     * operation has no input and the output is empty. While this should be rare, code generators must support this.
     */
    public function noInputAndOutput(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NoInputAndOutputOutput;

    /**
     * Null headers are not sent over the wire, empty headers are serialized to ""
     */
    public function nullAndEmptyHeadersClient(
        NullAndEmptyHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): NullAndEmptyHeadersIO;

    /**
     * Null headers are not sent over the wire, empty headers are serialized to ""
     */
    public function nullAndEmptyHeadersServer(
        NullAndEmptyHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): NullAndEmptyHeadersIO;

    /**
     * Omits null, but serializes empty string value.
     */
    public function omitsNullSerializesEmptyString(
        OmitsNullSerializesEmptyStringInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function putWithContentEncoding(
        PutWithContentEncodingInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * Automatically adds idempotency tokens.
     */
    public function queryIdempotencyTokenAutoFill(
        QueryIdempotencyTokenAutoFillInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function queryParamsAsStringListMap(
        QueryParamsAsStringListMapInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function queryPrecedence(QueryPrecedenceInput $input, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * Recursive shapes
     */
    public function recursiveShapes(
        RecursiveShapesRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): RecursiveShapesResponse;

    public function simpleScalarProperties(
        ?SimpleScalarPropertiesRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarPropertiesResponse;

    /**
     * The example tests how timestamp request and response headers are serialized.
     */
    public function timestampFormatHeaders(
        TimestampFormatHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): TimestampFormatHeadersIO;

    /**
     * This example serializes an XML attributes on synthesized document.
     */
    public function xmlAttributes(
        ?XmlAttributesRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlAttributesResponse;

    /**
     * This example serializes an XML attribute on a payload when it's defined in the middle of the member list. This tests
     * that implementations correctly write attributes immediately after the element start tag, which is critical for
     * languages like C# where attribute writing must happen before child elements.
     */
    public function xmlAttributesInMiddle(
        XmlAttributesInMiddleRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): XmlAttributesInMiddleResponse;

    /**
     * This example serializes an XML attributes on a document targeted by httpPayload.
     */
    public function xmlAttributesOnPayload(
        XmlAttributesOnPayloadRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): XmlAttributesOnPayloadResponse;

    /**
     * Blobs are base64 encoded
     */
    public function xmlBlobs(XmlBlobsRequest $input, ?RequestOverrideConfig $overrides = null): XmlBlobsResponse;

    /**
     * Blobs are base64 encoded
     */
    public function xmlEmptyBlobs(
        XmlEmptyBlobsRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): XmlEmptyBlobsResponse;

    public function xmlEmptyLists(
        ?XmlEmptyListsRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlEmptyListsResponse;

    public function xmlEmptyMaps(
        XmlEmptyMapsRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): XmlEmptyMapsResponse;

    public function xmlEmptyStrings(
        XmlEmptyStringsRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): XmlEmptyStringsResponse;

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function xmlEnums(
        ?XmlEnumsRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlEnumsResponse;

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function xmlIntEnums(
        ?XmlIntEnumsRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlIntEnumsResponse;

    /**
     * This test case serializes XML lists for the following cases for both input and output: 1. Normal XML lists. 2. Normal
     * XML sets. 3. XML lists of lists. 4. XML lists with @xmlName on its members 5. Flattened XML lists. 6. Flattened XML
     * lists with @xmlName. 7. Flattened XML lists with @xmlNamespace. 8. Lists of structures. 9. Flattened XML list of
     * structures
     */
    public function xmlLists(
        ?XmlListsRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlListsResponse;

    /**
     * Maps with @xmlNamespace and @xmlName
     */
    public function xmlMapWithXmlNamespace(
        ?XmlMapWithXmlNamespaceRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlMapWithXmlNamespaceResponse;

    /**
     * The example tests basic map serialization.
     */
    public function xmlMaps(XmlMapsRequest $input, ?RequestOverrideConfig $overrides = null): XmlMapsResponse;

    public function xmlMapsXmlName(
        XmlMapsXmlNameRequest $input,
        ?RequestOverrideConfig $overrides = null
    ): XmlMapsXmlNameResponse;

    public function xmlNamespaces(
        ?XmlNamespacesRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlNamespacesResponse;

    /**
     * This tests how timestamps are serialized, including using the default format of date-time and various
     * @timestampFormat trait values.
     */
    public function xmlTimestamps(
        ?XmlTimestampsRequest $input = null,
        ?RequestOverrideConfig $overrides = null
    ): XmlTimestampsResponse;

    public function xmlUnions(XmlUnionsRequest $input, ?RequestOverrideConfig $overrides = null): XmlUnionsResponse;
}
