<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_1;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_1\Operation\ContentTypeParametersOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\EmptyOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\JsonEnumsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\JsonIntEnumsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\JsonUnionsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\KitchenSinkOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\NullOperationOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\OperationWithOptionalInputOutputOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\PutAndGetInlineDocumentsOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\AwsJson1_1\Operation\SparseNullsOperationOperation;

final class AwsJson1_1Service implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'AwsJson1_1';
    }

    public static function id(): string
    {
        return 'aws.protocoltests.json#JsonProtocol';
    }

    public static function version(): string
    {
        return '2018-01-01';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'aws.protocoltests.json#JsonProtocol',
            type: ShapeType::Service,
            traitsById: [
                'aws.api#service' => ['sdkId' => 'Json Protocol', 'arnNamespace' => 'jsonprotocol', 'cloudFormationName' => 'JsonProtocol', 'cloudTrailEventSource' => 'jsonprotocol.amazonaws.com'],
                'aws.auth#sigv4' => ['name' => 'jsonprotocol'],
                'aws.protocols#awsJson1_1' => [],
                'smithy.api#title' => 'Sample Json 1.1 Protocol Service'
            ]
        );
    }

    /**
     * @return list<class-string>
     */
    public static function operations(): array
    {
        return [
            ContentTypeParametersOperation::class,
            DatetimeOffsetsOperation::class,
            EmptyOperationOperation::class,
            EndpointOperationOperation::class,
            EndpointWithHostLabelOperationOperation::class,
            FractionalSecondsOperation::class,
            GreetingWithErrorsOperation::class,
            HostWithPathOperationOperation::class,
            JsonEnumsOperation::class,
            JsonIntEnumsOperation::class,
            JsonUnionsOperation::class,
            KitchenSinkOperationOperation::class,
            NullOperationOperation::class,
            OperationWithOptionalInputOutputOperation::class,
            PutAndGetInlineDocumentsOperation::class,
            PutWithContentEncodingOperation::class,
            SimpleScalarPropertiesOperation::class,
            SparseNullsOperationOperation::class
        ];
    }
}
