<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1;

use Smithy\Client\AbstractClient;
use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\AwsJson1_1\Input\ContentTypeParametersInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\HostLabelInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\OperationWithOptionalInputOutputInput;
use Smithy\ProtocolTests\AwsJson1_1\Input\PutWithContentEncodingInput;
use Smithy\ProtocolTests\AwsJson1_1\Operation\ContentTypeParametersOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\EmptyOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\JsonEnumsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\JsonIntEnumsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\JsonUnionsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\KitchenSinkOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\NullOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\OperationWithOptionalInputOutputOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\PutAndGetInlineDocumentsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\SparseNullsOperationOperation;
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
 * Client for the AwsJson1_1 service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class AwsJson1_1Client extends AbstractClient implements AwsJson1_1ClientInterface
{
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(AwsJson1_1Service::instance()),
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

    public function emptyOperation(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            EmptyOperationOperation::instance(),
            $input ?? new Unit(),
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
     * An InvalidGreeting error. 3. A ComplexError error. Implementations must be able to successfully take a response and
     * properly deserialize successful and error responses.
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
     * This example serializes enums as top level properties, in lists, sets, and maps.
     */
    public function jsonEnums(
        ?JsonEnumsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonEnumsInputOutput
    {
        /** @var JsonEnumsInputOutput */
        return $this->executeOperation(
            JsonEnumsOperation::instance(),
            $input ?? new JsonEnumsInputOutput(),
            $overrides
        );
    }

    /**
     * This example serializes intEnums as top level properties, in lists, sets, and maps.
     */
    public function jsonIntEnums(
        ?JsonIntEnumsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): JsonIntEnumsInputOutput
    {
        /** @var JsonIntEnumsInputOutput */
        return $this->executeOperation(
            JsonIntEnumsOperation::instance(),
            $input ?? new JsonIntEnumsInputOutput(),
            $overrides
        );
    }

    /**
     * This operation uses unions for inputs and outputs.
     */
    public function jsonUnions(
        ?UnionInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): UnionInputOutput
    {
        /** @var UnionInputOutput */
        return $this->executeOperation(
            JsonUnionsOperation::instance(),
            $input ?? new UnionInputOutput(),
            $overrides
        );
    }

    public function kitchenSinkOperation(
        ?KitchenSink $input = null,
        ?RequestOverrideConfig $overrides = null
    ): KitchenSink
    {
        /** @var KitchenSink */
        return $this->executeOperation(
            KitchenSinkOperationOperation::instance(),
            $input ?? new KitchenSink(),
            $overrides
        );
    }

    public function nullOperation(
        ?NullOperationInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): NullOperationInputOutput
    {
        /** @var NullOperationInputOutput */
        return $this->executeOperation(
            NullOperationOperation::instance(),
            $input ?? new NullOperationInputOutput(),
            $overrides
        );
    }

    public function operationWithOptionalInputOutput(
        ?OperationWithOptionalInputOutputInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): OperationWithOptionalInputOutputOutput
    {
        /** @var OperationWithOptionalInputOutputOutput */
        return $this->executeOperation(
            OperationWithOptionalInputOutputOperation::instance(),
            $input ?? new OperationWithOptionalInputOutputInput(),
            $overrides
        );
    }

    /**
     * This example serializes an inline document as part of the payload.
     */
    public function putAndGetInlineDocuments(
        ?PutAndGetInlineDocumentsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): PutAndGetInlineDocumentsInputOutput
    {
        /** @var PutAndGetInlineDocumentsInputOutput */
        return $this->executeOperation(
            PutAndGetInlineDocumentsOperation::instance(),
            $input ?? new PutAndGetInlineDocumentsInputOutput(),
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

    public function simpleScalarProperties(
        ?SimpleScalarPropertiesInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarPropertiesInputOutput
    {
        /** @var SimpleScalarPropertiesInputOutput */
        return $this->executeOperation(
            SimpleScalarPropertiesOperation::instance(),
            $input ?? new SimpleScalarPropertiesInputOutput(),
            $overrides
        );
    }

    public function sparseNullsOperation(
        ?SparseNullsOperationInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SparseNullsOperationInputOutput
    {
        /** @var SparseNullsOperationInputOutput */
        return $this->executeOperation(
            SparseNullsOperationOperation::instance(),
            $input ?? new SparseNullsOperationInputOutput(),
            $overrides
        );
    }
}
