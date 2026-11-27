<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Serde\Utils;

/**
 * Enum class a type registry maps a shape ID to, like generated registries do for enums.
 */
enum RegistryStatusEnumFixture: string
{
    case Active = 'active';
}
