<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Client\Interceptor\{
    AbstractInterceptor,
    InputContext
};
use Smithy\Core\Shape\Document\StructDocument;

final class InputRecordingInterceptorFixture extends AbstractInterceptor
{
    public ?StructDocument $input = null;

    public function readBeforeExecution(InputContext $ctx): void
    {
        if ($ctx->input instanceof StructDocument) {
            $this->input = $ctx->input;
        }
    }
}
