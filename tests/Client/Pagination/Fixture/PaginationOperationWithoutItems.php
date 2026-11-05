<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Fixture;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
final class PaginationOperationWithoutItems extends PaginationOperation
{
    public function __construct() {}

    public function getId(): string
    {
        return 'smithy.tests#PaginateWithoutItems';
    }

    public static function schema(): Schema
    {
        return new Schema(
            id: 'smithy.tests#PaginateWithoutItems',
            type: ShapeType::Operation,
            traits: [
                Schema::T_PAGINATED => [
                    'inputToken' => 'nextToken',
                    'outputToken' => 'result.nextToken',
                    'pageSize' => 'maxResults'
                ]
            ]
        );
    }
}
