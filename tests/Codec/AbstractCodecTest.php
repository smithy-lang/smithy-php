<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codec;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Codec\AbstractCodec;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Tests\Codec\Utils\CodecFixture;

#[CoversClass(AbstractCodec::class)]
final class AbstractCodecTest extends TestCase
{
    public function testCreatesEmptyMembersBuffer(): void
    {
        $schema = new Schema(
            id: 'smithy.test#Empty',
            type: ShapeType::Structure
        );

        self::assertSame(
            [],
            (new CodecFixture())->createMembersBuffer($schema)
        );
    }

    public function testCreatesDenseMembersBuffer(): void
    {
        $schema = new Schema(
            id: 'smithy.test#Input',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.test#Input$first',
                    type: ShapeType::String_,
                    memberName: 'first',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.test#Input$second',
                    type: ShapeType::Integer,
                    memberName: 'second',
                    memberIndex: 1
                )
            ]
        );

        self::assertSame(
            [null, null],
            (new CodecFixture())->createMembersBuffer($schema)
        );
    }
}
