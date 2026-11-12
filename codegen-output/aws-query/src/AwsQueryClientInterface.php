<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery;

use Smithy\Client\ClientInterface;
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
 * Public contract for the AwsQuery service client.
 */
interface AwsQueryClientInterface extends ClientInterface
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

    /**
     * Flattened maps
     */
    public function flattenedXmlMap(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapOutput;

    /**
     * Flattened maps with @xmlName
     */
    public function flattenedXmlMapWithXmlName(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): FlattenedXmlMapWithXmlNameOutput;

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
     * An InvalidGreeting error. 3. A BadRequest error.
     */
    public function greetingWithErrors(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput;

    public function hostWithPathOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * The xmlName trait on the output structure is ignored in AWS Query. The wrapping element is always operation name +
     * "Response", and inside of that wrapper is another wrapper named operation name + "Result".
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
     * The example tests how requests and responses are serialized when there's no request or response payload because the
     * operation has no input or output. While this should be rare, code generators must support this.
     */
    public function noInputAndNoOutput(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * The example tests how requests and responses are serialized when there's no request payload or response members.
     * While this should be rare, code generators must support this.
     */
    public function noInputAndOutput(
        ?NoInputAndOutputInput $input = null,
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
     * This test serializes simple and complex maps.
     */
    public function queryMaps(QueryMapsInput $input, ?RequestOverrideConfig $overrides = null): Unit;

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

    public function xmlEmptyMaps(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlMapsOutput;

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function xmlEnums(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlEnumsOutput;

    /**
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function xmlIntEnums(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlIntEnumsOutput;

    /**
     * This test case serializes XML lists for the following cases for both input and output: 1. Normal XML lists. 2. Normal
     * XML sets. 3. XML lists of lists. 4. XML lists with @xmlName on its members 5. Flattened XML lists. 6. Flattened XML
     * lists with @xmlName. 7. Lists of structures.
     */
    public function xmlLists(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlListsOutput;

    /**
     * The example tests basic map serialization.
     */
    public function xmlMaps(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlMapsOutput;

    public function xmlMapsXmlName(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlMapsXmlNameOutput;

    public function xmlNamespaces(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlNamespacesOutput;

    /**
     * This tests how timestamps are serialized, including using the default format of date-time and various
     * @timestampFormat trait values.
     */
    public function xmlTimestamps(?Unit $input = null, ?RequestOverrideConfig $overrides = null): XmlTimestampsOutput;
}
