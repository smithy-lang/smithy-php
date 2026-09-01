<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

final class PartialReadStreamFixture extends TestStreamFixture
{
    public function read(int $length): string
    {
        return parent::read(\min(2, $length));
    }
}
