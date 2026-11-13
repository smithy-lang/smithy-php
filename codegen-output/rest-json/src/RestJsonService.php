<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestJson;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestJson\Operation\AllQueryStringTypesOperation;
use Smithy\ProtocolTests\RestJson\Operation\ConstantAndVariableQueryStringOperation;
use Smithy\ProtocolTests\RestJson\Operation\ConstantQueryStringOperation;
use Smithy\ProtocolTests\RestJson\Operation\ContentTypeParametersOperation;
use Smithy\ProtocolTests\RestJson\Operation\DatetimeOffsetsOperation;
use Smithy\ProtocolTests\RestJson\Operation\DocumentTypeAsMapValueOperation;
use Smithy\ProtocolTests\RestJson\Operation\DocumentTypeAsPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\DocumentTypeOperation;
use Smithy\ProtocolTests\RestJson\Operation\DuplexStreamOperation;
use Smithy\ProtocolTests\RestJson\Operation\DuplexStreamWithInitialMessagesOperation;
use Smithy\ProtocolTests\RestJson\Operation\EmptyInputAndEmptyOutputOperation;
use Smithy\ProtocolTests\RestJson\Operation\EndpointOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\EndpointWithHostLabelOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\FractionalSecondsOperation;
use Smithy\ProtocolTests\RestJson\Operation\GreetingWithErrorsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HostWithPathOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpChecksumRequiredOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpEmptyPrefixHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpEnumPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPayloadTraitsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPayloadWithStructureOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPayloadWithUnionOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPrefixHeadersInResponseOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpPrefixHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpQueryParamsOnlyOperationOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithFloatLabelsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithGreedyLabelInPathOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithLabelsAndTimestampFormatOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithLabelsOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpRequestWithRegexLiteralOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpResponseCodeOperation;
use Smithy\ProtocolTests\RestJson\Operation\HttpStringPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\IgnoreQueryParamsInResponseOperation;
use Smithy\ProtocolTests\RestJson\Operation\InputAndOutputWithHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\InputStreamOperation;
use Smithy\ProtocolTests\RestJson\Operation\InputStreamWithInitialRequestOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonBlobsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonEnumsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonIntEnumsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonListsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonMapsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonTimestampsOperation;
use Smithy\ProtocolTests\RestJson\Operation\JsonUnionsOperation;
use Smithy\ProtocolTests\RestJson\Operation\MediaTypeHeaderOperation;
use Smithy\ProtocolTests\RestJson\Operation\NoInputAndNoOutputOperation;
use Smithy\ProtocolTests\RestJson\Operation\NoInputAndOutputOperation;
use Smithy\ProtocolTests\RestJson\Operation\NullAndEmptyHeadersClientOperation;
use Smithy\ProtocolTests\RestJson\Operation\NullAndEmptyHeadersServerOperation;
use Smithy\ProtocolTests\RestJson\Operation\OmitsNullSerializesEmptyStringOperation;
use Smithy\ProtocolTests\RestJson\Operation\OmitsSerializingEmptyListsOperation;
use Smithy\ProtocolTests\RestJson\Operation\OutputStreamOperation;
use Smithy\ProtocolTests\RestJson\Operation\OutputStreamWithInitialResponseOperation;
use Smithy\ProtocolTests\RestJson\Operation\PostUnionWithJsonNameOperation;
use Smithy\ProtocolTests\RestJson\Operation\PutWithContentEncodingOperation;
use Smithy\ProtocolTests\RestJson\Operation\QueryIdempotencyTokenAutoFillOperation;
use Smithy\ProtocolTests\RestJson\Operation\QueryParamsAsStringListMapOperation;
use Smithy\ProtocolTests\RestJson\Operation\QueryPrecedenceOperation;
use Smithy\ProtocolTests\RestJson\Operation\RecursiveShapesOperation;
use Smithy\ProtocolTests\RestJson\Operation\ResponseCodeHttpFallbackOperation;
use Smithy\ProtocolTests\RestJson\Operation\ResponseCodeRequiredOperation;
use Smithy\ProtocolTests\RestJson\Operation\SimpleScalarPropertiesOperation;
use Smithy\ProtocolTests\RestJson\Operation\SparseJsonListsOperation;
use Smithy\ProtocolTests\RestJson\Operation\SparseJsonMapsOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestBodyStructureOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestGetNoInputNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestGetNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPayloadBlobOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPayloadStructureOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPostNoInputNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TestPostNoPayloadOperation;
use Smithy\ProtocolTests\RestJson\Operation\TimestampFormatHeadersOperation;
use Smithy\ProtocolTests\RestJson\Operation\UnitInputAndOutputOperation;

final class RestJsonService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'RestJson';
    }

    public static function id(): string
    {
        return 'aws.protocoltests.restjson#RestJson';
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
            id: 'aws.protocoltests.restjson#RestJson',
            type: ShapeType::Service,
            traitsById: [
                'aws.api#service' => ['sdkId' => 'Rest Json Protocol'],
                'aws.auth#sigv4' => ['name' => 'restjson'],
                'aws.protocols#restJson1' => [],
                'smithy.api#title' => 'Sample Rest Json Protocol Service'
            ]
        );
    }

    public static function operations(): array
    {
        return [
            AllQueryStringTypesOperation::class,
            ConstantAndVariableQueryStringOperation::class,
            ConstantQueryStringOperation::class,
            ContentTypeParametersOperation::class,
            DatetimeOffsetsOperation::class,
            DocumentTypeOperation::class,
            DocumentTypeAsMapValueOperation::class,
            DocumentTypeAsPayloadOperation::class,
            DuplexStreamOperation::class,
            DuplexStreamWithInitialMessagesOperation::class,
            EmptyInputAndEmptyOutputOperation::class,
            EndpointOperationOperation::class,
            EndpointWithHostLabelOperationOperation::class,
            FractionalSecondsOperation::class,
            GreetingWithErrorsOperation::class,
            HostWithPathOperationOperation::class,
            HttpChecksumRequiredOperation::class,
            HttpEmptyPrefixHeadersOperation::class,
            HttpEnumPayloadOperation::class,
            HttpPayloadTraitsOperation::class,
            HttpPayloadWithStructureOperation::class,
            HttpPayloadWithUnionOperation::class,
            HttpPrefixHeadersOperation::class,
            HttpPrefixHeadersInResponseOperation::class,
            HttpQueryParamsOnlyOperationOperation::class,
            HttpRequestWithFloatLabelsOperation::class,
            HttpRequestWithGreedyLabelInPathOperation::class,
            HttpRequestWithLabelsOperation::class,
            HttpRequestWithLabelsAndTimestampFormatOperation::class,
            HttpRequestWithRegexLiteralOperation::class,
            HttpResponseCodeOperation::class,
            HttpStringPayloadOperation::class,
            IgnoreQueryParamsInResponseOperation::class,
            InputAndOutputWithHeadersOperation::class,
            InputStreamOperation::class,
            InputStreamWithInitialRequestOperation::class,
            JsonBlobsOperation::class,
            JsonEnumsOperation::class,
            JsonIntEnumsOperation::class,
            JsonListsOperation::class,
            JsonMapsOperation::class,
            JsonTimestampsOperation::class,
            JsonUnionsOperation::class,
            MediaTypeHeaderOperation::class,
            NoInputAndNoOutputOperation::class,
            NoInputAndOutputOperation::class,
            NullAndEmptyHeadersClientOperation::class,
            NullAndEmptyHeadersServerOperation::class,
            OmitsNullSerializesEmptyStringOperation::class,
            OmitsSerializingEmptyListsOperation::class,
            OutputStreamOperation::class,
            OutputStreamWithInitialResponseOperation::class,
            PostUnionWithJsonNameOperation::class,
            PutWithContentEncodingOperation::class,
            QueryIdempotencyTokenAutoFillOperation::class,
            QueryParamsAsStringListMapOperation::class,
            QueryPrecedenceOperation::class,
            RecursiveShapesOperation::class,
            ResponseCodeHttpFallbackOperation::class,
            ResponseCodeRequiredOperation::class,
            SimpleScalarPropertiesOperation::class,
            SparseJsonListsOperation::class,
            SparseJsonMapsOperation::class,
            TestBodyStructureOperation::class,
            TestGetNoInputNoPayloadOperation::class,
            TestGetNoPayloadOperation::class,
            TestPayloadBlobOperation::class,
            TestPayloadStructureOperation::class,
            TestPostNoInputNoPayloadOperation::class,
            TestPostNoPayloadOperation::class,
            TimestampFormatHeadersOperation::class,
            UnitInputAndOutputOperation::class
        ];
    }
}
