<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Utils;

use Psr\Log\AbstractLogger;

/**
 * PSR-3 logger that records every log call in order.
 */
final class RecordingLoggerFixture extends AbstractLogger
{
    /**
     * @var list<array{level: mixed, message: string, context: array<mixed>}>
     */
    public array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log(
        $level,
        string|\Stringable $message,
        array $context = []
    ): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string) $message,
            'context' => $context
        ];
    }

    /**
     * Return the recorded messages with placeholders left unexpanded.
     *
     * @return list<string>
     */
    public function messages(): array
    {
        return \array_map(
            static fn(array $record): string => $record['message'],
            $this->records
        );
    }

    /**
     * Return the recorded levels in order.
     *
     * @return list<mixed>
     */
    public function levels(): array
    {
        return \array_map(
            static fn(array $record): mixed => $record['level'],
            $this->records
        );
    }
}
