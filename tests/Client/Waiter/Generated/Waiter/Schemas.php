<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Generated\Waiter;

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
    public static Schema $GetThingInput;
    public static Schema $GetThingOutput;

    public static function getThingInput(): Schema
    {
        if (isset(self::$GetThingInput)) {
            return self::$GetThingInput;
        }

        return self::$GetThingInput = new Schema(
            id: 'smithy.waiter#GetThingInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'thingId', memberIndex: 0, traits: [null, null, null, null, null, null, null, null, null, null, null, null, null, null, true])
            ],
            targetClass: \Smithy\Tests\Client\Waiter\Generated\Waiter\Input\GetThingInput::class
        );
    }

    public static function getThingOutput(): Schema
    {
        if (isset(self::$GetThingOutput)) {
            return self::$GetThingOutput;
        }

        return self::$GetThingOutput = new Schema(
            id: 'smithy.waiter#GetThingOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'status', memberIndex: 0),
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'failureReason', memberIndex: 1)
            ],
            targetClass: \Smithy\Tests\Client\Waiter\Generated\Waiter\Output\GetThingOutput::class
        );
    }

}
