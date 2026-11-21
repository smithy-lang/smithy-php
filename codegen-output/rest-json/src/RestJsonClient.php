<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson;

use Smithy\Client\AbstractClient;
use Smithy\Client\AbstractClientConfig;
use Smithy\Client\Plugin\PluginCollection;
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
use Smithy\ProtocolTests\RestJson\Operation\AllQueryStringTypesOperation;
use Smithy\ProtocolTests\RestJson\Operation\ConstantAndVariableQueryStringOperation;
use Smithy\ProtocolTests\RestJson\Operation\ConstantQueryStringOperation;
use Smithy\ProtocolTests\RestJson\Operation\ContentTypeParametersOperation;
use Smithy\ProtocolTests\RestJson\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\RestJson\Operation\DocumentTypeAsMapValueOperation;
use Smithy\ProtocolTests\RestJson\Operation\DocumentTypeAsPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\DocumentTypeOperation;
use Smithy\ProtocolTests\RestJson\Operation\DuplexStreamOperation;
use Smithy\ProtocolTests\RestJson\Operation\DuplexStreamWithInitialMessagesOperation;
use Smithy\ProtocolTests\RestJson\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\RestJson\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\RestJson\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpChecksumRequiredOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpEmptyPrefixHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpEnumPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPayloadTraitsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPayloadWithStructureOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPayloadWithUnionOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPrefixHeadersInResponseOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPrefixHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpQueryParamsOnlyOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithFloatLabelsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithGreedyLabelInPathOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithLabelsAndTimestampFormatOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithLabelsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithRegexLiteralOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpResponseCodeOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpStringPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\IgnoreQueryParamsInResponseOperation;
use Smithy\ProtocolTests\RestJson\Operation\InputAndOutputWithHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\InputStreamOperation;
use Smithy\ProtocolTests\RestJson\Operation\InputStreamWithInitialRequestOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonBlobsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonEnumsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonIntEnumsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonListsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonMapsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonTimestampsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonUnionsOperation;
use Smithy\ProtocolTests\RestJson\Operation\MediaTypeHeaderOperation;
use Smithy\ProtocolTests\RestJson\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\RestJson\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\RestJson\Operation\NullAndEmptyHeadersClientOperation;
use Smithy\ProtocolTests\RestJson\Operation\NullAndEmptyHeadersServerOperation;
use Smithy\ProtocolTests\RestJson\Operation\OmitsNullSerializesEmptyStringOperation;
use Smithy\ProtocolTests\RestJson\Operation\OmitsSerializingEmptyListsOperation;
use Smithy\ProtocolTests\RestJson\Operation\OutputStreamOperation;
use Smithy\ProtocolTests\RestJson\Operation\OutputStreamWithInitialResponseOperation;
use Smithy\ProtocolTests\RestJson\Operation\PostUnionWithJsonNameOperation;
use Smithy\ProtocolTests\RestJson\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\RestJson\Operation\QueryIdempotencyTokenAutoFillOperation;
use Smithy\ProtocolTests\RestJson\Operation\QueryParamsAsStringListMapOperation;
use Smithy\ProtocolTests\RestJson\Operation\QueryPrecedenceOperation;
use Smithy\ProtocolTests\RestJson\Operation\RecursiveShapesOperation;
use Smithy\ProtocolTests\RestJson\Operation\ResponseCodeHttpFallbackOperation;
use Smithy\ProtocolTests\RestJson\Operation\ResponseCodeRequiredOperation;
use Smithy\ProtocolTests\RestJson\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\RestJson\Operation\SparseJsonListsOperation;
use Smithy\ProtocolTests\RestJson\Operation\SparseJsonMapsOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestBodyStructureOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestGetNoInputNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestGetNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPayloadBlobOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPayloadStructureOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPostNoInputNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPostNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TimestampFormatHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\UnitInputAndOutputOperation;
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
 * Client for the RestJson service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class RestJsonClient extends AbstractClient implements RestJsonClientInterface
{
    public function __construct(
        AbstractClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(RestJsonService::instance()),
            $plugins
        );
    }

    /**
     * This example uses all query string types.
     */
    public function allQueryStringTypes(AllQueryStringTypesInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            AllQueryStringTypesOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example uses fixed query string params and variable query string params. The fixed query string parameters and
     * variable parameters must both be serialized (implementations may need to merge them together).
     */
    public function constantAndVariableQueryString(
        ConstantAndVariableQueryStringInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            ConstantAndVariableQueryStringOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example uses a constant query string parameters and a label. This simply tests that labels and query string
     * parameters are compatible. The fixed query string parameter named "hello" should in no way conflict with the label,
     * `{hello}`.
     */
    public function constantQueryString(ConstantQueryStringInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            ConstantQueryStringOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * The example tests how servers must support requests containing a `Content-Type` header with parameters.
     */
    public function contentTypeParameters(
        ContentTypeParametersInput $input,
        ?RequestOverrideConfig $overrides = null
    ): ContentTypeParametersOutput
    {
        /** @var ContentTypeParametersOutput */
        return $this->executeOperation(
            ContentTypeParametersOperation::instance(),
            $input,
            $overrides
        );
    }

    public function datetimeOffsets(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DatetimeOffsetsOutput
    {
        /** @var DatetimeOffsetsOutput */
        return $this->executeOperation(
            DatetimeOffsetsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This example serializes a document as part of the payload.
     */
    public function documentType(
        DocumentTypeInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): DocumentTypeInputOutput
    {
        /** @var DocumentTypeInputOutput */
        return $this->executeOperation(
            DocumentTypeOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example serializes documents as the value of maps.
     */
    public function documentTypeAsMapValue(
        DocumentTypeAsMapValueInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): DocumentTypeAsMapValueInputOutput
    {
        /** @var DocumentTypeAsMapValueInputOutput */
        return $this->executeOperation(
            DocumentTypeAsMapValueOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example serializes a document as the entire HTTP payload.
     */
    public function documentTypeAsPayload(
        DocumentTypeAsPayloadInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): DocumentTypeAsPayloadInputOutput
    {
        /** @var DocumentTypeAsPayloadInputOutput */
        return $this->executeOperation(
            DocumentTypeAsPayloadOperation::instance(),
            $input,
            $overrides
        );
    }

    public function duplexStream(DuplexStreamInput $input, ?RequestOverrideConfig $overrides = null): DuplexStreamOutput
    {
        /** @var DuplexStreamOutput */
        return $this->executeOperation(
            DuplexStreamOperation::instance(),
            $input,
            $overrides
        );
    }

    public function duplexStreamWithInitialMessages(
        DuplexStreamWithInitialMessagesInput $input,
        ?RequestOverrideConfig $overrides = null
    ): DuplexStreamWithInitialMessagesOutput
    {
        /** @var DuplexStreamWithInitialMessagesOutput */
        return $this->executeOperation(
            DuplexStreamWithInitialMessagesOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * The example tests how requests and responses are serialized when there's no request or response payload because the
     * operation has an empty input and empty output structure that reuses the same shape. While this should be rare, code
     * generators must support this.
     */
    public function emptyInputAndEmptyOutput(
        ?EmptyInputAndEmptyOutputInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): EmptyInputAndEmptyOutputOutput
    {
        /** @var EmptyInputAndEmptyOutputOutput */
        return $this->executeOperation(
            EmptyInputAndEmptyOutputOperation::instance(),
            $input ?? new EmptyInputAndEmptyOutputInput(),
            $overrides
        );
    }

    public function endpointOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            EndpointOperationOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function endpointWithHostLabelOperation(
        HostLabelInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            EndpointWithHostLabelOperationOperation::instance(),
            $input,
            $overrides
        );
    }

    public function fractionalSeconds(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FractionalSecondsOutput
    {
        /** @var FractionalSecondsOutput */
        return $this->executeOperation(
            FractionalSecondsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This operation has four possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
     * An InvalidGreeting error. 3. A BadRequest error. 4. A FooError. Implementations must be able to successfully take a
     * response and properly (de)serialize successful and error responses based on the the presence of the
     */
    public function greetingWithErrors(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput
    {
        /** @var GreetingWithErrorsOutput */
        return $this->executeOperation(
            GreetingWithErrorsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function hostWithPathOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            HostWithPathOperationOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This example tests httpChecksumRequired trait
     */
    public function httpChecksumRequired(
        HttpChecksumRequiredInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpChecksumRequiredInputOutput
    {
        /** @var HttpChecksumRequiredInputOutput */
        return $this->executeOperation(
            HttpChecksumRequiredOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Clients that perform this test extract all headers from the response.
     */
    public function httpEmptyPrefixHeaders(
        HttpEmptyPrefixHeadersInput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpEmptyPrefixHeadersOutput
    {
        /** @var HttpEmptyPrefixHeadersOutput */
        return $this->executeOperation(
            HttpEmptyPrefixHeadersOperation::instance(),
            $input,
            $overrides
        );
    }

    public function httpEnumPayload(EnumPayloadInput $input, ?RequestOverrideConfig $overrides = null): EnumPayloadInput
    {
        /** @var EnumPayloadInput */
        return $this->executeOperation(
            HttpEnumPayloadOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example serializes a blob shape in the payload. In this example, no JSON document is synthesized because the
     * payload is not a structure or a union type.
     */
    public function httpPayloadTraits(
        HttpPayloadTraitsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadTraitsInputOutput
    {
        /** @var HttpPayloadTraitsInputOutput */
        return $this->executeOperation(
            HttpPayloadTraitsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example serializes a structure in the payload. Note that serializing a structure changes the wrapper element
     * name to match the targeted structure.
     */
    public function httpPayloadWithStructure(
        HttpPayloadWithStructureInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithStructureInputOutput
    {
        /** @var HttpPayloadWithStructureInputOutput */
        return $this->executeOperation(
            HttpPayloadWithStructureOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example serializes a union in the payload.
     */
    public function httpPayloadWithUnion(
        HttpPayloadWithUnionInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPayloadWithUnionInputOutput
    {
        /** @var HttpPayloadWithUnionInputOutput */
        return $this->executeOperation(
            HttpPayloadWithUnionOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This examples adds headers to the input of a request and response by prefix.
     */
    public function httpPrefixHeaders(
        HttpPrefixHeadersInput $input,
        ?RequestOverrideConfig $overrides = null
    ): HttpPrefixHeadersOutput
    {
        /** @var HttpPrefixHeadersOutput */
        return $this->executeOperation(
            HttpPrefixHeadersOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Clients that perform this test extract all headers from the response.
     */
    public function httpPrefixHeadersInResponse(
        ?HttpPrefixHeadersInResponseInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpPrefixHeadersInResponseOutput
    {
        /** @var HttpPrefixHeadersInResponseOutput */
        return $this->executeOperation(
            HttpPrefixHeadersInResponseOperation::instance(),
            $input ?? new HttpPrefixHeadersInResponseInput(),
            $overrides
        );
    }

    /**
     * This example tests httpQueryParams when no other query parameters exist.
     */
    public function httpQueryParamsOnlyOperation(
        HttpQueryParamsOnlyInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            HttpQueryParamsOnlyOperationOperation::instance(),
            $input,
            $overrides
        );
    }

    public function httpRequestWithFloatLabels(
        HttpRequestWithFloatLabelsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            HttpRequestWithFloatLabelsOperation::instance(),
            $input,
            $overrides
        );
    }

    public function httpRequestWithGreedyLabelInPath(
        HttpRequestWithGreedyLabelInPathInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            HttpRequestWithGreedyLabelInPathOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * The example tests how requests are serialized when there's no input payload but there are HTTP labels.
     */
    public function httpRequestWithLabels(
        HttpRequestWithLabelsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            HttpRequestWithLabelsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * The example tests how requests serialize different timestamp formats in the URI path.
     */
    public function httpRequestWithLabelsAndTimestampFormat(
        HttpRequestWithLabelsAndTimestampFormatInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            HttpRequestWithLabelsAndTimestampFormatOperation::instance(),
            $input,
            $overrides
        );
    }

    public function httpRequestWithRegexLiteral(
        HttpRequestWithRegexLiteralInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            HttpRequestWithRegexLiteralOperation::instance(),
            $input,
            $overrides
        );
    }

    public function httpResponseCode(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): HttpResponseCodeOutput
    {
        /** @var HttpResponseCodeOutput */
        return $this->executeOperation(
            HttpResponseCodeOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function httpStringPayload(
        StringPayloadInput $input,
        ?RequestOverrideConfig $overrides = null
    ): StringPayloadInput
    {
        /** @var StringPayloadInput */
        return $this->executeOperation(
            HttpStringPayloadOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example ensures that query string bound request parameters are serialized in the body of responses if the
     * structure is used in both the request and response.
     */
    public function ignoreQueryParamsInResponse(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): IgnoreQueryParamsInResponseOutput
    {
        /** @var IgnoreQueryParamsInResponseOutput */
        return $this->executeOperation(
            IgnoreQueryParamsInResponseOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * The example tests how requests and responses are serialized when there is no input or output payload but there are
     * HTTP header bindings.
     */
    public function inputAndOutputWithHeaders(
        InputAndOutputWithHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): InputAndOutputWithHeadersIO
    {
        /** @var InputAndOutputWithHeadersIO */
        return $this->executeOperation(
            InputAndOutputWithHeadersOperation::instance(),
            $input,
            $overrides
        );
    }

    public function inputStream(InputStreamInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            InputStreamOperation::instance(),
            $input,
            $overrides
        );
    }

    public function inputStreamWithInitialRequest(
        InputStreamWithInitialRequestInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            InputStreamWithInitialRequestOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Blobs are base64 encoded
     */
    public function jsonBlobs(
        JsonBlobsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): JsonBlobsInputOutput
    {
        /** @var JsonBlobsInputOutput */
        return $this->executeOperation(
            JsonBlobsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function jsonEnums(
        JsonEnumsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): JsonEnumsInputOutput
    {
        /** @var JsonEnumsInputOutput */
        return $this->executeOperation(
            JsonEnumsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example serializes intEnums as top level properties, in lists, sets, and maps.
     */
    public function jsonIntEnums(
        JsonIntEnumsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): JsonIntEnumsInputOutput
    {
        /** @var JsonIntEnumsInputOutput */
        return $this->executeOperation(
            JsonIntEnumsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This test case serializes JSON lists for the following cases for both input and output: 1. Normal JSON lists. 2.
     * Normal JSON sets. 3. JSON lists of lists. 4. Lists of structures.
     */
    public function jsonLists(
        JsonListsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): JsonListsInputOutput
    {
        /** @var JsonListsInputOutput */
        return $this->executeOperation(
            JsonListsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * The example tests basic map serialization.
     */
    public function jsonMaps(JsonMapsInputOutput $input, ?RequestOverrideConfig $overrides = null): JsonMapsInputOutput
    {
        /** @var JsonMapsInputOutput */
        return $this->executeOperation(
            JsonMapsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This tests how timestamps are serialized, including using the default format of date-time and various
     * @timestampFormat trait values.
     */
    public function jsonTimestamps(
        JsonTimestampsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): JsonTimestampsInputOutput
    {
        /** @var JsonTimestampsInputOutput */
        return $this->executeOperation(
            JsonTimestampsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This operation uses unions for inputs and outputs.
     */
    public function jsonUnions(UnionInputOutput $input, ?RequestOverrideConfig $overrides = null): UnionInputOutput
    {
        /** @var UnionInputOutput */
        return $this->executeOperation(
            JsonUnionsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example ensures that mediaType strings are base64 encoded in headers.
     */
    public function mediaTypeHeader(
        MediaTypeHeaderInput $input,
        ?RequestOverrideConfig $overrides = null
    ): MediaTypeHeaderOutput
    {
        /** @var MediaTypeHeaderOutput */
        return $this->executeOperation(
            MediaTypeHeaderOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * The example tests how requests and responses are serialized when there's no request or response payload because the
     * operation has no input or output. While this should be rare, code generators must support this.
     */
    public function noInputAndNoOutput(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            NoInputAndNoOutputOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * The example tests how requests and responses are serialized when there's no request or response payload because the
     * operation has no input and the output is empty. While this should be rare, code generators must support this.
     */
    public function noInputAndOutput(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NoInputAndOutputOutput
    {
        /** @var NoInputAndOutputOutput */
        return $this->executeOperation(
            NoInputAndOutputOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * Null headers are not sent over the wire, empty headers are serialized to ""
     */
    public function nullAndEmptyHeadersClient(
        NullAndEmptyHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): NullAndEmptyHeadersIO
    {
        /** @var NullAndEmptyHeadersIO */
        return $this->executeOperation(
            NullAndEmptyHeadersClientOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Null headers are not sent over the wire, empty headers are serialized to ""
     */
    public function nullAndEmptyHeadersServer(
        NullAndEmptyHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): NullAndEmptyHeadersIO
    {
        /** @var NullAndEmptyHeadersIO */
        return $this->executeOperation(
            NullAndEmptyHeadersServerOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Omits null, but serializes empty string value.
     */
    public function omitsNullSerializesEmptyString(
        OmitsNullSerializesEmptyStringInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            OmitsNullSerializesEmptyStringOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Omits serializing empty lists. Because empty strings are serilized as `Foo=`, empty lists cannot also be serialized
     * as `Foo=` and instead must be omitted.
     */
    public function omitsSerializingEmptyLists(
        OmitsSerializingEmptyListsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            OmitsSerializingEmptyListsOperation::instance(),
            $input,
            $overrides
        );
    }

    public function outputStream(?Unit $input = null, ?RequestOverrideConfig $overrides = null): OutputStreamOutput
    {
        /** @var OutputStreamOutput */
        return $this->executeOperation(
            OutputStreamOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function outputStreamWithInitialResponse(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): OutputStreamWithInitialResponseOutput
    {
        /** @var OutputStreamWithInitialResponseOutput */
        return $this->executeOperation(
            OutputStreamWithInitialResponseOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This operation defines a union that uses jsonName on some members.
     */
    public function postUnionWithJsonName(
        PostUnionWithJsonNameInput $input,
        ?RequestOverrideConfig $overrides = null
    ): PostUnionWithJsonNameOutput
    {
        /** @var PostUnionWithJsonNameOutput */
        return $this->executeOperation(
            PostUnionWithJsonNameOperation::instance(),
            $input,
            $overrides
        );
    }

    public function putWithContentEncoding(
        PutWithContentEncodingInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            PutWithContentEncodingOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Automatically adds idempotency tokens.
     */
    public function queryIdempotencyTokenAutoFill(
        QueryIdempotencyTokenAutoFillInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            QueryIdempotencyTokenAutoFillOperation::instance(),
            $input,
            $overrides
        );
    }

    public function queryParamsAsStringListMap(
        QueryParamsAsStringListMapInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            QueryParamsAsStringListMapOperation::instance(),
            $input,
            $overrides
        );
    }

    public function queryPrecedence(QueryPrecedenceInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            QueryPrecedenceOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Recursive shapes
     */
    public function recursiveShapes(
        RecursiveShapesInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RecursiveShapesInputOutput
    {
        /** @var RecursiveShapesInputOutput */
        return $this->executeOperation(
            RecursiveShapesOperation::instance(),
            $input,
            $overrides
        );
    }

    public function responseCodeHttpFallback(
        ?ResponseCodeHttpFallbackInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): ResponseCodeHttpFallbackInputOutput
    {
        /** @var ResponseCodeHttpFallbackInputOutput */
        return $this->executeOperation(
            ResponseCodeHttpFallbackOperation::instance(),
            $input ?? new ResponseCodeHttpFallbackInputOutput(),
            $overrides
        );
    }

    public function responseCodeRequired(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): ResponseCodeRequiredOutput
    {
        /** @var ResponseCodeRequiredOutput */
        return $this->executeOperation(
            ResponseCodeRequiredOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function simpleScalarProperties(
        SimpleScalarPropertiesInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarPropertiesInputOutput
    {
        /** @var SimpleScalarPropertiesInputOutput */
        return $this->executeOperation(
            SimpleScalarPropertiesOperation::instance(),
            $input,
            $overrides
        );
    }

    public function sparseJsonLists(
        SparseJsonListsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): SparseJsonListsInputOutput
    {
        /** @var SparseJsonListsInputOutput */
        return $this->executeOperation(
            SparseJsonListsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example tests sparse map serialization.
     */
    public function sparseJsonMaps(
        SparseJsonMapsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): SparseJsonMapsInputOutput
    {
        /** @var SparseJsonMapsInputOutput */
        return $this->executeOperation(
            SparseJsonMapsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example operation serializes a structure in the HTTP body. It should ensure Content-Type: application/json is
     * used in all requests and that an "empty" body is an empty JSON document ({}).
     */
    public function testBodyStructure(
        TestBodyStructureInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): TestBodyStructureInputOutput
    {
        /** @var TestBodyStructureInputOutput */
        return $this->executeOperation(
            TestBodyStructureOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example GET operation has no input and serializes a request without a HTTP body. These tests are to ensure we do
     * not attach a body or related headers (Content-Length, Content-Type) to operations that semantically cannot produce an
     * HTTP body.
     */
    public function testGetNoInputNoPayload(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput
    {
        /** @var TestNoPayloadInputOutput */
        return $this->executeOperation(
            TestGetNoInputNoPayloadOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This example GET operation serializes a request without a modeled HTTP body. These tests are to ensure we do not
     * attach a body or related headers (Content-Length, Content-Type) to operations that semantically cannot produce an
     * HTTP body.
     */
    public function testGetNoPayload(
        TestNoPayloadInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput
    {
        /** @var TestNoPayloadInputOutput */
        return $this->executeOperation(
            TestGetNoPayloadOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example operation serializes a payload targeting a blob. The Blob shape is not structured content and we cannot
     * make assumptions about what data will be sent. This test ensures only a generic "Content-Type:
     * application/octet-stream" header is used, and that we are not treating an empty body as an empty JSON document.
     */
    public function testPayloadBlob(
        TestPayloadBlobInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): TestPayloadBlobInputOutput
    {
        /** @var TestPayloadBlobInputOutput */
        return $this->executeOperation(
            TestPayloadBlobOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example operation serializes a payload targeting a structure. This enforces the same requirements as
     * TestBodyStructure but with the body specified by the @httpPayload trait.
     */
    public function testPayloadStructure(
        TestPayloadStructureInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): TestPayloadStructureInputOutput
    {
        /** @var TestPayloadStructureInputOutput */
        return $this->executeOperation(
            TestPayloadStructureOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example POST operation has no input and serializes a request without a HTTP body. These tests are to ensure we
     * do not attach a body or related headers (Content-Type) to a POST operation with no modeled input.
     */
    public function testPostNoInputNoPayload(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput
    {
        /** @var TestNoPayloadInputOutput */
        return $this->executeOperation(
            TestPostNoInputNoPayloadOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This example POST operation serializes a request without a modeled HTTP body. These tests are to ensure we do not
     * attach a body or related headers (Content-Type) to a POST operation with no modeled payload.
     */
    public function testPostNoPayload(
        TestNoPayloadInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): TestNoPayloadInputOutput
    {
        /** @var TestNoPayloadInputOutput */
        return $this->executeOperation(
            TestPostNoPayloadOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This example tests how timestamp request and response headers are serialized.
     */
    public function timestampFormatHeaders(
        TimestampFormatHeadersIO $input,
        ?RequestOverrideConfig $overrides = null
    ): TimestampFormatHeadersIO
    {
        /** @var TimestampFormatHeadersIO */
        return $this->executeOperation(
            TimestampFormatHeadersOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This test is similar to NoInputAndNoOutput, but uses explicit Unit types.
     */
    public function unitInputAndOutput(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            UnitInputAndOutputOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }
}
