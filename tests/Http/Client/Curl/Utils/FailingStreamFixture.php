<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

final class FailingStreamFixture extends TestStreamFixture
{
    private int $readCount = 0;

    public function __construct(
        string $content,
        private readonly \Throwable $error
    )
    {
        parent::__construct($content, true, true);
    }

    public function read(int $length): string
    {
        if (++$this->readCount > 1) {
            throw $this->error;
        }

        return parent::read(\min(3, $length));
    }
}
