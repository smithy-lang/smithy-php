<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0;

/**
 * Shape-ID registry for the AwsJson1_0 service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for a raw
 * class-string when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class.
 */
final class AwsJson1_0TypeRegistry
{
    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'aws.protocoltests.json10#ComplexError' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Exception\\ComplexErrorException',
        'aws.protocoltests.json10#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\ComplexNestedErrorData',
        'aws.protocoltests.json10#ContentTypeParametersInput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\ContentTypeParametersInput',
        'aws.protocoltests.json10#ContentTypeParametersOutput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\ContentTypeParametersOutput',
        'aws.protocoltests.json10#EmptyInputAndEmptyOutputInput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\EmptyInputAndEmptyOutputInput',
        'aws.protocoltests.json10#EmptyInputAndEmptyOutputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\EmptyInputAndEmptyOutputOutput',
        'aws.protocoltests.json10#EndpointWithHostLabelOperationInput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\EndpointWithHostLabelOperationInput',
        'aws.protocoltests.json10#FooError' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Exception\\FooErrorException',
        'aws.protocoltests.json10#GreetingWithErrorsInput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\GreetingWithErrorsInput',
        'aws.protocoltests.json10#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\GreetingWithErrorsOutput',
        'aws.protocoltests.json10#InvalidGreeting' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Exception\\InvalidGreetingException',
        'aws.protocoltests.json10#JsonUnionsInput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\JsonUnionsInput',
        'aws.protocoltests.json10#JsonUnionsOutput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\JsonUnionsOutput',
        'aws.protocoltests.json10#MyUnion' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\MyUnion',
        'aws.protocoltests.json10#NoInputAndOutputOutput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\NoInputAndOutputOutput',
        'aws.protocoltests.json10#PutWithContentEncodingInput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\PutWithContentEncodingInput',
        'aws.protocoltests.json10#SimpleScalarPropertiesInput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\SimpleScalarPropertiesInput',
        'aws.protocoltests.json10#SimpleScalarPropertiesOutput' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\SimpleScalarPropertiesOutput',
        'aws.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\FooEnum',
        'aws.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\GreetingStruct',
        'aws.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\AwsJson1_0\\Exception\\ComplexErrorException' => 'aws.protocoltests.json10#ComplexError',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\ComplexNestedErrorData' => 'aws.protocoltests.json10#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\ContentTypeParametersInput' => 'aws.protocoltests.json10#ContentTypeParametersInput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\ContentTypeParametersOutput' => 'aws.protocoltests.json10#ContentTypeParametersOutput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\EmptyInputAndEmptyOutputInput' => 'aws.protocoltests.json10#EmptyInputAndEmptyOutputInput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\EmptyInputAndEmptyOutputOutput' => 'aws.protocoltests.json10#EmptyInputAndEmptyOutputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\EndpointWithHostLabelOperationInput' => 'aws.protocoltests.json10#EndpointWithHostLabelOperationInput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Exception\\FooErrorException' => 'aws.protocoltests.json10#FooError',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\GreetingWithErrorsInput' => 'aws.protocoltests.json10#GreetingWithErrorsInput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\GreetingWithErrorsOutput' => 'aws.protocoltests.json10#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Exception\\InvalidGreetingException' => 'aws.protocoltests.json10#InvalidGreeting',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\JsonUnionsInput' => 'aws.protocoltests.json10#JsonUnionsInput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\JsonUnionsOutput' => 'aws.protocoltests.json10#JsonUnionsOutput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\MyUnion' => 'aws.protocoltests.json10#MyUnion',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\NoInputAndOutputOutput' => 'aws.protocoltests.json10#NoInputAndOutputOutput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\PutWithContentEncodingInput' => 'aws.protocoltests.json10#PutWithContentEncodingInput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Input\\SimpleScalarPropertiesInput' => 'aws.protocoltests.json10#SimpleScalarPropertiesInput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Output\\SimpleScalarPropertiesOutput' => 'aws.protocoltests.json10#SimpleScalarPropertiesOutput',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\FooEnum' => 'aws.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\GreetingStruct' => 'aws.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\AwsJson1_0\\Shape\\IntegerEnum' => 'aws.protocoltests.shared#IntegerEnum'
    ];

    /**
     * Return the generated PHP class-string for a Smithy shape ID,
     * or null when the ID is unknown to this service.
     *
     * @return class-string|null
     */
    public static function classFor(string $shapeId): ?string
    {
        return self::CLASSES_BY_SHAPE_ID[$shapeId] ?? null;
    }

    /**
     * Return the Smithy shape ID for a generated class.
     * Returns null when this codegen run did not produce the class.
     *
     * @param class-string $class
     */
    public static function shapeIdFor(string $class): ?string
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
    public static function schemaFor(string $shapeId): ?\Smithy\Core\Serde\Schema
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
    public static function all(): array
    {
        return self::CLASSES_BY_SHAPE_ID;
    }
}
