<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws\RestXml;

use Smithy\Auth\NoAuthScheme;
use Smithy\Client\Auth\StaticAuthSchemeResolver;
use Smithy\Client\ClientInterface;
use Smithy\Client\Dynamic\{
    DynamicClient,
    DynamicOperation
};
use Smithy\Client\Plugin\IdempotencyTokenPlugin;
use Smithy\Core\Model\SmithyModel;
use Smithy\Core\Serde\{
    Schema,
    SerializableStructInterface
};
use Smithy\Core\Shape\Document\BlobValue;
use Smithy\Protocol\Aws\AwsDocumentException;
use Smithy\Core\Shape\{
    BigDecimal,
    BigInteger,
    OperationInterface,
    ShapeType
};
use Smithy\Protocol\Aws\RestXmlProtocol;
use Smithy\Tests\Protocol\Aws\RestXml\Support\RestXmlClientProtocolTestCase;
use Smithy\Tests\Utils\Http\MockHttpClient;

/**
 * Run the official Rest XML client tests against runtime-loaded code.
 */
final class DynamicClientProtocolTest extends RestXmlClientProtocolTestCase
{
    private static ?SmithyModel $model = null;

    protected function createClient(
        MockHttpClient $http,
        string $endpoint
    ): ClientInterface
    {
        return DynamicClient::create(
            model: self::$model ??= SmithyModel::fromFile(
                self::MODEL_PATH
            ),
            protocols: [
                RestXmlProtocol::ID => new RestXmlProtocol()
            ],
            protocolPreference: [RestXmlProtocol::ID],
            httpClient: $http,
            endpoint: $endpoint,
            supportedAuthSchemes: [NoAuthScheme::instance()],
            authSchemeResolver: StaticAuthSchemeResolver::noAuth(),
            context: [
                IdempotencyTokenPlugin::CONFIG_IDEMPOTENCY_TOKEN_PROVIDER
                    => '00000000-0000-4000-8000-000000000000'
            ]
        );
    }

    protected function resolveOperation(
        ClientInterface $client,
        string $operationId
    ): OperationInterface
    {
        self::assertInstanceOf(DynamicClient::class, $client);
        $operation = $client->getOperation(self::shapeName($operationId));
        self::assertInstanceOf(DynamicOperation::class, $operation);

        return $operation;
    }

    protected function createInput(
        OperationInterface $operation,
        array $params
    ): SerializableStructInterface
    {
        self::assertInstanceOf(DynamicOperation::class, $operation);

        return $operation->createInputFromValue(
            self::normalizeFixtureValue(
                $operation->getInputSchema(),
                $params
            )
        );
    }

    protected function expectModeledError(
        \Throwable $error
    ): SerializableStructInterface
    {
        self::assertInstanceOf(AwsDocumentException::class, $error);
        self::assertNotNull($error->getAwsErrorCode());
        $error->setExtendedRequestId('host-id');
        self::assertSame('host-id', $error->getExtendedRequestId());

        return $error;
    }

    protected function shouldSkipOperation(
        OperationInterface $operation
    ): bool
    {
        return $operation->getInputStreamMember() !== null
            || $operation->getOutputStreamMember() !== null;
    }

    private static function normalizeFixtureValue(
        Schema $schema,
        mixed $value
    ): mixed
    {
        if ($value === null) {
            return null;
        }

        $target = $schema->resolveTarget();

        return match ($target->type) {
            ShapeType::Float_->value,
            ShapeType::Double->value => self::normalizeFloat($value),
            ShapeType::Blob->value => \is_string($value)
                ? new BlobValue($value)
                : $value,
            ShapeType::BigInteger->value => $value instanceof BigInteger
                ? $value
                : new BigInteger((string) $value),
            ShapeType::BigDecimal->value => $value instanceof BigDecimal
                ? $value
                : new BigDecimal((string) $value),
            ShapeType::Structure->value,
            ShapeType::Union->value => self::normalizeMembers(
                $target,
                $value
            ),
            ShapeType::List_->value => self::normalizeList($target, $value),
            ShapeType::Map->value => self::normalizeMap($target, $value),
            default => $value
        };
    }

    private static function normalizeMembers(
        Schema $schema,
        mixed $value
    ): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        foreach ($value as $name => $memberValue) {
            if (!\is_string($name)) {
                continue;
            }

            $member = $schema->getMember($name);
            if ($member !== null) {
                $value[$name] = self::normalizeFixtureValue(
                    $member,
                    $memberValue
                );
            }
        }

        return $value;
    }

    private static function normalizeList(
        Schema $schema,
        mixed $value
    ): mixed
    {
        $member = $schema->getListMember();
        if (!\is_array($value) || $member === null) {
            return $value;
        }

        foreach ($value as $index => $memberValue) {
            $value[$index] = self::normalizeFixtureValue(
                $member,
                $memberValue
            );
        }

        return $value;
    }

    private static function normalizeMap(
        Schema $schema,
        mixed $value
    ): mixed
    {
        $member = $schema->getMapValue();
        if (!\is_array($value) || $member === null) {
            return $value;
        }

        foreach ($value as $key => $memberValue) {
            $value[$key] = self::normalizeFixtureValue(
                $member,
                $memberValue
            );
        }

        return $value;
    }

    private static function normalizeFloat(mixed $value): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }

        return match ($value) {
            'NaN' => \NAN,
            'Infinity' => \INF,
            '-Infinity' => -\INF,
            default => $value
        };
    }

    private static function shapeName(string $shapeId): string
    {
        $separator = \strrpos($shapeId, '#');

        return $separator === false
            ? $shapeId
            : \substr($shapeId, $separator + 1);
    }
}
