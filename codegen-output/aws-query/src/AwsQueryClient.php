<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery;

use Smithy\Client\AbstractClient;
use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\RequestOverrideConfig;
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
use Smithy\ProtocolTests\AwsQuery\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FlattenedXmlMapOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FlattenedXmlMapWithXmlNameOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FlattenedXmlMapWithXmlNamespaceOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\IgnoresWrappingXmlNameOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\NestedStructuresOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryIdempotencyTokenAutoFillOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryListsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryMapsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryTimestampsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\RecursiveXmlShapesOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\SimpleInputParamsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\SimpleScalarXmlPropertiesOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlBlobsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEmptyBlobsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEmptyListsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEmptyMapsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEnumsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlIntEnumsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlListsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlMapsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlMapsXmlNameOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlNamespacesOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlTimestampsOperation;
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
use Smithy\ProtocolTests\AwsQuery\Shape\Unit;

/**
 * Client for the AwsQuery service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 *
 * A query service that sends query requests and XML responses.
 */
final class AwsQueryClient extends AbstractClient implements AwsQueryClientInterface
{
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(AwsQueryService::instance()),
            $plugins
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
     * The example tests how requests and responses are serialized when there's no request or response members. While this
     * should be rare, code generators must support this.
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

    /**
     * Flattened maps
     */
    public function flattenedXmlMap(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapOutput
    {
        /** @var FlattenedXmlMapOutput */
        return $this->executeOperation(
            FlattenedXmlMapOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * Flattened maps with @xmlName
     */
    public function flattenedXmlMapWithXmlName(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapWithXmlNameOutput
    {
        /** @var FlattenedXmlMapWithXmlNameOutput */
        return $this->executeOperation(
            FlattenedXmlMapWithXmlNameOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * Flattened maps with @xmlNamespace and @xmlName
     */
    public function flattenedXmlMapWithXmlNamespace(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapWithXmlNamespaceOutput
    {
        /** @var FlattenedXmlMapWithXmlNamespaceOutput */
        return $this->executeOperation(
            FlattenedXmlMapWithXmlNamespaceOperation::instance(),
            $input ?? new Unit(),
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
     * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
     * An InvalidGreeting error. 3. A BadRequest error.
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
     * The xmlName trait on the output structure is ignored in AWS Query. The wrapping element is always operation name +
     * "Response", and inside of that wrapper is another wrapper named operation name + "Result".
     */
    public function ignoresWrappingXmlName(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): IgnoresWrappingXmlNameOutput
    {
        /** @var IgnoresWrappingXmlNameOutput */
        return $this->executeOperation(
            IgnoresWrappingXmlNameOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This test serializes nested and recursive structure members.
     */
    public function nestedStructures(NestedStructuresInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            NestedStructuresOperation::instance(),
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
     * The example tests how requests and responses are serialized when there's no request payload or response members.
     * While this should be rare, code generators must support this.
     */
    public function noInputAndOutput(
        ?NoInputAndOutputInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NoInputAndOutputOutput
    {
        /** @var NoInputAndOutputOutput */
        return $this->executeOperation(
            NoInputAndOutputOperation::instance(),
            $input ?? new NoInputAndOutputInput(),
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

    /**
     * This test serializes simple and complex lists.
     */
    public function queryLists(QueryListsInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            QueryListsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This test serializes simple and complex maps.
     */
    public function queryMaps(QueryMapsInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            QueryMapsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This test serializes timestamps. 1. Timestamps are serialized as RFC 3339 date-time values by default. 2. A
     * timestampFormat trait on a member changes the format. 3. A timestampFormat trait on the shape targeted by the member
     * changes the format.
     */
    public function queryTimestamps(QueryTimestampsInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            QueryTimestampsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * Recursive shapes
     */
    public function recursiveXmlShapes(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): RecursiveXmlShapesOutput
    {
        /** @var RecursiveXmlShapesOutput */
        return $this->executeOperation(
            RecursiveXmlShapesOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This test serializes strings, numbers, and boolean values.
     */
    public function simpleInputParams(SimpleInputParamsInput $input, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            SimpleInputParamsOperation::instance(),
            $input,
            $overrides
        );
    }

    public function simpleScalarXmlProperties(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarXmlPropertiesOutput
    {
        /** @var SimpleScalarXmlPropertiesOutput */
        return $this->executeOperation(
            SimpleScalarXmlPropertiesOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * Blobs are base64 encoded
     */
    public function xmlBlobs(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlBlobsOutput
    {
        /** @var XmlBlobsOutput */
        return $this->executeOperation(
            XmlBlobsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function xmlEmptyBlobs(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlBlobsOutput
    {
        /** @var XmlBlobsOutput */
        return $this->executeOperation(
            XmlEmptyBlobsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function xmlEmptyLists(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlListsOutput
    {
        /** @var XmlListsOutput */
        return $this->executeOperation(
            XmlEmptyListsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function xmlEmptyMaps(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlMapsOutput
    {
        /** @var XmlMapsOutput */
        return $this->executeOperation(
            XmlEmptyMapsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function xmlEnums(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlEnumsOutput
    {
        /** @var XmlEnumsOutput */
        return $this->executeOperation(
            XmlEnumsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function xmlIntEnums(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlIntEnumsOutput
    {
        /** @var XmlIntEnumsOutput */
        return $this->executeOperation(
            XmlIntEnumsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This test case serializes XML lists for the following cases for both input and output: 1. Normal XML lists. 2. Normal
     * XML sets. 3. XML lists of lists. 4. XML lists with @xmlName on its members 5. Flattened XML lists. 6. Flattened XML
     * lists with @xmlName. 7. Lists of structures.
     */
    public function xmlLists(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlListsOutput
    {
        /** @var XmlListsOutput */
        return $this->executeOperation(
            XmlListsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * The example tests basic map serialization.
     */
    public function xmlMaps(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlMapsOutput
    {
        /** @var XmlMapsOutput */
        return $this->executeOperation(
            XmlMapsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function xmlMapsXmlName(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlMapsXmlNameOutput
    {
        /** @var XmlMapsXmlNameOutput */
        return $this->executeOperation(
            XmlMapsXmlNameOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function xmlNamespaces(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlNamespacesOutput
    {
        /** @var XmlNamespacesOutput */
        return $this->executeOperation(
            XmlNamespacesOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    /**
     * This tests how timestamps are serialized, including using the default format of date-time and various
     * @timestampFormat trait values.
     */
    public function xmlTimestamps(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlTimestampsOutput
    {
        /** @var XmlTimestampsOutput */
        return $this->executeOperation(
            XmlTimestampsOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }
}
