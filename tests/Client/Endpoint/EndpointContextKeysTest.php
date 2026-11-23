<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Endpoint\EndpointContextKeys;

#[CoversClass(EndpointContextKeys::class)]
final class EndpointContextKeysTest extends TestCase
{
    public function testParametersKeyIsShared(): void
    {
        $this->assertSame(
            EndpointContextKeys::parameters(),
            EndpointContextKeys::parameters()
        );
        $this->assertSame(
            'smithy.endpointParameters',
            EndpointContextKeys::parameters()->name
        );
    }

    public function testTraceSinkKeyIsShared(): void
    {
        $this->assertSame(
            EndpointContextKeys::traceSink(),
            EndpointContextKeys::traceSink()
        );
        $this->assertSame(
            'smithy.endpointResolutionTraceSink',
            EndpointContextKeys::traceSink()->name
        );
    }

    public function testKeysAreDistinct(): void
    {
        $this->assertNotSame(
            EndpointContextKeys::parameters(),
            EndpointContextKeys::traceSink()
        );
    }
}
