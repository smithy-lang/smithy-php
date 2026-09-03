<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
/**
 * Centralized schema registry for all shapes in this service.
 *
 * Contains all Schema objects as public static properties with lazy initialization.
 * Each structure has a dedicated init method that populates its member schemas.
 */
final class Schemas
{
    public static Schema $ListMapsInput;
    public static Schema $ListMapsOutput;
    public static Schema $ListStringsInput;
    public static Schema $ListStringsOutput;
    public static Schema $ListWithoutPageSizeInput;
    public static Schema $ListWithoutPageSizeOutput;

    public static function listMapsInput(): Schema
    {
        if (isset(self::$ListMapsInput)) {
            return self::$ListMapsInput;
        }

        return self::$ListMapsInput = new Schema(
            id: 'smithy.pagination#ListMapsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.pagination#PaginationTokenMap', type: ShapeType::Map, memberName: 'exclusiveStartKey', memberIndex: 0, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_)),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'limit', memberIndex: 1)
            ],
            targetClass: \Smithy\Tests\Client\Pagination\Generated\Pagination\Input\ListMapsInput::class
        );
    }

    public static function listMapsOutput(): Schema
    {
        if (isset(self::$ListMapsOutput)) {
            return self::$ListMapsOutput;
        }

        return self::$ListMapsOutput = new Schema(
            id: 'smithy.pagination#ListMapsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.pagination#Items', type: ShapeType::List_, memberName: 'items', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_)),
                new Schema(id: 'smithy.pagination#PaginationTokenMap', type: ShapeType::Map, memberName: 'lastEvaluatedKey', memberIndex: 1, mapKey: new Schema(id: 'smithy.api#String', type: ShapeType::String_), mapValue: new Schema(id: 'smithy.api#String', type: ShapeType::String_))
            ],
            targetClass: \Smithy\Tests\Client\Pagination\Generated\Pagination\Output\ListMapsOutput::class
        );
    }

    public static function listStringsInput(): Schema
    {
        if (isset(self::$ListStringsInput)) {
            return self::$ListStringsInput;
        }

        return self::$ListStringsInput = new Schema(
            id: 'smithy.pagination#ListStringsInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'nextToken', memberIndex: 0),
                new Schema(id: 'smithy.api#Integer', type: ShapeType::Integer, memberName: 'pageSize', memberIndex: 1)
            ],
            targetClass: \Smithy\Tests\Client\Pagination\Generated\Pagination\Input\ListStringsInput::class
        );
    }

    public static function listStringsOutput(): Schema
    {
        if (isset(self::$ListStringsOutput)) {
            return self::$ListStringsOutput;
        }

        return self::$ListStringsOutput = new Schema(
            id: 'smithy.pagination#ListStringsOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.pagination#Items', type: ShapeType::List_, memberName: 'items', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_)),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'nextToken', memberIndex: 1)
            ],
            targetClass: \Smithy\Tests\Client\Pagination\Generated\Pagination\Output\ListStringsOutput::class
        );
    }

    public static function listWithoutPageSizeInput(): Schema
    {
        if (isset(self::$ListWithoutPageSizeInput)) {
            return self::$ListWithoutPageSizeInput;
        }

        return self::$ListWithoutPageSizeInput = new Schema(
            id: 'smithy.pagination#ListWithoutPageSizeInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'cursor', memberIndex: 0)
            ],
            targetClass: \Smithy\Tests\Client\Pagination\Generated\Pagination\Input\ListWithoutPageSizeInput::class
        );
    }

    public static function listWithoutPageSizeOutput(): Schema
    {
        if (isset(self::$ListWithoutPageSizeOutput)) {
            return self::$ListWithoutPageSizeOutput;
        }

        return self::$ListWithoutPageSizeOutput = new Schema(
            id: 'smithy.pagination#ListWithoutPageSizeOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.pagination#Items', type: ShapeType::List_, memberName: 'items', memberIndex: 0, listMember: new Schema(id: 'smithy.api#String', type: ShapeType::String_)),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'cursor', memberIndex: 1)
            ],
            targetClass: \Smithy\Tests\Client\Pagination\Generated\Pagination\Output\ListWithoutPageSizeOutput::class
        );
    }

}
