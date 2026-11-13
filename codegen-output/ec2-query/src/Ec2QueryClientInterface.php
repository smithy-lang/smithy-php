<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\Ec2Query\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\Ec2Query\Input\HostLabelInput;
use Smithy\ProtocolTests\Ec2Query\Input\NestedStructuresInput;
use Smithy\ProtocolTests\Ec2Query\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryIdempotencyTokenAutoFillInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryListsInput;
use Smithy\ProtocolTests\Ec2Query\Input\QueryTimestampsInput;
use Smithy\ProtocolTests\Ec2Query\Input\SimpleInputParamsInput;
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
 * Public contract for the Ec2Query service client.
 */
interface Ec2QueryClientInterface extends ClientInterface
{
    public function datetimeOffsets(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): DatetimeOffsetsOutput;

    /**
     * The example tests how requests and responses are serialized when there's no request or response members. While this
     * should be rare, code generators must support this.
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
     * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
     * An InvalidGreeting error. 3. A BadRequest error.
     */
    public function greetingWithErrors(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput;

    public function hostWithPathOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * The xmlName trait on the output structure is ignored in AWS Query. The wrapping element is always operation name +
     * "Response".
     */
    public function ignoresWrappingXmlName(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): IgnoresWrappingXmlNameOutput;

    /**
     * This test serializes nested and recursive structure members.
     */
    public function nestedStructures(NestedStructuresInput $input, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * The example tests how requests and responses are serialized when there's no request payload or response members.
     * While this should be rare, code generators must support this.
     */
    public function noInputAndOutput(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NoInputAndOutputOutput;

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

    /**
     * This test serializes simple and complex lists.
     */
    public function queryLists(QueryListsInput $input, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * This test serializes timestamps. 1. Timestamps are serialized as RFC 3339 date-time values by default. 2. A
     * timestampFormat trait on a member changes the format. 3. A timestampFormat trait on the shape targeted by the member
     * changes the format.
     */
    public function queryTimestamps(QueryTimestampsInput $input, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * Recursive shapes
     */
    public function recursiveXmlShapes(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): RecursiveXmlShapesOutput;

    /**
     * This test serializes strings, numbers, and boolean values.
     */
    public function simpleInputParams(SimpleInputParamsInput $input, ?RequestOverrideConfig $overrides = null): Unit;

    public function simpleScalarXmlProperties(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarXmlPropertiesOutput;

    /**
     * Blobs are base64 encoded
     */
    public function xmlBlobs(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlBlobsOutput;

    public function xmlEmptyBlobs(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlBlobsOutput;

    public function xmlEmptyLists(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlListsOutput;

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function xmlEnums(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlEnumsOutput;

    /**
     * This example serializes intEnums as top level properties, in lists, sets, and maps.
     */
    public function xmlIntEnums(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlIntEnumsOutput;

    /**
     * This test case serializes XML lists for the following cases for both input and output: 1. Normal XML lists. 2. Normal
     * XML sets. 3. XML lists of lists. 4. XML lists with @xmlName on its members 5. Flattened XML lists. 6. Flattened XML
     * lists with @xmlName. 7. Flattened XML lists with @xmlNamespace. 8. Lists of structures.
     */
    public function xmlLists(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlListsOutput;

    public function xmlNamespaces(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlNamespacesOutput;

    /**
     * This tests how timestamps are serialized, including using the default format of date-time and various
     * @timestampFormat trait values.
     */
    public function xmlTimestamps(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlTimestampsOutput;
}
