<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Auth\Utils;

use Smithy\Client\Auth\IdentityInterface;
final class TestIdentityFixture implements IdentityInterface
{
    public function getExpirationTime(): ?float
    {
        return null;
    }
}
