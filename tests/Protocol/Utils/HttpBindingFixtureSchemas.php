<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

/**
 * Provide schema fixtures for HTTP binding tests.
 */
final class HttpBindingFixtureSchemas
{
    private static ?Schema $STRING = null;
    private static ?Schema $REQUEST = null;
    private static ?Schema $RESPONSE = null;
    private static ?Schema $PROVIDER_REQUEST = null;
    private static ?Schema $PAYLOAD_REQUEST = null;
    private static ?Schema $PAYLOAD_RESPONSE = null;
    private static ?Schema $STRUCTURE_PAYLOAD_REQUEST = null;
    private static ?Schema $STRUCTURE_PAYLOAD_RESPONSE = null;
    private static ?Schema $PAYLOAD_SHAPE = null;
    private static ?Schema $TIMESTAMP_REQUEST = null;
    private static ?Schema $TIMESTAMP_RESPONSE = null;
    private static ?Schema $OPERATION = null;
    private static ?Schema $HTTP_OPERATION = null;

    /**
     * Get the string schema fixture.
     */
    public static function string(): Schema
    {
        return self::$STRING ??= new Schema(
            id: 'smithy.test#Name',
            type: ShapeType::String_
        );
    }

    /**
     * Get the request schema fixture.
     */
    public static function request(): Schema
    {
        return self::$REQUEST ??= new Schema(
            id: 'smithy.test#Request',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Request$header',
                    type: ShapeType::String_,
                    memberName: 'header',
                    memberIndex: 0,
                    traits: [
                        Schema::T_HTTP_HEADER => 'X-Test',
                        Schema::T_MEDIA_TYPE => 'text/plain'
                    ]
                ),
                new Schema(
                    id: 'smithy.test#Request$mode',
                    type: ShapeType::Boolean,
                    memberName: 'mode',
                    memberIndex: 1,
                    traits: [Schema::T_HTTP_QUERY => 'mode']
                ),
                new Schema(
                    id: 'smithy.test#Request$id',
                    type: ShapeType::String_,
                    memberName: 'id',
                    memberIndex: 2,
                    traits: [Schema::T_HTTP_LABEL => true]
                ),
                new Schema(
                    id: 'smithy.test#Request$metadata',
                    type: ShapeType::Map,
                    memberName: 'metadata',
                    memberIndex: 3,
                    traits: [Schema::T_HTTP_PREFIX_HEADERS => 'x-meta-']
                ),
                new Schema(
                    id: 'smithy.test#Request$extra',
                    type: ShapeType::Map,
                    memberName: 'extra',
                    memberIndex: 4,
                    traits: [Schema::T_HTTP_QUERY_PARAMS => true]
                ),
                new Schema(
                    id: 'smithy.test#Request$body',
                    type: ShapeType::String_,
                    memberName: 'body',
                    memberIndex: 5
                )
            ],
            targetClass: HttpBindingInputShapeFixture::class
        );
    }

    /**
     * Get the response schema fixture.
     */
    public static function response(): Schema
    {
        return self::$RESPONSE ??= new Schema(
            id: 'smithy.test#Response',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Response$media',
                    type: ShapeType::String_,
                    memberName: 'media',
                    memberIndex: 0,
                    traits: [
                        Schema::T_HTTP_HEADER => 'X-Media',
                        Schema::T_MEDIA_TYPE => 'text/plain'
                    ]
                ),
                new Schema(
                    id: 'smithy.test#Response$count',
                    type: ShapeType::Integer,
                    memberName: 'count',
                    memberIndex: 1,
                    traits: [Schema::T_HTTP_HEADER => 'X-Count']
                ),
                new Schema(
                    id: 'smithy.test#Response$list',
                    type: ShapeType::List_,
                    memberName: 'list',
                    memberIndex: 2,
                    listMember: new Schema(
                        id: 'smithy.test#String',
                        type: ShapeType::String_
                    ),
                    traits: [Schema::T_HTTP_HEADER => 'X-List']
                ),
                new Schema(
                    id: 'smithy.test#Response$metadata',
                    type: ShapeType::Map,
                    memberName: 'metadata',
                    memberIndex: 3,
                    traits: [Schema::T_HTTP_PREFIX_HEADERS => 'X-Meta-']
                ),
                new Schema(
                    id: 'smithy.test#Response$status',
                    type: ShapeType::Integer,
                    memberName: 'status',
                    memberIndex: 4,
                    traits: [Schema::T_HTTP_RESPONSE_CODE => true]
                ),
                new Schema(
                    id: 'smithy.test#Response$body',
                    type: ShapeType::String_,
                    memberName: 'body',
                    memberIndex: 5
                ),
                new Schema(
                    id: 'smithy.test#Response$state',
                    type: ShapeType::Enum,
                    memberName: 'state',
                    memberIndex: 6,
                    traits: [Schema::T_HTTP_HEADER => 'X-State'],
                    targetClass: HttpBindingStringEnumFixture::class
                ),
                new Schema(
                    id: 'smithy.test#Response$priority',
                    type: ShapeType::IntEnum,
                    memberName: 'priority',
                    memberIndex: 7,
                    traits: [Schema::T_HTTP_HEADER => 'X-Priority'],
                    targetClass: HttpBindingIntEnumFixture::class
                )
            ],
            targetClass: HttpBindingOutputShapeFixture::class
        );
    }

    /**
     * Get the provider request schema fixture.
     */
    public static function providerRequest(): Schema
    {
        return self::$PROVIDER_REQUEST ??= new Schema(
            id: 'smithy.test#Request',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Request$timestamp',
                    type: ShapeType::Timestamp,
                    memberName: 'timestamp',
                    memberIndex: 0,
                    traits: [Schema::T_HTTP_HEADER => 'X-Test']
                ),
                new Schema(
                    id: 'smithy.test#Request$mode',
                    type: ShapeType::Boolean,
                    memberName: 'mode',
                    memberIndex: 1,
                    traits: [Schema::T_HTTP_QUERY => 'mode']
                ),
                new Schema(
                    id: 'smithy.test#Request$id',
                    type: ShapeType::Timestamp,
                    memberName: 'id',
                    memberIndex: 2,
                    traits: [Schema::T_HTTP_LABEL => true]
                ),
                new Schema(
                    id: 'smithy.test#Request$metadata',
                    type: ShapeType::Map,
                    memberName: 'metadata',
                    memberIndex: 3,
                    traits: [Schema::T_HTTP_PREFIX_HEADERS => 'X-Meta-']
                ),
                new Schema(
                    id: 'smithy.test#Request$extra',
                    type: ShapeType::Map,
                    memberName: 'extra',
                    memberIndex: 4,
                    traits: [Schema::T_HTTP_QUERY_PARAMS => true]
                ),
                new Schema(
                    id: 'smithy.test#Request$body',
                    type: ShapeType::String_,
                    memberName: 'body',
                    memberIndex: 5
                ),
                new Schema(
                    id: 'smithy.test#Request$status',
                    type: ShapeType::Integer,
                    memberName: 'status',
                    memberIndex: 6,
                    traits: [Schema::T_HTTP_RESPONSE_CODE => true]
                )
            ]
        );
    }

    /**
     * Get the payload request schema fixture.
     */
    public static function payloadRequest(): Schema
    {
        return self::$PAYLOAD_REQUEST ??= new Schema(
            id: 'smithy.test#PayloadRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#PayloadRequest$payload',
                    type: ShapeType::String_,
                    memberName: 'payload',
                    memberIndex: 0,
                    traits: [
                        Schema::T_HTTP_PAYLOAD => true,
                        Schema::T_MEDIA_TYPE => 'text/custom'
                    ]
                )
            ]
        );
    }

    /**
     * Get the payload response schema fixture.
     */
    public static function payloadResponse(): Schema
    {
        return self::$PAYLOAD_RESPONSE ??= new Schema(
            id: 'smithy.test#PayloadResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#PayloadResponse$payload',
                    type: ShapeType::String_,
                    memberName: 'payload',
                    memberIndex: 0,
                    traits: [Schema::T_HTTP_PAYLOAD => true]
                )
            ]
        );
    }

    /**
     * Get the structure payload request schema fixture.
     */
    public static function structurePayloadRequest(): Schema
    {
        return self::$STRUCTURE_PAYLOAD_REQUEST ??= new Schema(
            id: 'smithy.test#StructurePayloadRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#StructurePayloadRequest$payload',
                    type: ShapeType::Structure,
                    memberName: 'payload',
                    memberIndex: 0,
                    members: self::payloadShape()->getMembers(),
                    traits: [Schema::T_HTTP_PAYLOAD => true],
                    targetClass: HttpBindingPayloadShapeFixture::class
                )
            ]
        );
    }

    /**
     * Get the structure payload response schema fixture.
     */
    public static function structurePayloadResponse(): Schema
    {
        return self::$STRUCTURE_PAYLOAD_RESPONSE ??= new Schema(
            id: 'smithy.test#StructurePayloadResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#StructurePayloadResponse$payload',
                    type: ShapeType::Structure,
                    memberName: 'payload',
                    memberIndex: 0,
                    members: self::payloadShape()->getMembers(),
                    traits: [Schema::T_HTTP_PAYLOAD => true],
                    targetClass: HttpBindingPayloadShapeFixture::class
                )
            ]
        );
    }

    /**
     * Get the payload shape schema fixture.
     */
    public static function payloadShape(): Schema
    {
        return self::$PAYLOAD_SHAPE ??= new Schema(
            id: 'smithy.test#PayloadShape',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#PayloadShape$value',
                    type: ShapeType::String_,
                    memberName: 'value',
                    memberIndex: 0
                )
            ],
            targetClass: HttpBindingPayloadShapeFixture::class
        );
    }

    /**
     * Get the timestamp request schema fixture.
     */
    public static function timestampRequest(): Schema
    {
        return self::$TIMESTAMP_REQUEST ??= new Schema(
            id: 'smithy.test#TimestampRequest',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#TimestampRequest$createdAt',
                    type: ShapeType::Timestamp,
                    memberName: 'createdAt',
                    memberIndex: 0
                )
            ]
        );
    }

    /**
     * Get the timestamp response schema fixture.
     */
    public static function timestampResponse(): Schema
    {
        return self::$TIMESTAMP_RESPONSE ??= new Schema(
            id: 'smithy.test#TimestampResponse',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#TimestampResponse$createdAt',
                    type: ShapeType::Timestamp,
                    memberName: 'createdAt',
                    memberIndex: 0
                )
            ]
        );
    }

    /**
     * Get the operation schema fixture.
     */
    public static function operation(): Schema
    {
        return self::$OPERATION ??= new Schema(
            id: 'smithy.test#GetThing',
            type: ShapeType::Operation
        );
    }

    /**
     * Get the HTTP operation schema fixture.
     */
    public static function httpOperation(): Schema
    {
        return self::$HTTP_OPERATION ??= new Schema(
            id: 'smithy.test#GetThing',
            type: ShapeType::Operation,
            traits: [
                Schema::T_HTTP => [
                    'method' => 'GET',
                    'uri' => '/things/{id}?fixed=true',
                    'pathSegments' => [
                        ['/things/', null, false],
                        [null, 0, false]
                    ],
                    'pathQueryPairs' => [['fixed', 'true']],
                    'code' => 206
                ]
            ]
        );
    }
}
