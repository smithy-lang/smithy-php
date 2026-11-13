<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode\Fixture;

use Smithy\Client\Endpoint\Bytecode\EndpointResolutionTraceInterface;
use Smithy\Core\Endpoint\Endpoint;

final class EndpointResolutionTraceFixture implements
    EndpointResolutionTraceInterface
{
    /** @var array<string, mixed> */
    public array $parameters = [];

    /** @var list<array{int, int, bool, bool}> */
    public array $nodes = [];

    /** @var list<array{int, Endpoint|null}> */
    public array $results = [];

    public function parameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function node(
        int $nodeReference,
        int $conditionIndex,
        bool $satisfied,
        bool $branch
    ): void
    {
        $this->nodes[] = [
            $nodeReference,
            $conditionIndex,
            $satisfied,
            $branch
        ];
    }

    public function result(int $resultIndex, ?Endpoint $endpoint): void
    {
        $this->results[] = [$resultIndex, $endpoint];
    }
}
