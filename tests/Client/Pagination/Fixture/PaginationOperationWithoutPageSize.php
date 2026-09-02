<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Fixture;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
final class PaginationOperationWithoutPageSize extends PaginationOperation
{
    public function __construct() {}

    public function getId(): string
    {
        return 'smithy.tests#PaginateWithoutPageSize';
    }

    public static function schema(): Schema
    {
        return new Schema(
            id: 'smithy.tests#PaginateWithoutPageSize',
            type: ShapeType::Operation,
            traits: [
                Schema::T_PAGINATED => [
                    'inputToken' => 'nextToken',
                    'outputToken' => 'result.nextToken',
                    'items' => 'result.items'
                ]
            ]
        );
    }
}
