<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
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
use Smithy\ProtocolTests\RestJson\Shape\DocumentTypeAsMapValueInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\DocumentTypeAsPayloadInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\DocumentTypeInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\EnumPayloadInput;
use Smithy\ProtocolTests\RestJson\Shape\HttpChecksumRequiredInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\HttpPayloadTraitsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\HttpPayloadWithStructureInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\HttpPayloadWithUnionInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\InputAndOutputWithHeadersIO;
use Smithy\ProtocolTests\RestJson\Shape\JsonBlobsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonEnumsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonIntEnumsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonListsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonMapsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\JsonTimestampsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\NullAndEmptyHeadersIO;
use Smithy\ProtocolTests\RestJson\Shape\RecursiveShapesInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\ResponseCodeHttpFallbackInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\SimpleScalarPropertiesInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\SparseJsonListsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\SparseJsonMapsInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\StringPayloadInput;
use Smithy\ProtocolTests\RestJson\Shape\TestBodyStructureInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TestNoPayloadInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TestPayloadBlobInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TestPayloadStructureInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\TimestampFormatHeadersIO;
use Smithy\ProtocolTests\RestJson\Shape\UnionInputOutput;
use Smithy\ProtocolTests\RestJson\Shape\Unit;

/**
 * Public contract for the RestJson service client.
 */
interface RestJsonClientInterface extends ClientInterface
{
    /**
     * This example uses all query string types.
     */
    public function allQueryStringTypes(
        ?AllQueryStringTypesInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * This example uses fixed query string params and variable query string params. The fixed query string parameters and
     * variable parameters must both be serialized (implementations may need to merge them together).
     */
    public function constantAndVariableQueryString(
        ?ConstantAndVariableQueryStringInput $input = null,
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
        ?ContentTypeParametersInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): ContentTypeParametersOutput;

    public function datetimeOffsets(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DatetimeOffsetsOutput;

    /**
     * This example serializes a document as part of the payload.
     */
    public function documentType(
        ?DocumentTypeInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DocumentTypeInputOutput;

    /**
     * This example serializes documents as the value of maps.
     */
    public function documentTypeAsMapValue(
        ?DocumentTypeAsMapValueInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DocumentTypeAsMapValueInputOutput;

    /**
     * This example serializes a document as the entire HTTP payload.
     */
    public function documentTypeAsPayload(
        ?DocumentTypeAsPayloadInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DocumentTypeAsPayloadInputOutput;

    public function duplexStream(
        ?DuplexStreamInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DuplexStreamOutput;

    public function duplexStreamWithInitialMessages(
        DuplexStreamWithInitialMessagesInput $input,
        ?RequestOverrideConfig $overrides = null
    ): DuplexStreamWithInitialMessagesOutput;

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

    public function endpointWithHostLabelOperation(
        HostLabelInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function fractionalSeconds(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FractionalSecondsOutput;

    /**
     * This operation has four possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
     * An InvalidGreeting error. 3. A BadRequest error. 4. A FooError. Implementations must be able to successfully take a
     * response and properly (de)serialize successful and error responses based on the the presence of the
     */
    public function greetingWithErrors(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput;

    public function hostWithPathOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * This example tests httpChecksumRequired trait
     */
    public function httpChecksumRequired(
        ?HttpChecksumRequiredInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpChecksumRequiredInputOutput;

    /**
     * Clients that perform this test extract all headers from the response.
     */
    public function httpEmptyPrefixHeaders(
        ?HttpEmptyPrefixHeadersInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpEmptyPrefixHeadersOutput;

    public function httpEnumPayload(
        ?EnumPayloadInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): EnumPayloadInput;

    /**
     * This example serializes a blob shape in the payload. In this example, no JSON document is synthesized because the
     * payload is not a structure or a union type.
     */
    public function httpPayloadTraits(
        ?HttpPayloadTraitsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadTraitsInputOutput;

    /**
     * This example serializes a structure in the payload. Note that serializing a structure changes the wrapper element
     * name to match the targeted structure.
     */
    public function httpPayloadWithStructure(
        ?HttpPayloadWithStructureInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithStructureInputOutput;

    /**
     * This example serializes a union in the payload.
     */
    public function httpPayloadWithUnion(
        ?HttpPayloadWithUnionInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithUnionInputOutput;

    /**
     * This examples adds headers to the input of a request and response by prefix.
     */
    public function httpPrefixHeaders(
        ?HttpPrefixHeadersInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpPrefixHeadersOutput;

    /**
     * Clients that perform this test extract all headers from the response.
     */
    public function httpPrefixHeadersInResponse(
        ?HttpPrefixHeadersInResponseInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpPrefixHeadersInResponseOutput;

    /**
     * This example tests httpQueryParams when no other query parameters exist.
     */
    public function httpQueryParamsOnlyOperation(
        ?HttpQueryParamsOnlyInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

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

    public function httpRequestWithRegexLiteral(
        HttpRequestWithRegexLiteralInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function httpResponseCode(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpResponseCodeOutput;

    public function httpStringPayload(
        ?StringPayloadInput $input = null,
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
        ?InputAndOutputWithHeadersIO $input = null,
        ?RequestOverrideConfig $overrides = null
    ): InputAndOutputWithHeadersIO;

    public function inputStream(?InputStreamInput $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    public function inputStreamWithInitialRequest(
        InputStreamWithInitialRequestInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * Blobs are base64 encoded
     */
    public function jsonBlobs(
        ?JsonBlobsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonBlobsInputOutput;

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function jsonEnums(
        ?JsonEnumsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonEnumsInputOutput;

    /**
     * This example serializes intEnums as top level properties, in lists, sets, and maps.
     */
    public function jsonIntEnums(
        ?JsonIntEnumsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonIntEnumsInputOutput;

    /**
     * This test case serializes JSON lists for the following cases for both input and output: 1. Normal JSON lists. 2.
     * Normal JSON sets. 3. JSON lists of lists. 4. Lists of structures.
     */
    public function jsonLists(
        ?JsonListsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonListsInputOutput;

    /**
     * The example tests basic map serialization.
     */
    public function jsonMaps(
        ?JsonMapsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonMapsInputOutput;

    /**
     * This tests how timestamps are serialized, including using the default format of date-time and various
     * @timestampFormat trait values.
     */
    public function jsonTimestamps(
        ?JsonTimestampsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonTimestampsInputOutput;

    /**
     * This operation uses unions for inputs and outputs.
     */
    public function jsonUnions(
        ?UnionInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): UnionInputOutput;

    /**
     * This example ensures that mediaType strings are base64 encoded in headers.
     */
    public function mediaTypeHeader(
        ?MediaTypeHeaderInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): MediaTypeHeaderOutput;

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
        ?NullAndEmptyHeadersIO $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NullAndEmptyHeadersIO;

    /**
     * Null headers are not sent over the wire, empty headers are serialized to ""
     */
    public function nullAndEmptyHeadersServer(
        ?NullAndEmptyHeadersIO $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NullAndEmptyHeadersIO;

    /**
     * Omits null, but serializes empty string value.
     */
    public function omitsNullSerializesEmptyString(
        ?OmitsNullSerializesEmptyStringInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * Omits serializing empty lists. Because empty strings are serilized as `Foo=`, empty lists cannot also be serialized
     * as `Foo=` and instead must be omitted.
     */
    public function omitsSerializingEmptyLists(
        ?OmitsSerializingEmptyListsInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function outputStream(?Unit $input = null, ?RequestOverrideConfig $overrides = null): OutputStreamOutput;

    public function outputStreamWithInitialResponse(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): OutputStreamWithInitialResponseOutput;

    /**
     * This operation defines a union that uses jsonName on some members.
     */
    public function postUnionWithJsonName(
        ?PostUnionWithJsonNameInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): PostUnionWithJsonNameOutput;

    public function putWithContentEncoding(
        ?PutWithContentEncodingInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * Automatically adds idempotency tokens.
     */
    public function queryIdempotencyTokenAutoFill(
        ?QueryIdempotencyTokenAutoFillInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function queryParamsAsStringListMap(
        ?QueryParamsAsStringListMapInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function queryPrecedence(
        ?QueryPrecedenceInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * Recursive shapes
     */
    public function recursiveShapes(
        ?RecursiveShapesInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): RecursiveShapesInputOutput;

    public function responseCodeHttpFallback(
        ?ResponseCodeHttpFallbackInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): ResponseCodeHttpFallbackInputOutput;

    public function responseCodeRequired(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): ResponseCodeRequiredOutput;

    public function simpleScalarProperties(
        ?SimpleScalarPropertiesInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarPropertiesInputOutput;

    public function sparseJsonLists(
        ?SparseJsonListsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SparseJsonListsInputOutput;

    /**
     * This example tests sparse map serialization.
     */
    public function sparseJsonMaps(
        ?SparseJsonMapsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SparseJsonMapsInputOutput;

    /**
     * This example operation serializes a structure in the HTTP body. It should ensure Content-Type: application/json is
     * used in all requests and that an "empty" body is an empty JSON document ({}).
     */
    public function testBodyStructure(
        ?TestBodyStructureInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestBodyStructureInputOutput;

    /**
     * This example GET operation has no input and serializes a request without a HTTP body. These tests are to ensure we do
     * not attach a body or related headers (Content-Length, Content-Type) to operations that semantically cannot produce an
     * HTTP body.
     */
    public function testGetNoInputNoPayload(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput;

    /**
     * This example GET operation serializes a request without a modeled HTTP body. These tests are to ensure we do not
     * attach a body or related headers (Content-Length, Content-Type) to operations that semantically cannot produce an
     * HTTP body.
     */
    public function testGetNoPayload(
        ?TestNoPayloadInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput;

    /**
     * This example operation serializes a payload targeting a blob. The Blob shape is not structured content and we cannot
     * make assumptions about what data will be sent. This test ensures only a generic "Content-Type:
     * application/octet-stream" header is used, and that we are not treating an empty body as an empty JSON document.
     */
    public function testPayloadBlob(
        ?TestPayloadBlobInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestPayloadBlobInputOutput;

    /**
     * This example operation serializes a payload targeting a structure. This enforces the same requirements as
     * TestBodyStructure but with the body specified by the @httpPayload trait.
     */
    public function testPayloadStructure(
        ?TestPayloadStructureInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestPayloadStructureInputOutput;

    /**
     * This example POST operation has no input and serializes a request without a HTTP body. These tests are to ensure we
     * do not attach a body or related headers (Content-Type) to a POST operation with no modeled input.
     */
    public function testPostNoInputNoPayload(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput;

    /**
     * This example POST operation serializes a request without a modeled HTTP body. These tests are to ensure we do not
     * attach a body or related headers (Content-Type) to a POST operation with no modeled payload.
     */
    public function testPostNoPayload(
        ?TestNoPayloadInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput;

    /**
     * This example tests how timestamp request and response headers are serialized.
     */
    public function timestampFormatHeaders(
        ?TimestampFormatHeadersIO $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TimestampFormatHeadersIO;

    /**
     * This test is similar to NoInputAndNoOutput, but uses explicit Unit types.
     */
    public function unitInputAndOutput(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;
}
