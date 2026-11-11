<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsJson1_0;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsJson1_0\Operation\ContentTypeParametersOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\JsonUnionsOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\QueryIncompatibleOperationOperation;
use Smithy\ProtocolTests\AwsJson1_0\Operation\SimpleScalarPropertiesOperation;

final class AwsJson1_0Service implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'AwsJson1_0';
    }

    public static function id(): string
    {
        return 'aws.protocoltests.json10#JsonRpc10';
    }

    public static function version(): string
    {
        return '2020-07-14';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'aws.protocoltests.json10#JsonRpc10',
            type: ShapeType::Service,
            traitsById: [
                'aws.api#service' => ['sdkId' => 'JSON RPC 10'],
                'aws.auth#sigv4' => ['name' => 'jsonrpc10'],
                'aws.protocols#awsJson1_0' => [],
                'smithy.api#title' => 'Sample Json 1.0 Protocol Service'
            ]
        );
    }

    public static function operations(): array
    {
        return [
            ContentTypeParametersOperation::class,
            EmptyInputAndEmptyOutputOperation::class,
            EndpointOperationOperation::class,
            EndpointWithHostLabelOperationOperation::class,
            GreetingWithErrorsOperation::class,
            HostWithPathOperationOperation::class,
            JsonUnionsOperation::class,
            NoInputAndNoOutputOperation::class,
            NoInputAndOutputOperation::class,
            PutWithContentEncodingOperation::class,
            QueryIncompatibleOperationOperation::class,
            SimpleScalarPropertiesOperation::class
        ];
    }
}
