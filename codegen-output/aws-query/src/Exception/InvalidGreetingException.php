<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\AwsQuery\Exception;

use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Serde\ShapeSerializerInterface;
use Smithy\Core\Shape\FromMembersTrait;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\AwsQuery\Schemas;

/**
 * This error is thrown when an invalid greeting value is provided.
 */
final class InvalidGreetingException extends AwsQueryException implements SerializableStructInterface
{
    use FromMembersTrait;

    public const SHAPE_ID = 'aws.protocoltests.query#InvalidGreeting';

    private static Schema $SCHEMA;
    private static Schema $SCHEMA_MESSAGE;

    private readonly ?string $modeledMessage;

    public function __construct(
        ?string $message = null
    ) {
        parent::__construct($message ?? '', ErrorFault::Client);
        $this->modeledMessage = $message;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        if (isset(self::$SCHEMA)) {
            return self::$SCHEMA;
        }
        $s = Schemas::invalidGreetingException();
        $m = $s->getMembersByIndex();
        self::$SCHEMA_MESSAGE = $m[0];
        return self::$SCHEMA = $s;
    }

    /**
     * Get the shape schema for this instance.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /**
     * Serialize the shape to the desired wire format.
     *
     * @return void
     */
    public function serialize(ShapeSerializerInterface $serializer, int $idx): void
    {
        $serializer->serializeStruct(self::schema(), $this, $idx);
    }

    /**
     * Serialize the shape members to the desired wire format.
     *
     * @return void
     */
    public function serializeMembers(ShapeSerializerInterface $serializer): void
    {
        if (isset($this->modeledMessage)) {
            $serializer->serializeString(self::$SCHEMA_MESSAGE, $this->modeledMessage, 0);
        }
    }

    /**
     * Get a modeled member value.
     */
    public function getMemberValue(Schema $member): mixed
    {
        return match ($member->memberIndex) {
            0 => $this->modeledMessage,
            default => null,
        };
    }

}
