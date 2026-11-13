<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\AwsJson1_0\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\EndpointWithHostLabelOperationInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\GreetingWithErrorsInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\JsonUnionsInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\SimpleScalarPropertiesInput;
use Smithy\ProtocolTests\AwsJson1_0\Output\ContentTypeParametersOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\EmptyInputAndEmptyOutputOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\JsonUnionsOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\NoInputAndOutputOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\SimpleScalarPropertiesOutput;
use Smithy\ProtocolTests\AwsJson1_0\Shape\Unit;

/**
 * Public contract for the AwsJson1_0 service client.
 */
interface AwsJson1_0ClientInterface extends ClientInterface
{
    /**
     * The example tests how servers must support requests containing a `Content-Type` header with parameters.
     */
    public function contentTypeParameters(
        ContentTypeParametersInput $input,
        ?RequestOverrideConfig $overrides = null
    ): ContentTypeParametersOutput;

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
        EndpointWithHostLabelOperationInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    /**
     * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
     * An InvalidGreeting error. 3. A ComplexError error. Implementations must be able to successfully take a response and
     * properly deserialize successful and error responses.
     */
    public function greetingWithErrors(
        GreetingWithErrorsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput;

    public function hostWithPathOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    /**
     * This operation uses unions for inputs and outputs.
     */
    public function jsonUnions(JsonUnionsInput $input, ?RequestOverrideConfig $overrides = null): JsonUnionsOutput;

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

    public function putWithContentEncoding(
        PutWithContentEncodingInput $input,
        ?RequestOverrideConfig $overrides = null
    ): Unit;

    public function queryIncompatibleOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    public function simpleScalarProperties(
        SimpleScalarPropertiesInput $input,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarPropertiesOutput;
}
