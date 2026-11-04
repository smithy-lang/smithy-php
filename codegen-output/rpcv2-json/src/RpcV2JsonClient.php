<?php

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json;

use Smithy\Client\AbstractClient;
use Smithy\Client\ClientConfig;
use Smithy\Client\Plugin\PluginCollection;
use Smithy\Client\RequestOverrideConfig;
use Smithy\ProtocolTests\RpcV2Json\Input\OperationWithDefaultsInput;
use Smithy\ProtocolTests\RpcV2Json\Operation\BigDecimalOperationOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\BigIntegerOperationOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\EmptyInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\NoInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\OperationWithDefaultsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\OptionalInputOutputOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RecursiveShapesOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RpcV2JsonDenseMapsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RpcV2JsonListsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\RpcV2JsonSparseMapsOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\SparseNullsOperationOperation;
use Smithy\ProtocolTests\RpcV2Json\Operation\TimestampFormatIgnoredOperation;
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
 * Client for the RpcV2Json service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around AbstractClient::executeOperation().
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class RpcV2JsonClient extends AbstractClient
{
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        parent::__construct(
            $config->withService(RpcV2JsonService::instance()),
            $plugins
        );
    }

    public function bigDecimalOperation(
        BigDecimalStructure $input,
        ?RequestOverrideConfig $overrides = null
    ): BigDecimalStructure
    {
        /** @var BigDecimalStructure */
        return $this->executeOperation(
            BigDecimalOperationOperation::instance(),
            $input,
            $overrides
        );
    }

    public function bigIntegerOperation(
        BigIntegerStructure $input,
        ?RequestOverrideConfig $overrides = null
    ): BigIntegerStructure
    {
        /** @var BigIntegerStructure */
        return $this->executeOperation(
            BigIntegerOperationOperation::instance(),
            $input,
            $overrides
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
    public function rpcV2JsonDenseMaps(
        RpcV2JsonDenseMapsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2JsonDenseMapsInputOutput
    {
        /** @var RpcV2JsonDenseMapsInputOutput */
        return $this->executeOperation(
            RpcV2JsonDenseMapsOperation::instance(),
            $input,
            $overrides
        );
    }

    /**
     * This test case serializes JSON lists for the following cases for both input and output: 1. Normal lists. 2. Normal
     * sets. 3. Lists of lists. 4. Lists of structures.
     */
    public function rpcV2JsonLists(
        RpcV2JsonListInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2JsonListInputOutput
    {
        /** @var RpcV2JsonListInputOutput */
        return $this->executeOperation(
            RpcV2JsonListsOperation::instance(),
            $input,
            $overrides
        );
    }

    public function rpcV2JsonSparseMaps(
        RpcV2JsonSparseMapsInputOutput $input,
        ?RequestOverrideConfig $overrides = null
    ): RpcV2JsonSparseMapsInputOutput
    {
        /** @var RpcV2JsonSparseMapsInputOutput */
        return $this->executeOperation(
            RpcV2JsonSparseMapsOperation::instance(),
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

    /**
     * This operation tests that the rpcv2Json protocol always uses epoch-seconds for timestamp serialization, regardless of
     * the `timestampFormat` trait. Members targeting `DateTime` (date-time), `HttpDate` (http-date), and `EpochSeconds`
     * (epoch-seconds) must all serialize as JSON numbers.
     */
    public function timestampFormatIgnored(
        TimestampFormatIgnoredIO $input,
        ?RequestOverrideConfig $overrides = null
    ): TimestampFormatIgnoredIO
    {
        /** @var TimestampFormatIgnoredIO */
        return $this->executeOperation(
            TimestampFormatIgnoredOperation::instance(),
            $input,
            $overrides
        );
    }
}
