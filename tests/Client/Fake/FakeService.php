<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Fake;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\{
    OperationInterface,
    ServiceInterface,
    ShapeType
};

/**
 * Minimal service metadata for client configuration tests.
 */
final class FakeService implements ServiceInterface
{
    private static ?self $instance = null;

    private static ?Schema $schema = null;

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function getName(): string
    {
        return 'Fake';
    }

    public function getId(): string
    {
        return 'smithy.tests#FakeService';
    }

    public function getVersion(): string
    {
        return '1';
    }

    public function getSchema(): Schema
    {
        return self::$schema ??= new Schema(
            id: $this->getId(),
            type: ShapeType::Service,
            traitsById: [FakeProtocol::ID => true]
        );
    }

    public function getOperations(): array
    {
        return [FakeOperation::instance()];
    }

    public function getOperation(string $name): ?OperationInterface
    {
        return \strcasecmp($name, FakeOperation::instance()->getName()) === 0
            ? FakeOperation::instance()
            : null;
    }
}
