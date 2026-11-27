<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RpcV2Cbor;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;

/**
 * Shape-ID registry for the RpcV2Cbor service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for the
 * class name when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class RpcV2CborTypeRegistry implements TypeRegistryInterface
{
    use TypeRegistryDeserializeTrait;

    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'smithy.framework#ValidationException' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Exception\\ValidationException',
        'smithy.framework#ValidationExceptionField' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\ValidationExceptionField',
        'smithy.protocoltests.rpcv2Cbor#ClientOptionalDefaults' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\ClientOptionalDefaults',
        'smithy.protocoltests.rpcv2Cbor#ComplexError' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Exception\\ComplexErrorException',
        'smithy.protocoltests.rpcv2Cbor#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\ComplexNestedErrorData',
        'smithy.protocoltests.rpcv2Cbor#Defaults' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\Defaults',
        'smithy.protocoltests.rpcv2Cbor#EmptyStructure' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\EmptyStructure',
        'smithy.protocoltests.rpcv2Cbor#Float16Output' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\Float16Output',
        'smithy.protocoltests.rpcv2Cbor#FractionalSecondsOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\FractionalSecondsOutput',
        'smithy.protocoltests.rpcv2Cbor#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\GreetingWithErrorsOutput',
        'smithy.protocoltests.rpcv2Cbor#InvalidGreeting' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Exception\\InvalidGreetingException',
        'smithy.protocoltests.rpcv2Cbor#OperationWithDefaultsInput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Input\\OperationWithDefaultsInput',
        'smithy.protocoltests.rpcv2Cbor#OperationWithDefaultsOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\OperationWithDefaultsOutput',
        'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RecursiveShapesInputOutput',
        'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested1' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RecursiveShapesInputOutputNested1',
        'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested2' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RecursiveShapesInputOutputNested2',
        'smithy.protocoltests.rpcv2Cbor#RpcV2CborDenseMapsInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborDenseMapsInputOutput',
        'smithy.protocoltests.rpcv2Cbor#RpcV2CborListInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborListInputOutput',
        'smithy.protocoltests.rpcv2Cbor#RpcV2CborNestedUnion' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborNestedUnion',
        'smithy.protocoltests.rpcv2Cbor#RpcV2CborSparseMapsInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborSparseMapsInputOutput',
        'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnion' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborUnion',
        'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnionInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborUnionInputOutput',
        'smithy.protocoltests.rpcv2Cbor#SimpleScalarStructure' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\SimpleScalarStructure',
        'smithy.protocoltests.rpcv2Cbor#SimpleStructure' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\SimpleStructure',
        'smithy.protocoltests.rpcv2Cbor#SparseNullsOperationInputOutput' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\SparseNullsOperationInputOutput',
        'smithy.protocoltests.rpcv2Cbor#StructureListMember' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\StructureListMember',
        'smithy.protocoltests.rpcv2Cbor#TestEnum' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\TestEnum',
        'smithy.protocoltests.rpcv2Cbor#TestIntEnum' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\TestIntEnum',
        'smithy.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\FooEnum',
        'smithy.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\GreetingStruct',
        'smithy.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Exception\\ValidationException' => 'smithy.framework#ValidationException',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\ValidationExceptionField' => 'smithy.framework#ValidationExceptionField',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\ClientOptionalDefaults' => 'smithy.protocoltests.rpcv2Cbor#ClientOptionalDefaults',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Exception\\ComplexErrorException' => 'smithy.protocoltests.rpcv2Cbor#ComplexError',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\ComplexNestedErrorData' => 'smithy.protocoltests.rpcv2Cbor#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\Defaults' => 'smithy.protocoltests.rpcv2Cbor#Defaults',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\EmptyStructure' => 'smithy.protocoltests.rpcv2Cbor#EmptyStructure',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\Float16Output' => 'smithy.protocoltests.rpcv2Cbor#Float16Output',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\FractionalSecondsOutput' => 'smithy.protocoltests.rpcv2Cbor#FractionalSecondsOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\GreetingWithErrorsOutput' => 'smithy.protocoltests.rpcv2Cbor#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Exception\\InvalidGreetingException' => 'smithy.protocoltests.rpcv2Cbor#InvalidGreeting',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Input\\OperationWithDefaultsInput' => 'smithy.protocoltests.rpcv2Cbor#OperationWithDefaultsInput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Output\\OperationWithDefaultsOutput' => 'smithy.protocoltests.rpcv2Cbor#OperationWithDefaultsOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RecursiveShapesInputOutput' => 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RecursiveShapesInputOutputNested1' => 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested1',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RecursiveShapesInputOutputNested2' => 'smithy.protocoltests.rpcv2Cbor#RecursiveShapesInputOutputNested2',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborDenseMapsInputOutput' => 'smithy.protocoltests.rpcv2Cbor#RpcV2CborDenseMapsInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborListInputOutput' => 'smithy.protocoltests.rpcv2Cbor#RpcV2CborListInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborNestedUnion' => 'smithy.protocoltests.rpcv2Cbor#RpcV2CborNestedUnion',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborSparseMapsInputOutput' => 'smithy.protocoltests.rpcv2Cbor#RpcV2CborSparseMapsInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborUnion' => 'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnion',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\RpcV2CborUnionInputOutput' => 'smithy.protocoltests.rpcv2Cbor#RpcV2CborUnionInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\SimpleScalarStructure' => 'smithy.protocoltests.rpcv2Cbor#SimpleScalarStructure',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\SimpleStructure' => 'smithy.protocoltests.rpcv2Cbor#SimpleStructure',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\SparseNullsOperationInputOutput' => 'smithy.protocoltests.rpcv2Cbor#SparseNullsOperationInputOutput',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\StructureListMember' => 'smithy.protocoltests.rpcv2Cbor#StructureListMember',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\TestEnum' => 'smithy.protocoltests.rpcv2Cbor#TestEnum',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\TestIntEnum' => 'smithy.protocoltests.rpcv2Cbor#TestIntEnum',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\FooEnum' => 'smithy.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\GreetingStruct' => 'smithy.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\RpcV2Cbor\\Shape\\IntegerEnum' => 'smithy.protocoltests.shared#IntegerEnum'
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
