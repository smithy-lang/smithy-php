<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Json;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;

/**
 * Shape-ID registry for the RpcV2Json service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for the
 * class name when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class RpcV2JsonTypeRegistry implements TypeRegistryInterface
{
    use TypeRegistryDeserializeTrait;

    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'smithy.framework#ValidationException' => 'Smithy\\ProtocolTests\\RpcV2Json\\Exception\\ValidationException',
        'smithy.framework#ValidationExceptionField' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\ValidationExceptionField',
        'smithy.protocoltests.rpcv2Json#BigDecimalStructure' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\BigDecimalStructure',
        'smithy.protocoltests.rpcv2Json#BigIntegerStructure' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\BigIntegerStructure',
        'smithy.protocoltests.rpcv2Json#ClientOptionalDefaults' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\ClientOptionalDefaults',
        'smithy.protocoltests.rpcv2Json#ComplexError' => 'Smithy\\ProtocolTests\\RpcV2Json\\Exception\\ComplexErrorException',
        'smithy.protocoltests.rpcv2Json#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\ComplexNestedErrorData',
        'smithy.protocoltests.rpcv2Json#Defaults' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\Defaults',
        'smithy.protocoltests.rpcv2Json#EmptyStructure' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\EmptyStructure',
        'smithy.protocoltests.rpcv2Json#FractionalSecondsOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Output\\FractionalSecondsOutput',
        'smithy.protocoltests.rpcv2Json#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Output\\GreetingWithErrorsOutput',
        'smithy.protocoltests.rpcv2Json#InvalidGreeting' => 'Smithy\\ProtocolTests\\RpcV2Json\\Exception\\InvalidGreetingException',
        'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Input\\OperationWithDefaultsInput',
        'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Output\\OperationWithDefaultsOutput',
        'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RecursiveShapesInputOutput',
        'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RecursiveShapesInputOutputNested1',
        'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RecursiveShapesInputOutputNested2',
        'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RpcV2JsonDenseMapsInputOutput',
        'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RpcV2JsonListInputOutput',
        'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RpcV2JsonSparseMapsInputOutput',
        'smithy.protocoltests.rpcv2Json#SimpleScalarStructure' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\SimpleScalarStructure',
        'smithy.protocoltests.rpcv2Json#SimpleStructure' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\SimpleStructure',
        'smithy.protocoltests.rpcv2Json#SparseNullsOperationInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\SparseNullsOperationInputOutput',
        'smithy.protocoltests.rpcv2Json#StructureListMember' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\StructureListMember',
        'smithy.protocoltests.rpcv2Json#TestEnum' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\TestEnum',
        'smithy.protocoltests.rpcv2Json#TestIntEnum' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\TestIntEnum',
        'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\TimestampFormatIgnoredIO',
        'smithy.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\FooEnum',
        'smithy.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\GreetingStruct',
        'smithy.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\RpcV2Json\\Exception\\ValidationException' => 'smithy.framework#ValidationException',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\ValidationExceptionField' => 'smithy.framework#ValidationExceptionField',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\BigDecimalStructure' => 'smithy.protocoltests.rpcv2Json#BigDecimalStructure',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\BigIntegerStructure' => 'smithy.protocoltests.rpcv2Json#BigIntegerStructure',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\ClientOptionalDefaults' => 'smithy.protocoltests.rpcv2Json#ClientOptionalDefaults',
        'Smithy\\ProtocolTests\\RpcV2Json\\Exception\\ComplexErrorException' => 'smithy.protocoltests.rpcv2Json#ComplexError',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\ComplexNestedErrorData' => 'smithy.protocoltests.rpcv2Json#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\Defaults' => 'smithy.protocoltests.rpcv2Json#Defaults',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\EmptyStructure' => 'smithy.protocoltests.rpcv2Json#EmptyStructure',
        'Smithy\\ProtocolTests\\RpcV2Json\\Output\\FractionalSecondsOutput' => 'smithy.protocoltests.rpcv2Json#FractionalSecondsOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Output\\GreetingWithErrorsOutput' => 'smithy.protocoltests.rpcv2Json#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Exception\\InvalidGreetingException' => 'smithy.protocoltests.rpcv2Json#InvalidGreeting',
        'Smithy\\ProtocolTests\\RpcV2Json\\Input\\OperationWithDefaultsInput' => 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsInput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Output\\OperationWithDefaultsOutput' => 'smithy.protocoltests.rpcv2Json#OperationWithDefaultsOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RecursiveShapesInputOutput' => 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RecursiveShapesInputOutputNested1' => 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested1',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RecursiveShapesInputOutputNested2' => 'smithy.protocoltests.rpcv2Json#RecursiveShapesInputOutputNested2',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RpcV2JsonDenseMapsInputOutput' => 'smithy.protocoltests.rpcv2Json#RpcV2JsonDenseMapsInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RpcV2JsonListInputOutput' => 'smithy.protocoltests.rpcv2Json#RpcV2JsonListInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\RpcV2JsonSparseMapsInputOutput' => 'smithy.protocoltests.rpcv2Json#RpcV2JsonSparseMapsInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\SimpleScalarStructure' => 'smithy.protocoltests.rpcv2Json#SimpleScalarStructure',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\SimpleStructure' => 'smithy.protocoltests.rpcv2Json#SimpleStructure',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\SparseNullsOperationInputOutput' => 'smithy.protocoltests.rpcv2Json#SparseNullsOperationInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\StructureListMember' => 'smithy.protocoltests.rpcv2Json#StructureListMember',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\TestEnum' => 'smithy.protocoltests.rpcv2Json#TestEnum',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\TestIntEnum' => 'smithy.protocoltests.rpcv2Json#TestIntEnum',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\TimestampFormatIgnoredIO' => 'smithy.protocoltests.rpcv2Json#TimestampFormatIgnoredIO',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\FooEnum' => 'smithy.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\GreetingStruct' => 'smithy.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\RpcV2Json\\Shape\\IntegerEnum' => 'smithy.protocoltests.shared#IntegerEnum'
    ];

    /**
     * Return the generated class name for a Smithy shape ID,
     * or null when the ID is unknown to this service.
     */
    public function classFor(string $shapeId): ?string
    {
        return self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
    }

    /**
     * Return the Smithy shape ID for a generated class.
     * Returns null when this codegen run did not produce the class.
     *
     * @param class-string $class
     */
    public function shapeIdFor(string $class): ?string
    {
        return self::SHAPE_IDS_BY_CLASS[$class] ?? null;
    }

    /**
     * Resolve a shape ID to its `Schema` object. Uses the generated
     * class's lazy `schema()` static, so schemas are only built the
     * first time they are accessed.
     *
     * Returns null when the shape ID is not registered.
     */
    public function schemaFor(string $shapeId): ?Schema
    {
        $class = self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
        if ($class === null) {
            return null;
        }
        return \method_exists($class, 'schema') ? $class::schema() : null;
    }

    /**
     * Iterate every registered (shape ID, class-string) pair. Useful
     * for building higher-level indexes on top of the registry (a
     * category-scoped subset, a namespace filter, etc.).
     *
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return self::CLASSES_BY_SHAPE_ID;
    }
}
