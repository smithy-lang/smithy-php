<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Generated\Waiter;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\GeneratedServiceTrait;
use Smithy\Core\Shape\ServiceInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Operation\GetThingOperation;

final class WaiterService implements ServiceInterface
{
    use GeneratedServiceTrait;

    private static ?Schema $SCHEMA = null;

    public static function name(): string
    {
        return 'Waiter';
    }

    public static function id(): string
    {
        return 'smithy.waiter#Waiter';
    }

    public static function version(): string
    {
        return '2026-08-04';
    }

    /**
     * Get the service shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'smithy.waiter#Waiter',
            type: ShapeType::Service,
            traitsById: ['smithy.protocols#rpcv2Cbor' => true]
        );
    }

    public static function operations(): array
    {
        return [
            GetThingOperation::class
        ];
    }
}
