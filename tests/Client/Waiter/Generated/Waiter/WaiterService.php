<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Waiter\Generated\Waiter;

use Smithy\Core\Shape\ServiceInterface;
use Smithy\Tests\Client\Waiter\Generated\Waiter\Operation\GetThingOperation;
final class WaiterService implements ServiceInterface
{
    use \Smithy\Core\Shape\GeneratedServiceTrait;
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

    public static function operations(): array
    {
        return [
            GetThingOperation::class
        ];
    }
}
