<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\RpcV2Cbor\Input\OperationWithDefaultsInput;
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
 * Public contract for the RpcV2Cbor service client.
 */
interface RpcV2CborClientInterface extends ClientInterface
{
    public function emptyInputOutput(
        ?EmptyStructure $input = null,
        ?RequestOverrideConfig $overrides = null
    ): EmptyStructure;

    public function float16(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Float16Output;

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

    public function noInputOutput(?Unit $input = null, ?RequestOverrideConfig $overrides = null): Unit;

    public function operationWithDefaults(
        OperationWithDefaultsInput $input,
        ?RequestOverrideConfig $overrides = null
    ): OperationWithDefaultsOutput;

    public function optionalInputOutput(
        SimpleStructure $input,
        ?RequestOverrideConfig $overrides = null
    ): SimpleStructure;

    public function recursiveShapes(
        RecursiveShapesInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RecursiveShapesInputOutput;

    /**
     * The example tests basic map serialization.
     */
    public function rpcV2CborDenseMaps(
        RpcV2CborDenseMapsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborDenseMapsInputOutput;

    /**
     * This test case serializes JSON lists for the following cases for both input and output: 1. Normal lists. 2. Normal
     * sets. 3. Lists of lists. 4. Lists of structures.
     */
    public function rpcV2CborLists(
        RpcV2CborListInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborListInputOutput;

    public function rpcV2CborSparseMaps(
        RpcV2CborSparseMapsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborSparseMapsInputOutput;

    /**
     * This operation uses unions for inputs and outputs.
     */
    public function rpcV2CborUnions(
        RpcV2CborUnionInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2CborUnionInputOutput;

    public function simpleScalarProperties(
        SimpleScalarStructure $input,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarStructure;

    public function sparseNullsOperation(
        SparseNullsOperationInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): SparseNullsOperationInputOutput;
}
