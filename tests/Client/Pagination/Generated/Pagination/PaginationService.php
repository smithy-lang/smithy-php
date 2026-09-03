<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination;

use Smithy\Core\Shape\ServiceInterface;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Operation\{
    ListMapsOperation,
    ListStringsOperation,
    ListWithoutPageSizeOperation
};
final class PaginationService implements ServiceInterface
{
    use \Smithy\Core\Shape\GeneratedServiceTrait;
    public static function name(): string
    {
        return 'Pagination';
    }

    public static function id(): string
    {
        return 'smithy.pagination#Pagination';
    }

    public static function version(): string
    {
        return '2026-08-03';
    }

    public static function operations(): array
    {
        return [
            ListMapsOperation::class,
            ListStringsOperation::class,
            ListWithoutPageSizeOperation::class
        ];
    }
}
