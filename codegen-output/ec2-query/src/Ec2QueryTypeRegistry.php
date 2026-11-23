<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\TypeRegistryDeserializeTrait;
use Smithy\Core\Serde\TypeRegistryInterface;

/**
 * Shape-ID registry for the Ec2Query service.
 *
 * Populated once at codegen time from the service closure. Callers
 * use `schemaFor` for lazy schema resolution, `classFor` for a raw
 * class-string when they need to reflect on the generated type, and
 * `shapeIdFor` for the inverse lookup keyed by class. `deserialize`
 * builds the registered shape that a document's discriminator names.
 */
final class Ec2QueryTypeRegistry implements TypeRegistryInterface
{
    use TypeRegistryDeserializeTrait;

    /**
     * @var array<string, class-string>
     */
    private const CLASSES_BY_SHAPE_ID = [
        'aws.protocoltests.ec2#ComplexError' => 'Smithy\\ProtocolTests\\Ec2Query\\Exception\\ComplexErrorException',
        'aws.protocoltests.ec2#ComplexNestedErrorData' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\ComplexNestedErrorData',
        'aws.protocoltests.ec2#DatetimeOffsetsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\DatetimeOffsetsOutput',
        'aws.protocoltests.ec2#EmptyInputAndEmptyOutputInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\EmptyInputAndEmptyOutputInput',
        'aws.protocoltests.ec2#EmptyInputAndEmptyOutputOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\EmptyInputAndEmptyOutputOutput',
        'aws.protocoltests.ec2#FractionalSecondsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\FractionalSecondsOutput',
        'aws.protocoltests.ec2#GreetingWithErrorsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\GreetingWithErrorsOutput',
        'aws.protocoltests.ec2#HostLabelInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\HostLabelInput',
        'aws.protocoltests.ec2#IgnoresWrappingXmlNameOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\IgnoresWrappingXmlNameOutput',
        'aws.protocoltests.ec2#InvalidGreeting' => 'Smithy\\ProtocolTests\\Ec2Query\\Exception\\InvalidGreetingException',
        'aws.protocoltests.ec2#NestedStructWithList' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\NestedStructWithList',
        'aws.protocoltests.ec2#NestedStructuresInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\NestedStructuresInput',
        'aws.protocoltests.ec2#NoInputAndOutputOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\NoInputAndOutputOutput',
        'aws.protocoltests.ec2#PutWithContentEncodingInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\PutWithContentEncodingInput',
        'aws.protocoltests.ec2#QueryIdempotencyTokenAutoFillInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\QueryIdempotencyTokenAutoFillInput',
        'aws.protocoltests.ec2#QueryListsInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\QueryListsInput',
        'aws.protocoltests.ec2#QueryTimestampsInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\QueryTimestampsInput',
        'aws.protocoltests.ec2#RecursiveXmlShapesOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\RecursiveXmlShapesOutput',
        'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\RecursiveXmlShapesOutputNested1',
        'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested2' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\RecursiveXmlShapesOutputNested2',
        'aws.protocoltests.ec2#SimpleInputParamsInput' => 'Smithy\\ProtocolTests\\Ec2Query\\Input\\SimpleInputParamsInput',
        'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\SimpleScalarXmlPropertiesOutput',
        'aws.protocoltests.ec2#StructArg' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\StructArg',
        'aws.protocoltests.ec2#StructureListMember' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\StructureListMember',
        'aws.protocoltests.ec2#XmlBlobsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlBlobsOutput',
        'aws.protocoltests.ec2#XmlEnumsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlEnumsOutput',
        'aws.protocoltests.ec2#XmlIntEnumsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlIntEnumsOutput',
        'aws.protocoltests.ec2#XmlListsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlListsOutput',
        'aws.protocoltests.ec2#XmlNamespaceNested' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\XmlNamespaceNested',
        'aws.protocoltests.ec2#XmlNamespacesOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlNamespacesOutput',
        'aws.protocoltests.ec2#XmlTimestampsOutput' => 'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlTimestampsOutput',
        'aws.protocoltests.shared#FooEnum' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\FooEnum',
        'aws.protocoltests.shared#GreetingStruct' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\GreetingStruct',
        'aws.protocoltests.shared#IntegerEnum' => 'Smithy\\ProtocolTests\\Ec2Query\\Shape\\IntegerEnum'
    ];

    /**
     * @var array<class-string, string>
     */
    private const SHAPE_IDS_BY_CLASS = [
        'Smithy\\ProtocolTests\\Ec2Query\\Exception\\ComplexErrorException' => 'aws.protocoltests.ec2#ComplexError',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\ComplexNestedErrorData' => 'aws.protocoltests.ec2#ComplexNestedErrorData',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\DatetimeOffsetsOutput' => 'aws.protocoltests.ec2#DatetimeOffsetsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\EmptyInputAndEmptyOutputInput' => 'aws.protocoltests.ec2#EmptyInputAndEmptyOutputInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\EmptyInputAndEmptyOutputOutput' => 'aws.protocoltests.ec2#EmptyInputAndEmptyOutputOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\FractionalSecondsOutput' => 'aws.protocoltests.ec2#FractionalSecondsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\GreetingWithErrorsOutput' => 'aws.protocoltests.ec2#GreetingWithErrorsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\HostLabelInput' => 'aws.protocoltests.ec2#HostLabelInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\IgnoresWrappingXmlNameOutput' => 'aws.protocoltests.ec2#IgnoresWrappingXmlNameOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Exception\\InvalidGreetingException' => 'aws.protocoltests.ec2#InvalidGreeting',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\NestedStructWithList' => 'aws.protocoltests.ec2#NestedStructWithList',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\NestedStructuresInput' => 'aws.protocoltests.ec2#NestedStructuresInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\NoInputAndOutputOutput' => 'aws.protocoltests.ec2#NoInputAndOutputOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\PutWithContentEncodingInput' => 'aws.protocoltests.ec2#PutWithContentEncodingInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\QueryIdempotencyTokenAutoFillInput' => 'aws.protocoltests.ec2#QueryIdempotencyTokenAutoFillInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\QueryListsInput' => 'aws.protocoltests.ec2#QueryListsInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\QueryTimestampsInput' => 'aws.protocoltests.ec2#QueryTimestampsInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\RecursiveXmlShapesOutput' => 'aws.protocoltests.ec2#RecursiveXmlShapesOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\RecursiveXmlShapesOutputNested1' => 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested1',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\RecursiveXmlShapesOutputNested2' => 'aws.protocoltests.ec2#RecursiveXmlShapesOutputNested2',
        'Smithy\\ProtocolTests\\Ec2Query\\Input\\SimpleInputParamsInput' => 'aws.protocoltests.ec2#SimpleInputParamsInput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\SimpleScalarXmlPropertiesOutput' => 'aws.protocoltests.ec2#SimpleScalarXmlPropertiesOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\StructArg' => 'aws.protocoltests.ec2#StructArg',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\StructureListMember' => 'aws.protocoltests.ec2#StructureListMember',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlBlobsOutput' => 'aws.protocoltests.ec2#XmlBlobsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlEnumsOutput' => 'aws.protocoltests.ec2#XmlEnumsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlIntEnumsOutput' => 'aws.protocoltests.ec2#XmlIntEnumsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlListsOutput' => 'aws.protocoltests.ec2#XmlListsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\XmlNamespaceNested' => 'aws.protocoltests.ec2#XmlNamespaceNested',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlNamespacesOutput' => 'aws.protocoltests.ec2#XmlNamespacesOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Output\\XmlTimestampsOutput' => 'aws.protocoltests.ec2#XmlTimestampsOutput',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\FooEnum' => 'aws.protocoltests.shared#FooEnum',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\GreetingStruct' => 'aws.protocoltests.shared#GreetingStruct',
        'Smithy\\ProtocolTests\\Ec2Query\\Shape\\IntegerEnum' => 'aws.protocoltests.shared#IntegerEnum'
    ];

    /**
     * Return the generated PHP class-string for a Smithy shape ID,
     * or null when the ID is unknown to this service.
     *
     * @return class-string|null
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
