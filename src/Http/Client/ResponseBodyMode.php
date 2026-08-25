<?php

declare(strict_types=1);

namespace Smithy\Http\Client;

/**
 * Select how an HTTP response body is delivered.
 */
enum ResponseBodyMode: int
{
    /** Buffer known small responses and stream unknown or large responses. */
    case Auto = 0;

    /** Buffer the complete response in memory before returning it. */
    case Buffered = 1;

    /** Return a live bounded response stream after final headers. */
    case Streamed = 2;
}
