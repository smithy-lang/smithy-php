<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Serde\CompositeTypeRegistry;
use Smithy\Core\Serde\TypeRegistryInterface;
use Smithy\Core\Shape\Document\MapDocument;
use Smithy\ProtocolTests\RestJson\RestJsonTypeRegistry;

final class GeneratedTypeRegistryTest extends TestCase
{
    public function testGeneratedRegistryDeserializesDocumentIntoGeneratedShape(): void
    {
        $registry = new RestJsonTypeRegistry();
        $shapeId = \array_key_first($registry->all());
        self::assertIsString($shapeId);
        $class = $registry->classFor($shapeId);
        self::assertNotNull($class);

        $shape = $registry->deserialize(
            new MapDocument([], null, $shapeId)
        );

        self::assertInstanceOf(TypeRegistryInterface::class, $registry);
        self::assertInstanceOf($class, $shape);
        self::assertSame($registry->schemaFor($shapeId), $class::schema());
    }

    public function testGeneratedRegistryComposes(): void
    {
        $registry = new RestJsonTypeRegistry();
        $shapeId = \array_key_first($registry->all());
        self::assertIsString($shapeId);

        $composed = CompositeTypeRegistry::compose(
            new \Smithy\ProtocolTests\AwsQuery\AwsQueryTypeRegistry(),
            $registry
        );

        self::assertSame($registry->classFor($shapeId), $composed->classFor($shapeId));
    }
}
