<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query;

use Smithy\Client\AbstractClient;
use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\Ec2Query\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\Ec2Query\Input\HostLabelInput;
use Smithy\ProtocolTests\Ec2Query\Input\NestedStructuresInput;
use Smithy\ProtocolTests\Ec2Query\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryIdempotencyTokenAutoFillInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryListsInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryTimestampsInput;
use Smithy\ProtocolTests\Ec2Query\Input\SimpleInputParamsInput;
use Smithy\ProtocolTests\Ec2Query\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\IgnoresWrappingXmlNameOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\NestedStructuresOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\QueryIdempotencyTokenAutoFillOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\QueryListsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\QueryTimestampsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\RecursiveXmlShapesOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\SimpleInputParamsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\SimpleScalarXmlPropertiesOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlBlobsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlEmptyBlobsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlEmptyListsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlEnumsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlIntEnumsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlListsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlNamespacesOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlTimestampsOperation;
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
use Smithy\ProtocolTests\Ec2Query\Shape\Unit;

/**
 * Client for the Ec2Query service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 *
 * An EC2 query service that sends query requests and XML responses.
 */
final class Ec2QueryClient extends AbstractClient implements Ec2QueryClientInterface
{
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(Ec2QueryService::instance()),
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
     * "Response".
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
     * The example tests how requests and responses are serialized when there's no request payload or response members.
     * While this should be rare, code generators must support this.
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
     * This example serializes intEnums as top level properties, in lists, sets, and maps.
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
     * lists with @xmlName. 7. Flattened XML lists with @xmlNamespace. 8. Lists of structures.
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
