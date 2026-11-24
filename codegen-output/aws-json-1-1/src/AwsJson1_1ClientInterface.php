<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\AwsJson1_1\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\HostLabelInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\OperationWithOptionalInputOutputInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\AwsJson1_1\Output\ContentTypeParametersOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\DatetimeOffsetsOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsJson1_1\Output\OperationWithOptionalInputOutputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\JsonEnumsInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\JsonIntEnumsInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\KitchenSink;
use Smithy\ProtocolTests\AwsJson1_1\Shape\NullOperationInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\PutAndGetInlineDocumentsInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\SimpleScalarPropertiesInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\SparseNullsOperationInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\UnionInputOutput;
use Smithy\ProtocolTests\AwsJson1_1\Shape\Unit;

/**
 * Public contract for the AwsJson1_1 service client.
 */
interface AwsJson1_1ClientInterface extends ClientInterface
{
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

    public function emptyOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

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
     * An InvalidGreeting error. 3. A ComplexError error. Implementations must be able to successfully take a response and
     * properly deserialize successful and error responses.
     */
    public function greetingWithErrors(
        ?Unit $input = null,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput;

    public function hostWithPathOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

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
     * This operation uses unions for inputs and outputs.
     */
    public function jsonUnions(
        ?UnionInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): UnionInputOutput;

    public function kitchenSinkOperation(
        ?KitchenSink $input = null,
        ?RequestOverrideConfig $overrides = null
    ): KitchenSink;

    public function nullOperation(
        ?NullOperationInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NullOperationInputOutput;

    public function operationWithOptionalInputOutput(
        ?OperationWithOptionalInputOutputInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): OperationWithOptionalInputOutputOutput;

    /**
     * This example serializes an inline document as part of the payload.
     */
    public function putAndGetInlineDocuments(
        ?PutAndGetInlineDocumentsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): PutAndGetInlineDocumentsInputOutput;

    public function putWithContentEncoding(
        ?PutWithContentEncodingInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function simpleScalarProperties(
        ?SimpleScalarPropertiesInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarPropertiesInputOutput;

    public function sparseNullsOperation(
        ?SparseNullsOperationInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SparseNullsOperationInputOutput;
}
