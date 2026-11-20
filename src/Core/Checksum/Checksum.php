<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Core\Checksum;

/**
 * Compute a checksum over a byte stream fed one chunk at a time.
 */
interface Checksum
{
    /**
     * Feed the next chunk of bytes into the running checksum.
     */
    public function update(string $bytes): void;

    /**
     * Return the finished checksum as raw bytes.
     */
    public function digest(): string;

    /**
     * Return the checksum length in bytes.
     */
    public function digestLength(): int;
}
