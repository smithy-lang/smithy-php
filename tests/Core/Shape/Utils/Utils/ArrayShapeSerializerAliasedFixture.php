<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;


final class ArrayShapeSerializerAliasedFixture
    extends AbstractArrayShapeSerializerFixture
{
    private static Schema $SCHEMA;

    private readonly ?string $modeledMessage;

    /**
     * Create an aliased-member fixture.
     */
    public function __construct(?string $message = null)
    {
        $this->modeledMessage = $message;
    }

    /**
     * Return the fixture schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'test#ArrayShapeSerializerAliasedFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'Message',
                    memberIndex: 0
                )
            ],
            targetClass: self::class
        );
    }

    /** @inheritDoc */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->modeledMessage,
            default => null
        };
    }
}
