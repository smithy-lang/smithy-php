<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\Ec2Query;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\Ec2Query\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\IgnoresWrappingXmlNameOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\NestedStructuresOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\QueryIdempotencyTokenAutoFillOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\QueryListsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\QueryTimestampsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\RecursiveXmlShapesOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\SimpleInputParamsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\SimpleScalarXmlPropertiesOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlBlobsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlEmptyBlobsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlEmptyListsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlEnumsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlIntEnumsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlListsOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlNamespacesOperation;
use Smithy\ProtocolTests\Ec2Query\Operation\XmlTimestampsOperation;

/**
 * An EC2 query service that sends query requests and XML responses.
 */
final class Ec2QueryService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'Ec2Query';
    }

    public static function id(): string
    {
        return 'aws.protocoltests.ec2#AwsEc2';
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
            id: 'aws.protocoltests.ec2#AwsEc2',
            type: ShapeType::Service,
            traitsById: [
                'aws.api#service' => ['sdkId' => 'EC2 Protocol'],
                'aws.auth#sigv4' => ['name' => 'ec2query'],
                'aws.protocols#ec2Query' => [],
                'smithy.api#documentation' => 'An EC2 query service that sends query requests and XML responses.',
                'smithy.api#suppress' => ['SigV4Traits'],
                'smithy.api#title' => 'Sample Ec2 Protocol Service',
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
            FractionalSecondsOperation::class,
            GreetingWithErrorsOperation::class,
            HostWithPathOperationOperation::class,
            IgnoresWrappingXmlNameOperation::class,
            NestedStructuresOperation::class,
            NoInputAndOutputOperation::class,
            PutWithContentEncodingOperation::class,
            QueryIdempotencyTokenAutoFillOperation::class,
            QueryListsOperation::class,
            QueryTimestampsOperation::class,
            RecursiveXmlShapesOperation::class,
            SimpleInputParamsOperation::class,
            SimpleScalarXmlPropertiesOperation::class,
            XmlBlobsOperation::class,
            XmlEmptyBlobsOperation::class,
            XmlEmptyListsOperation::class,
            XmlEnumsOperation::class,
            XmlIntEnumsOperation::class,
            XmlListsOperation::class,
            XmlNamespacesOperation::class,
            XmlTimestampsOperation::class
        ];
    }
}
