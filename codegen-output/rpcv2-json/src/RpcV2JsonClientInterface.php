<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json;

use Smithy\Client\ClientInterface;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\RpcV2Json\Input\OperationWithDefaultsInput;
use Smithy\ProtocolTests\RpcV2Json\Output\FractionalSecondsOutput;
use Smithy\ProtocolTests\RpcV2Json\Output\GreetingWithErrorsOutput;
use Smithy\ProtocolTests\RpcV2Json\Output\OperationWithDefaultsOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\BigDecimalStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\BigIntegerStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\EmptyStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\RecursiveShapesInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\RpcV2JsonDenseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\RpcV2JsonListInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\RpcV2JsonSparseMapsInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\SimpleScalarStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\SimpleStructure;
use Smithy\ProtocolTests\RpcV2Json\Shape\SparseNullsOperationInputOutput;
use Smithy\ProtocolTests\RpcV2Json\Shape\TimestampFormatIgnoredIO;
use Smithy\ProtocolTests\RpcV2Json\Shape\Unit;

/**
 * Public contract for the RpcV2Json service client.
 */
interface RpcV2JsonClientInterface extends ClientInterface
{
    public function bigDecimalOperation(
        ?BigDecimalStructure $input = null,
        ?RequestOverrideConfig $overrides = null
    ): BigDecimalStructure;

    public function bigIntegerOperation(
        ?BigIntegerStructure $input = null,
        ?RequestOverrideConfig $overrides = null
    ): BigIntegerStructure;

    public function emptyInputOutput(
        ?EmptyStructure $input = null,
        ?RequestOverrideConfig $overrides = null
    ): EmptyStructure;

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
        ?OperationWithDefaultsInput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): OperationWithDefaultsOutput;

    public function optionalInputOutput(
        ?SimpleStructure $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleStructure;

    public function recursiveShapes(
        ?RecursiveShapesInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): RecursiveShapesInputOutput;

    /**
     * The example tests basic map serialization.
     */
    public function rpcV2JsonDenseMaps(
        ?RpcV2JsonDenseMapsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2JsonDenseMapsInputOutput;

    /**
     * This test case serializes JSON lists for the following cases for both input and output: 1. Normal lists. 2. Normal
     * sets. 3. Lists of lists. 4. Lists of structures.
     */
    public function rpcV2JsonLists(
        ?RpcV2JsonListInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2JsonListInputOutput;

    public function rpcV2JsonSparseMaps(
        ?RpcV2JsonSparseMapsInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2JsonSparseMapsInputOutput;

    public function simpleScalarProperties(
        ?SimpleScalarStructure $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SimpleScalarStructure;

    public function sparseNullsOperation(
        ?SparseNullsOperationInputOutput $input = null,
        ?RequestOverrideConfig $overrides = null
    ): SparseNullsOperationInputOutput;

    /**
     * This operation tests that the rpcv2Json protocol always uses epoch-seconds for timestamp serialization, regardless of
     * the `timestampFormat` trait. Members targeting `DateTime` (date-time), `HttpDate` (http-date), and `EpochSeconds`
     * (epoch-seconds) must all serialize as JSON numbers.
     */
    public function timestampFormatIgnored(
        ?TimestampFormatIgnoredIO $input = null,
        ?RequestOverrideConfig $overrides = null
    ): TimestampFormatIgnoredIO;
}
