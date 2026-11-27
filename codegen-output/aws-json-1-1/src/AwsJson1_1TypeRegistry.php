<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;

/**
 * Shape-ID registry for the AwsJson1_1 service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for the
 * class name when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class AwsJson1_1TypeRegistry implements TypeRegistryInterface
{
    use TypeRegistryDeserializeTrait;

    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'aws.protocoltests.json#ComplexError' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\ComplexErrorException',
        'aws.protocoltests.json#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\ComplexNestedErrorData',
        'aws.protocoltests.json#ContentTypeParametersInput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\ContentTypeParametersInput',
        'aws.protocoltests.json#ContentTypeParametersOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\ContentTypeParametersOutput',
        'aws.protocoltests.json#DatetimeOffsetsOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\DatetimeOffsetsOutput',
        'aws.protocoltests.json#EmptyStruct' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\EmptyStruct',
        'aws.protocoltests.json#ErrorWithMembers' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\ErrorWithMembersException',
        'aws.protocoltests.json#ErrorWithoutMembers' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\ErrorWithoutMembersException',
        'aws.protocoltests.json#FooError' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\FooErrorException',
        'aws.protocoltests.json#FractionalSecondsOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\FractionalSecondsOutput',
        'aws.protocoltests.json#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\GreetingWithErrorsOutput',
        'aws.protocoltests.json#HostLabelInput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\HostLabelInput',
        'aws.protocoltests.json#InvalidGreeting' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\InvalidGreetingException',
        'aws.protocoltests.json#JsonEnumsInputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\JsonEnumsInputOutput',
        'aws.protocoltests.json#JsonIntEnumsInputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\JsonIntEnumsInputOutput',
        'aws.protocoltests.json#KitchenSink' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\KitchenSink',
        'aws.protocoltests.json#MyUnion' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\MyUnion',
        'aws.protocoltests.json#NullOperationInputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\NullOperationInputOutput',
        'aws.protocoltests.json#OperationWithOptionalInputOutputInput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\OperationWithOptionalInputOutputInput',
        'aws.protocoltests.json#OperationWithOptionalInputOutputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\OperationWithOptionalInputOutputOutput',
        'aws.protocoltests.json#PutAndGetInlineDocumentsInputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\PutAndGetInlineDocumentsInputOutput',
        'aws.protocoltests.json#PutWithContentEncodingInput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\PutWithContentEncodingInput',
        'aws.protocoltests.json#SimpleScalarPropertiesInputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\SimpleScalarPropertiesInputOutput',
        'aws.protocoltests.json#SimpleStruct' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\SimpleStruct',
        'aws.protocoltests.json#SparseNullsOperationInputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\SparseNullsOperationInputOutput',
        'aws.protocoltests.json#StructWithJsonName' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\StructWithJsonName',
        'aws.protocoltests.json#UnionInputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\UnionInputOutput',
        'aws.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\FooEnum',
        'aws.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\GreetingStruct',
        'aws.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\ComplexErrorException' => 'aws.protocoltests.json#ComplexError',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\ComplexNestedErrorData' => 'aws.protocoltests.json#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\ContentTypeParametersInput' => 'aws.protocoltests.json#ContentTypeParametersInput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\ContentTypeParametersOutput' => 'aws.protocoltests.json#ContentTypeParametersOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\DatetimeOffsetsOutput' => 'aws.protocoltests.json#DatetimeOffsetsOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\EmptyStruct' => 'aws.protocoltests.json#EmptyStruct',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\ErrorWithMembersException' => 'aws.protocoltests.json#ErrorWithMembers',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\ErrorWithoutMembersException' => 'aws.protocoltests.json#ErrorWithoutMembers',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\FooErrorException' => 'aws.protocoltests.json#FooError',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\FractionalSecondsOutput' => 'aws.protocoltests.json#FractionalSecondsOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\GreetingWithErrorsOutput' => 'aws.protocoltests.json#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\HostLabelInput' => 'aws.protocoltests.json#HostLabelInput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Exception\\InvalidGreetingException' => 'aws.protocoltests.json#InvalidGreeting',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\JsonEnumsInputOutput' => 'aws.protocoltests.json#JsonEnumsInputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\JsonIntEnumsInputOutput' => 'aws.protocoltests.json#JsonIntEnumsInputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\KitchenSink' => 'aws.protocoltests.json#KitchenSink',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\MyUnion' => 'aws.protocoltests.json#MyUnion',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\NullOperationInputOutput' => 'aws.protocoltests.json#NullOperationInputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\OperationWithOptionalInputOutputInput' => 'aws.protocoltests.json#OperationWithOptionalInputOutputInput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Output\\OperationWithOptionalInputOutputOutput' => 'aws.protocoltests.json#OperationWithOptionalInputOutputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\PutAndGetInlineDocumentsInputOutput' => 'aws.protocoltests.json#PutAndGetInlineDocumentsInputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Input\\PutWithContentEncodingInput' => 'aws.protocoltests.json#PutWithContentEncodingInput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\SimpleScalarPropertiesInputOutput' => 'aws.protocoltests.json#SimpleScalarPropertiesInputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\SimpleStruct' => 'aws.protocoltests.json#SimpleStruct',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\SparseNullsOperationInputOutput' => 'aws.protocoltests.json#SparseNullsOperationInputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\StructWithJsonName' => 'aws.protocoltests.json#StructWithJsonName',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\UnionInputOutput' => 'aws.protocoltests.json#UnionInputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\FooEnum' => 'aws.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\GreetingStruct' => 'aws.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\AwsJson1_1\\Shape\\IntegerEnum' => 'aws.protocoltests.shared#IntegerEnum'
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
