<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Fixture;

use Smithy\Client\{
    ClientConfig,
    ClientInterface,
    RequestOverrideConfig
};
use Smithy\Client\Pagination\{
    PageSizePaginatedRequestInterface,
    PaginatedRequestInterface
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;
final class MockClient implements ClientInterface
{
    /** @var list<string|array<array-key, mixed>|null> */
    public array $tokens = [];

    /** @var list<int|null> */
    public array $pageSizes = [];

    /** @var list<RequestOverrideConfig|null> */
    public array $overrides = [];

    /** @param list<PaginationOutput> $outputs */
    public function __construct(private array $outputs) {}

    public function getConfig(): ClientConfig
    {
        throw new \LogicException(
            'Pagination tests do not use client config.'
        );
    }

    public function executeOperation(
        OperationInterface $operation,
        SerializableStructInterface $input,
        ?RequestOverrideConfig $overrides = null
    ): SerializableStructInterface
    {
        if (!$input instanceof PaginatedRequestInterface) {
            throw new \LogicException('Expected a paginated request.');
        }

        $this->tokens[] = $input->getPaginationToken();
        $this->pageSizes[] = $input
            instanceof PageSizePaginatedRequestInterface
                ? $input->getPaginationPageSize()
                : null;
        $this->overrides[] = $overrides;
        $output = \array_shift($this->outputs);
        if (!$output instanceof PaginationOutput) {
            throw new \LogicException('No pagination output remains.');
        }

        return $output;
    }
}
