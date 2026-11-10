<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor;

use Smithy\Client\AbstractClient;
use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\RpcV2Cbor\Input\OperationWithDefaultsInput;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\EmptyInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\Float16Operation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\NoInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\OperationWithDefaultsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\OptionalInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RecursiveShapesOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborDenseMapsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborListsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborSparseMapsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\RpcV2CborUnionsOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Operation\SparseNullsOperationOperation;
use Smithy\ProtocolTests\RpcV2Cbor\Output\Float16Output;
use Smithy\ProtocolTests\RpcV2Cbor\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Output\OperationWithDefaultsOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\EmptyStructure;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RecursiveShapesInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborDenseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborListInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborSparseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\RpcV2CborUnionInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\SimpleScalarStructure;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\SimpleStructure;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\SparseNullsOperationInputOutput;
use Smithy\ProtocolTests\RpcV2Cbor\Shape\Unit;

/**
 * Client for the RpcV2Cbor service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class RpcV2CborClient extends AbstractClient implements RpcV2CborClientInterface
{
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(RpcV2CborService::instance()),
            $plugins
        );
    }

    public function emptyInputOutput(
        ?EmptyStructure $input = null,
        ?RequestOverrideConfig $overrides = null
    ): EmptyStructure
    {
        /** @var EmptyStructure */
        return $this->executeOperation(
            EmptyInputOutputOperation::instance(),
            $input ?? new EmptyStructure(),
            $overrides
        );
    }

    public function float16(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Float16Output
    {
        /** @var Float16Output */
        return $this->executeOperation(
            Float16Operation::instance(),
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

    public function noInputOutput(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit
    {
        /** @var Unit */
        return $this->executeOperation(
            NoInputOutputOperation::instance(),
            $input ?? new Unit(),
            $overrides
        );
    }

    public function operationWithDefaults(
        OperationWithDefaultsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): OperationWithDefaultsOutput
    {
        /** @var OperationWithDefaultsOutput */
        return $this->executeOperation(
            OperationWithDefaultsOperation::instance(),
            $input,
            $overrides
        );
    }

    public function optionalInputOutput(
        SimpleStructure $input,
        ?RequestOverrideConfig $overrides = null
    ): SimpleStructure
    {
        /** @var SimpleStructure */
        return $this->executeOperation(
            OptionalInputOutputOperation::instance(),
            $input,
            $overrides
        );
    }

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

    /**
     * The example tests basic map serialization.
     */
    public function rpcV2CborDenseMaps(
        RpcV2CborDenseMapsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborDenseMapsInputOutput
    {
        /** @var RpcV2CborDenseMapsInputOutput */
        return $this->executeOperation(
            RpcV2CborDenseMapsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This test case serializes JSON lists for the following cases for both input and output: 1. Normal lists. 2. Normal
     * sets. 3. Lists of lists. 4. Lists of structures.
     */
    public function rpcV2CborLists(
        RpcV2CborListInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborListInputOutput
    {
        /** @var RpcV2CborListInputOutput */
        return $this->executeOperation(
            RpcV2CborListsOperation::instance(),
            $input,
            $overrides
        );
    }

    public function rpcV2CborSparseMaps(
        RpcV2CborSparseMapsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborSparseMapsInputOutput
    {
        /** @var RpcV2CborSparseMapsInputOutput */
        return $this->executeOperation(
            RpcV2CborSparseMapsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This operation uses unions for inputs and outputs.
     */
    public function rpcV2CborUnions(
        RpcV2CborUnionInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborUnionInputOutput
    {
        /** @var RpcV2CborUnionInputOutput */
        return $this->executeOperation(
            RpcV2CborUnionsOperation::instance(),
            $input,
            $overrides
        );
    }

    public function simpleScalarProperties(
        SimpleScalarStructure $input,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarStructure
    {
        /** @var SimpleScalarStructure */
        return $this->executeOperation(
            SimpleScalarPropertiesOperation::instance(),
            $input,
            $overrides
        );
    }

    public function sparseNullsOperation(
        SparseNullsOperationInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): SparseNullsOperationInputOutput
    {
        /** @var SparseNullsOperationInputOutput */
        return $this->executeOperation(
            SparseNullsOperationOperation::instance(),
            $input,
            $overrides
        );
    }
}
