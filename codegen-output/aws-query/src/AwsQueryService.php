<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsQuery\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FlattenedXmlMapOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FlattenedXmlMapWithXmlNameOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FlattenedXmlMapWithXmlNamespaceOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\IgnoresWrappingXmlNameOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\NestedStructuresOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryIdempotencyTokenAutoFillOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryListsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryMapsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\QueryTimestampsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\RecursiveXmlShapesOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\SimpleInputParamsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\SimpleScalarXmlPropertiesOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlBlobsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEmptyBlobsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEmptyListsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEmptyMapsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlEnumsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlIntEnumsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlListsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlMapsOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlMapsXmlNameOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlNamespacesOperation;
use Smithy\ProtocolTests\AwsQuery\Operation\XmlTimestampsOperation;

/**
 * A query service that sends query requests and XML responses.
 */
final class AwsQueryService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'AwsQuery';
    }

    public static function id(): string
    {
        return 'aws.protocoltests.query#AwsQuery';
    }

    public static function version(): string
    {
        return '2020-01-08';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'aws.protocoltests.query#AwsQuery',
            type: ShapeType::Service,
            traitsById: [
                'aws.api#service' => ['sdkId' => 'Query Protocol'],
                'aws.auth#sigv4' => ['name' => 'awsquery'],
                'aws.protocols#awsQuery' => [],
                'smithy.api#documentation' => 'A query service that sends query requests and XML responses.',
                'smithy.api#title' => 'Sample Query Protocol Service',
                'smithy.api#xmlNamespace' => ['uri' => 'https://example.com/']
            ]
        );
    }

    public static function operations(): array
    {
        return [
            DatetimeOffsetsOperation::class,
            EmptyInputAndEmptyOutputOperation::class,
            EndpointOperationOperation::class,
            EndpointWithHostLabelOperationOperation::class,
            FlattenedXmlMapOperation::class,
            FlattenedXmlMapWithXmlNameOperation::class,
            FlattenedXmlMapWithXmlNamespaceOperation::class,
            FractionalSecondsOperation::class,
            GreetingWithErrorsOperation::class,
            HostWithPathOperationOperation::class,
            IgnoresWrappingXmlNameOperation::class,
            NestedStructuresOperation::class,
            NoInputAndNoOutputOperation::class,
            NoInputAndOutputOperation::class,
            PutWithContentEncodingOperation::class,
            QueryIdempotencyTokenAutoFillOperation::class,
            QueryListsOperation::class,
            QueryMapsOperation::class,
            QueryTimestampsOperation::class,
            RecursiveXmlShapesOperation::class,
            SimpleInputParamsOperation::class,
            SimpleScalarXmlPropertiesOperation::class,
            XmlBlobsOperation::class,
            XmlEmptyBlobsOperation::class,
            XmlEmptyListsOperation::class,
            XmlEmptyMapsOperation::class,
            XmlEnumsOperation::class,
            XmlIntEnumsOperation::class,
            XmlListsOperation::class,
            XmlMapsOperation::class,
            XmlMapsXmlNameOperation::class,
            XmlNamespacesOperation::class,
            XmlTimestampsOperation::class
        ];
    }
}
