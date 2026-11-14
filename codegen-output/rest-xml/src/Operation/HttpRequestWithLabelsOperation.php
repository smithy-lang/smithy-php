<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\ProtocolTests\RestXml\Operation;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
use Smithy\Core\Shape\ShapeType;
use Smithy\ProtocolTests\RestXml\Exception\RestXmlException;
use Smithy\ProtocolTests\RestXml\Input\HttpRequestWithLabelsInput;
use Smithy\ProtocolTests\RestXml\Shape\Unit;

/**
 * The example tests how requests are serialized when there's no input payload but there are HTTP labels.
 */
final class HttpRequestWithLabelsOperation implements OperationInterface
{
    public const NAME = 'HttpRequestWithLabels';
    public const ID = 'aws.protocoltests.restxml#HttpRequestWithLabels';
    public const SERVICE_ID = 'aws.protocoltests.restxml#RestXml';
    public const INPUT_CLASS = HttpRequestWithLabelsInput::class;
    public const OUTPUT_CLASS = Unit::class;
    public const AUTH_SCHEMES = [
        'aws.auth#sigv4'
    ];
    public const ERRORS = [];

    private static ?self $INSTANCE = null;
    private static ?Schema $SCHEMA = null;

    public static function instance(): self
    {
        return self::$INSTANCE ??= new self();
    }

    private function __construct() {}

    public function getName(): string
    {
        return self::NAME;
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getServiceId(): ?string
    {
        return self::SERVICE_ID;
    }

    public function getServiceExceptionClass(): string
    {
        return RestXmlException::class;
    }

    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

    public function getEffectiveAuthSchemes(): array
    {
        return self::AUTH_SCHEMES;
    }

    /**
     * Get the shape schema.
     */
    public static function schema(): Schema
    {
        return self::$SCHEMA ??= new Schema(
            id: 'aws.protocoltests.restxml#HttpRequestWithLabels',
            type: ShapeType::Operation,
            traits: [
                Schema::T_HTTP => [
                    'method' => 'GET',
                    'uri' => '/HttpRequestWithLabels/{string}/{short}/{integer}/{long}/{float}/{double}/{boolean}/{timestamp}',
                    'pathSegments' => [['/HttpRequestWithLabels/', null, false], [null, 0, false], ['/', null, false], [null, 1, false], ['/', null, false], [null, 2, false], ['/', null, false], [null, 3, false], ['/', null, false], [null, 4, false], ['/', null, false], [null, 5, false], ['/', null, false], [null, 6, false], ['/', null, false], [null, 7, false]]
                ],
                Schema::T_READ_ONLY => true
            ]
        );
    }

    public function getSchema(): Schema
    {
        return self::schema();
    }

    public static function inputSchema(): Schema
    {
        return HttpRequestWithLabelsInput::schema();
    }

    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    public static function outputSchema(): Schema
    {
        return Unit::schema();
    }

    public function getOutputSchema(): Schema
    {
        return self::outputSchema();
    }

    /**
     * @return list<Schema> Modeled error schemas.
     */
    public function getErrorSchemas(): array
    {
        return [];
    }

    public function createInput(array $members): SerializableStructInterface
    {
        return HttpRequestWithLabelsInput::fromMembers($members);
    }

    public function createOutput(array $members): SerializableStructInterface
    {
        return Unit::fromMembers($members);
    }

    public function getRequestTarget(): ?string
    {
        return null;
    }

    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        return null;
    }

    public function getInputStreamMember(): ?Schema
    {
        return null;
    }

    public function getOutputStreamMember(): ?Schema
    {
        return null;
    }
}
