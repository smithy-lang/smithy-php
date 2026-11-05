<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface,
    ShapeSerializerInterface
};
use Smithy\Core\Shape\{
    FromMembersTrait,
    ShapeType
};

final class IdempotentTestInputFixture implements SerializableStructInterface
{
    use FromMembersTrait;

    private static ?Schema $schema = null;

    public function __construct(
        public readonly ?string $value = null,
        public readonly ?string $token = null
    ) {}

    public static function schema(): Schema
    {
        return self::$schema ??= new Schema(
            id: 'smithy.tests#IdempotentTestInputFixture',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'Value',
                    memberIndex: 0
                ),
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'Token',
                    memberIndex: 1,
                    traits: [Schema::T_IDEMPOTENCY_TOKEN => true]
                )
            ]
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public function serialize(ShapeSerializerInterface $serializer, int $idx): void {}

    public function serializeMembers(ShapeSerializerInterface $serializer): void {}

    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->value,
            1 => $this->token,
            default => null
        };
    }
}
