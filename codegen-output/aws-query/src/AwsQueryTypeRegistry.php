<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;

/**
 * Shape-ID registry for the AwsQuery service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for the
 * class name when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class AwsQueryTypeRegistry implements TypeRegistryInterface
{
    use TypeRegistryDeserializeTrait;

    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'aws.protocoltests.query#ComplexError' => 'Smithy\\ProtocolTests\\AwsQuery\\Exception\\ComplexErrorException',
        'aws.protocoltests.query#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\ComplexNestedErrorData',
        'aws.protocoltests.query#CustomCodeError' => 'Smithy\\ProtocolTests\\AwsQuery\\Exception\\CustomCodeErrorException',
        'aws.protocoltests.query#DatetimeOffsetsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\DatetimeOffsetsOutput',
        'aws.protocoltests.query#EmptyInputAndEmptyOutputInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\EmptyInputAndEmptyOutputInput',
        'aws.protocoltests.query#EmptyInputAndEmptyOutputOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\EmptyInputAndEmptyOutputOutput',
        'aws.protocoltests.query#FlattenedXmlMapOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\FlattenedXmlMapOutput',
        'aws.protocoltests.query#FlattenedXmlMapWithXmlNameOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\FlattenedXmlMapWithXmlNameOutput',
        'aws.protocoltests.query#FlattenedXmlMapWithXmlNamespaceOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\FlattenedXmlMapWithXmlNamespaceOutput',
        'aws.protocoltests.query#FractionalSecondsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\FractionalSecondsOutput',
        'aws.protocoltests.query#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\GreetingWithErrorsOutput',
        'aws.protocoltests.query#HostLabelInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\HostLabelInput',
        'aws.protocoltests.query#IgnoresWrappingXmlNameOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\IgnoresWrappingXmlNameOutput',
        'aws.protocoltests.query#InvalidGreeting' => 'Smithy\\ProtocolTests\\AwsQuery\\Exception\\InvalidGreetingException',
        'aws.protocoltests.query#NestedStructWithList' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\NestedStructWithList',
        'aws.protocoltests.query#NestedStructWithMap' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\NestedStructWithMap',
        'aws.protocoltests.query#NestedStructuresInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\NestedStructuresInput',
        'aws.protocoltests.query#NoInputAndOutputInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\NoInputAndOutputInput',
        'aws.protocoltests.query#NoInputAndOutputOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\NoInputAndOutputOutput',
        'aws.protocoltests.query#PutWithContentEncodingInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\PutWithContentEncodingInput',
        'aws.protocoltests.query#QueryIdempotencyTokenAutoFillInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryIdempotencyTokenAutoFillInput',
        'aws.protocoltests.query#QueryListsInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryListsInput',
        'aws.protocoltests.query#QueryMapsInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryMapsInput',
        'aws.protocoltests.query#QueryTimestampsInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryTimestampsInput',
        'aws.protocoltests.query#RecursiveXmlShapesOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\RecursiveXmlShapesOutput',
        'aws.protocoltests.query#RecursiveXmlShapesOutputNested1' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\RecursiveXmlShapesOutputNested1',
        'aws.protocoltests.query#RecursiveXmlShapesOutputNested2' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\RecursiveXmlShapesOutputNested2',
        'aws.protocoltests.query#SimpleInputParamsInput' => 'Smithy\\ProtocolTests\\AwsQuery\\Input\\SimpleInputParamsInput',
        'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\SimpleScalarXmlPropertiesOutput',
        'aws.protocoltests.query#StructArg' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\StructArg',
        'aws.protocoltests.query#StructureListMember' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\StructureListMember',
        'aws.protocoltests.query#XmlBlobsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlBlobsOutput',
        'aws.protocoltests.query#XmlEnumsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlEnumsOutput',
        'aws.protocoltests.query#XmlIntEnumsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlIntEnumsOutput',
        'aws.protocoltests.query#XmlListsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlListsOutput',
        'aws.protocoltests.query#XmlMapsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlMapsOutput',
        'aws.protocoltests.query#XmlMapsXmlNameOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlMapsXmlNameOutput',
        'aws.protocoltests.query#XmlNamespaceNested' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\XmlNamespaceNested',
        'aws.protocoltests.query#XmlNamespacesOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlNamespacesOutput',
        'aws.protocoltests.query#XmlTimestampsOutput' => 'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlTimestampsOutput',
        'aws.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\FooEnum',
        'aws.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\GreetingStruct',
        'aws.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\AwsQuery\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\AwsQuery\\Exception\\ComplexErrorException' => 'aws.protocoltests.query#ComplexError',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\ComplexNestedErrorData' => 'aws.protocoltests.query#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\AwsQuery\\Exception\\CustomCodeErrorException' => 'aws.protocoltests.query#CustomCodeError',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\DatetimeOffsetsOutput' => 'aws.protocoltests.query#DatetimeOffsetsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\EmptyInputAndEmptyOutputInput' => 'aws.protocoltests.query#EmptyInputAndEmptyOutputInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\EmptyInputAndEmptyOutputOutput' => 'aws.protocoltests.query#EmptyInputAndEmptyOutputOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\FlattenedXmlMapOutput' => 'aws.protocoltests.query#FlattenedXmlMapOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\FlattenedXmlMapWithXmlNameOutput' => 'aws.protocoltests.query#FlattenedXmlMapWithXmlNameOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\FlattenedXmlMapWithXmlNamespaceOutput' => 'aws.protocoltests.query#FlattenedXmlMapWithXmlNamespaceOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\FractionalSecondsOutput' => 'aws.protocoltests.query#FractionalSecondsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\GreetingWithErrorsOutput' => 'aws.protocoltests.query#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\HostLabelInput' => 'aws.protocoltests.query#HostLabelInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\IgnoresWrappingXmlNameOutput' => 'aws.protocoltests.query#IgnoresWrappingXmlNameOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Exception\\InvalidGreetingException' => 'aws.protocoltests.query#InvalidGreeting',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\NestedStructWithList' => 'aws.protocoltests.query#NestedStructWithList',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\NestedStructWithMap' => 'aws.protocoltests.query#NestedStructWithMap',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\NestedStructuresInput' => 'aws.protocoltests.query#NestedStructuresInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\NoInputAndOutputInput' => 'aws.protocoltests.query#NoInputAndOutputInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\NoInputAndOutputOutput' => 'aws.protocoltests.query#NoInputAndOutputOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\PutWithContentEncodingInput' => 'aws.protocoltests.query#PutWithContentEncodingInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryIdempotencyTokenAutoFillInput' => 'aws.protocoltests.query#QueryIdempotencyTokenAutoFillInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryListsInput' => 'aws.protocoltests.query#QueryListsInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryMapsInput' => 'aws.protocoltests.query#QueryMapsInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\QueryTimestampsInput' => 'aws.protocoltests.query#QueryTimestampsInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\RecursiveXmlShapesOutput' => 'aws.protocoltests.query#RecursiveXmlShapesOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\RecursiveXmlShapesOutputNested1' => 'aws.protocoltests.query#RecursiveXmlShapesOutputNested1',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\RecursiveXmlShapesOutputNested2' => 'aws.protocoltests.query#RecursiveXmlShapesOutputNested2',
        'Smithy\\ProtocolTests\\AwsQuery\\Input\\SimpleInputParamsInput' => 'aws.protocoltests.query#SimpleInputParamsInput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\SimpleScalarXmlPropertiesOutput' => 'aws.protocoltests.query#SimpleScalarXmlPropertiesOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\StructArg' => 'aws.protocoltests.query#StructArg',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\StructureListMember' => 'aws.protocoltests.query#StructureListMember',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlBlobsOutput' => 'aws.protocoltests.query#XmlBlobsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlEnumsOutput' => 'aws.protocoltests.query#XmlEnumsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlIntEnumsOutput' => 'aws.protocoltests.query#XmlIntEnumsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlListsOutput' => 'aws.protocoltests.query#XmlListsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlMapsOutput' => 'aws.protocoltests.query#XmlMapsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlMapsXmlNameOutput' => 'aws.protocoltests.query#XmlMapsXmlNameOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\XmlNamespaceNested' => 'aws.protocoltests.query#XmlNamespaceNested',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlNamespacesOutput' => 'aws.protocoltests.query#XmlNamespacesOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Output\\XmlTimestampsOutput' => 'aws.protocoltests.query#XmlTimestampsOutput',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\FooEnum' => 'aws.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\GreetingStruct' => 'aws.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\AwsQuery\\Shape\\IntegerEnum' => 'aws.protocoltests.shared#IntegerEnum'
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
