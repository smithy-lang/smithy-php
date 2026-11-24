<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0;

use Smithy\Client\AbstractClient;
use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\AwsJson1_0\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\EmptyInputAndEmptyOutputInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\EndpointWithHostLabelOperationInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\GreetingWithErrorsInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\JsonUnionsInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\AwsJson1_0\Input\SimpleScalarPropertiesInput;
use Smithy\ProtocolTests\AwsJson1_0\Operation\ContentTypeParametersOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\JsonUnionsOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\QueryIncompatibleOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\AwsJson1_0\Output\ContentTypeParametersOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\EmptyInputAndEmptyOutputOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\JsonUnionsOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\NoInputAndOutputOutput;
use Smithy\ProtocolTests\AwsJson1_0\Output\SimpleScalarPropertiesOutput;
use Smithy\ProtocolTests\AwsJson1_0\Shape\Unit;

/**
 * Client for the AwsJson1_0 service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class AwsJson1_0Client extends AbstractClient implements AwsJson1_0ClientInterface
{
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(AwsJson1_0Service::instance()),
            $plugins
        );
    }

    /**
     * The example tests how servers must support requests containing a `Content-Type` header with parameters.
     */
    public function contentTypeParameters(
        ?ContentTypeParametersInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): ContentTypeParametersOutput
    {
        /** @var ContentTypeParametersOutput */
        return $this->executeOperation(
            ContentTypeParametersOperation::instance(),
            $input ?? new ContentTypeParametersInput(),
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
        EndpointWithHostLabelOperationInput $input,
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
     * This operation has three possible return values: 1. A successful response in the form of GreetingWithErrorsOutput 2.
     * An InvalidGreeting error. 3. A ComplexError error. Implementations must be able to successfully take a response and
     * properly deserialize successful and error responses.
     */
    public function greetingWithErrors(
        ?GreetingWithErrorsInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): GreetingWithErrorsOutput
    {
        /** @var GreetingWithErrorsOutput */
        return $this->executeOperation(
            GreetingWithErrorsOperation::instance(),
            $input ?? new GreetingWithErrorsInput(),
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
     * This operation uses unions for inputs and outputs.
     */
    public function jsonUnions(
        ?JsonUnionsInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonUnionsOutput
    {
        /** @var JsonUnionsOutput */
        return $this->executeOperation(
            JsonUnionsOperation::instance(),
            $input ?? new JsonUnionsInput(),
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

    public function putWithContentEncoding(
        ?PutWithContentEncodingInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            PutWithContentEncodingOperation::instance(),
            $input ?? new PutWithContentEncodingInput(),
            $overrides
        );
    }

    public function queryIncompatibleOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            QueryIncompatibleOperationOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function simpleScalarProperties(
        ?SimpleScalarPropertiesInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarPropertiesOutput
    {
        /** @var SimpleScalarPropertiesOutput */
        return $this->executeOperation(
            SimpleScalarPropertiesOperation::instance(),
            $input ?? new SimpleScalarPropertiesInput(),
            $overrides
        );
    }
}
