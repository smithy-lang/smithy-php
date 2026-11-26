<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Operation\AllQueryStringTypesOperation;
use Smithy\ProtocolTests\RestXml\Operation\BodyWithXmlNameOperation;
use Smithy\ProtocolTests\RestXml\Operation\ConstantAndVariableQueryStringOperation;
use Smithy\ProtocolTests\RestXml\Operation\ConstantQueryStringOperation;
use Smithy\ProtocolTests\RestXml\Operation\ContentTypeParametersOperation;
use Smithy\ProtocolTests\RestXml\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\RestXml\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\RestXml\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\RestXml\Operation\EndpointWithHostLabelHeaderOperationOperation;
use Smithy\ProtocolTests\RestXml\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\RestXml\Operation\FlattenedXmlMapOperation;
use Smithy\ProtocolTests\RestXml\Operation\FlattenedXmlMapWithXmlNameOperation;
use Smithy\ProtocolTests\RestXml\Operation\FlattenedXmlMapWithXmlNamespaceOperation;
use Smithy\ProtocolTests\RestXml\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\RestXml\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpEmptyPrefixHeadersOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpEnumPayloadOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPayloadTraitsOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPayloadWithMemberXmlNameOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPayloadWithStructureOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPayloadWithUnionOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPayloadWithXmlNameOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPayloadWithXmlNamespaceAndPrefixOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPayloadWithXmlNamespaceOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpPrefixHeadersOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpRequestWithFloatLabelsOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpRequestWithGreedyLabelInPathOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpRequestWithLabelsAndTimestampFormatOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpRequestWithLabelsOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpResponseCodeOperation;
use Smithy\ProtocolTests\RestXml\Operation\HttpStringPayloadOperation;
use Smithy\ProtocolTests\RestXml\Operation\IgnoreQueryParamsInResponseOperation;
use Smithy\ProtocolTests\RestXml\Operation\InputAndOutputWithHeadersOperation;
use Smithy\ProtocolTests\RestXml\Operation\NestedXmlMapWithXmlNameOperation;
use Smithy\ProtocolTests\RestXml\Operation\NestedXmlMapsOperation;
use Smithy\ProtocolTests\RestXml\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\RestXml\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\RestXml\Operation\NullAndEmptyHeadersClientOperation;
use Smithy\ProtocolTests\RestXml\Operation\NullAndEmptyHeadersServerOperation;
use Smithy\ProtocolTests\RestXml\Operation\OmitsNullSerializesEmptyStringOperation;
use Smithy\ProtocolTests\RestXml\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\RestXml\Operation\QueryIdempotencyTokenAutoFillOperation;
use Smithy\ProtocolTests\RestXml\Operation\QueryParamsAsStringListMapOperation;
use Smithy\ProtocolTests\RestXml\Operation\QueryPrecedenceOperation;
use Smithy\ProtocolTests\RestXml\Operation\RecursiveShapesOperation;
use Smithy\ProtocolTests\RestXml\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\RestXml\Operation\TimestampFormatHeadersOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlAttributesInMiddleOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlAttributesOnPayloadOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlAttributesOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlBlobsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlEmptyBlobsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlEmptyListsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlEmptyMapsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlEmptyStringsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlEnumsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlIntEnumsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlListsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlMapWithXmlNamespaceOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlMapsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlMapsXmlNameOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlNamespacesOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlTimestampsOperation;
use Smithy\ProtocolTests\RestXml\Operation\XmlUnionsOperation;

/**
 * A REST XML service that sends XML requests and responses.
 */
final class RestXmlService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'RestXml';
    }

    public static function id(): string
    {
        return 'aws.protocoltests.restxml#RestXml';
    }

    public static function version(): string
    {
        return '2019-12-16';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'aws.protocoltests.restxml#RestXml',
            type: ShapeType::Service,
            traitsById: [
                'aws.api#service' => ['sdkId' => 'Rest Xml Protocol'],
                'aws.auth#sigv4' => ['name' => 'restxml'],
                'aws.protocols#restXml' => [],
                'smithy.api#documentation' => 'A REST XML service that sends XML requests and responses.',
                'smithy.api#title' => 'Sample Rest Xml Protocol Service'
            ]
        );
    }

    /**
     * @return list<class-string>
     */
    public static function operations(): array
    {
        return [
            AllQueryStringTypesOperation::class,
            BodyWithXmlNameOperation::class,
            ConstantAndVariableQueryStringOperation::class,
            ConstantQueryStringOperation::class,
            ContentTypeParametersOperation::class,
            DatetimeOffsetsOperation::class,
            EmptyInputAndEmptyOutputOperation::class,
            EndpointOperationOperation::class,
            EndpointWithHostLabelHeaderOperationOperation::class,
            EndpointWithHostLabelOperationOperation::class,
            FlattenedXmlMapOperation::class,
            FlattenedXmlMapWithXmlNameOperation::class,
            FlattenedXmlMapWithXmlNamespaceOperation::class,
            FractionalSecondsOperation::class,
            GreetingWithErrorsOperation::class,
            HttpEmptyPrefixHeadersOperation::class,
            HttpEnumPayloadOperation::class,
            HttpPayloadTraitsOperation::class,
            HttpPayloadWithMemberXmlNameOperation::class,
            HttpPayloadWithStructureOperation::class,
            HttpPayloadWithUnionOperation::class,
            HttpPayloadWithXmlNameOperation::class,
            HttpPayloadWithXmlNamespaceOperation::class,
            HttpPayloadWithXmlNamespaceAndPrefixOperation::class,
            HttpPrefixHeadersOperation::class,
            HttpRequestWithFloatLabelsOperation::class,
            HttpRequestWithGreedyLabelInPathOperation::class,
            HttpRequestWithLabelsOperation::class,
            HttpRequestWithLabelsAndTimestampFormatOperation::class,
            HttpResponseCodeOperation::class,
            HttpStringPayloadOperation::class,
            IgnoreQueryParamsInResponseOperation::class,
            InputAndOutputWithHeadersOperation::class,
            NestedXmlMapsOperation::class,
            NestedXmlMapWithXmlNameOperation::class,
            NoInputAndNoOutputOperation::class,
            NoInputAndOutputOperation::class,
            NullAndEmptyHeadersClientOperation::class,
            NullAndEmptyHeadersServerOperation::class,
            OmitsNullSerializesEmptyStringOperation::class,
            PutWithContentEncodingOperation::class,
            QueryIdempotencyTokenAutoFillOperation::class,
            QueryParamsAsStringListMapOperation::class,
            QueryPrecedenceOperation::class,
            RecursiveShapesOperation::class,
            SimpleScalarPropertiesOperation::class,
            TimestampFormatHeadersOperation::class,
            XmlAttributesOperation::class,
            XmlAttributesInMiddleOperation::class,
            XmlAttributesOnPayloadOperation::class,
            XmlBlobsOperation::class,
            XmlEmptyBlobsOperation::class,
            XmlEmptyListsOperation::class,
            XmlEmptyMapsOperation::class,
            XmlEmptyStringsOperation::class,
            XmlEnumsOperation::class,
            XmlIntEnumsOperation::class,
            XmlListsOperation::class,
            XmlMapsOperation::class,
            XmlMapsXmlNameOperation::class,
            XmlMapWithXmlNamespaceOperation::class,
            XmlNamespacesOperation::class,
            XmlTimestampsOperation::class,
            XmlUnionsOperation::class
        ];
    }
}
