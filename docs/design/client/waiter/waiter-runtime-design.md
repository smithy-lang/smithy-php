# Waiter Runtime Design

**Status:** Current as of September 30, 2026

**Package:** `smithy-php` (`Smithy\Client\Waiter\`, `Smithy\Codegen\`)

## Summary

Smithy-PHP generates a typed `waitUntil<Name>()` client method for each
waiter in an operation's `smithy.waiters#waitable` trait. The method polls
the operation through the normal client pipeline until an ordered acceptor
selects success, selects failure, or the caller's time budget expires.

Callers must provide a maximum wait time. Modeled minimum and maximum retry
delays control full-jitter backoff. `WaiterConfig` can override those two
delay bounds for one invocation.

The public waiter specification defines model validation, acceptor behavior,
and retry timing. Smithy-Java served as an implementation reference where
the public specification left room for language-specific API design.

## Architecture

```text
Smithy model
    |
    |  validate and normalize smithy.waiters#waitable
    v
WaiterTraitNormalizer
    |
    v
ClientGenerator
    |
    |  emit waitUntil<Name>()
    v
Generated service client
    |
    |  AbstractClient::createWaiter()
    v
Waiter -> operation pipeline -> protocol -> HTTP client
    |
    +-> ordered Acceptor list
    +-> Matcher and PathComparator
    +-> Backoff and Async::delay()
    +-> WaiterResult or WaiterFailureException
```

Waiter definitions are embedded in generated client methods. Runtime code
does not parse Smithy traits or service-specific waiter documents.

## Model Source

Smithy-PHP consumes the native `smithy.waiters#waitable` trait from the
Smithy JSON AST. Legacy AWS `waiters-2.json` conversion is outside this
implementation. An AWS model build step can add native traits before
invoking Smithy-PHP codegen.

`SmithyModel::getValidatedWaiters()` normalizes one operation.
`SmithyModel::getValidatedServiceWaiters()` walks the service closure and
checks waiter-name uniqueness across its operations and resources.

### Waiter validation

`WaiterTraitNormalizer` enforces these constraints:

- Waiter names use UpperCamelCase alphanumeric characters.
- Waiter names are case-insensitively unique in the service closure.
- Event-stream operations cannot carry waiters.
- `minDelay` and `maxDelay` are positive integers.
- `minDelay` does not exceed `maxDelay`.
- `acceptors` is a non-empty ordered list with a success state.
- Each acceptor has one valid state and exactly one matcher member.
- Output and input-output matchers require an operation output.
- Failure messages appear only on failure acceptors.
- Documentation, deprecation flags, and tags use their specified types.

An absent `minDelay` becomes 2 seconds. An absent `maxDelay` becomes 120
seconds. Defaults apply only to absent fields. An explicit null fails type
validation.

Acceptor and waiter declaration order survives normalization. Acceptor order
affects runtime behavior because the first matching acceptor wins.

### Normalized representation

Codegen receives waiter metadata in this form:

```php
[
    'documentation' => null,
    'acceptors' => [
        [
            'state' => 'success',
            'matcher' => [
                'type' => 'output',
                'path' => 'status',
                'expected' => 'ready',
                'comparator' => 'stringEquals'
            ],
            'message' => null
        ]
    ],
    'minDelay' => 2,
    'maxDelay' => 120,
    'deprecated' => false,
    'tags' => []
]
```

Flattening the matcher union keeps model parsing out of
`ClientGenerator`.

## Generated API

Each named waiter becomes a method on the generated service client:

```php
public function waitUntilBucketExists(
    HeadBucketRequest $input,
    float $maxWaitTime,
    ?WaiterConfig $config = null
): WaiterResult
{
    return $this->createWaiter(
        HeadBucketOperation::instance(),
        [
            new Acceptor(
                Acceptor::SUCCESS,
                Matcher::success(true)
            ),
            new Acceptor(
                Acceptor::RETRY,
                Matcher::errorType('NotFound')
            )
        ],
        5,
        120,
        $config
    )->wait($input, $maxWaitTime);
}
```

The input is required even when the operation input has no members. The
caller owns the total wait budget, expressed as finite positive seconds.

Client methods use this order for each operation:

1. Operation method.
2. Paginator method, when present.
3. Attached waiter methods sorted by waiter name.

Waiters are direct client methods. The generator does not emit a waiter
container or a client waiter accessor.

### Generated documentation

Modeled waiter documentation is the method summary when present. Codegen
otherwise recognizes common `<Resource><State>` names:

```php
/**
 * Wait until {@see self::headBucket()} reports the bucket as existing.
 */
```

Compound states receive action nouns. `StackDeleteComplete`, for example,
becomes "reports the stack deletion as complete."

An unrecognized name uses a literal fallback:

```php
/**
 * Wait until {@see self::sign()} satisfies the modeled
 * `SuccessfulSigningJob` condition.
 */
```

The fallback avoids assigning meaning that the model does not provide.
Modeled deprecation adds `@deprecated`. The normalized tag list remains
available to codegen but has no generated PHP API.

## Runtime Types

| Type | Responsibility |
|---|---|
| `Waiter` | Run the polling workflow and enforce the deadline. |
| `Acceptor` | Pair a matcher with success, failure, or retry. |
| `Matcher` | Evaluate one modeled matcher form. |
| `PathComparator` | Compare a JMESPath result with an expected value. |
| `AttemptContext` | Hold input, output, or modeled error for one attempt. |
| `Backoff` | Compute full-jitter retry delays. |
| `WaiterConfig` | Override modeled delay bounds for one call. |
| `WaiterResult` | Return the output or modeled error accepted as success. |
| `WaiterFailureException` | Report terminal failure and timing diagnostics. |

`Acceptor` uses integer state constants:

```php
Acceptor::SUCCESS; // 0
Acceptor::FAILURE; // 1
Acceptor::RETRY;   // 2
```

The state is read during every acceptor resolution. Constants avoid a
separate waiter-state object while keeping generated construction explicit.

## Polling Workflow

`Waiter::wait()` runs this loop:

1. Increment the attempt count.
2. Execute the attached operation with the original input.
3. Convert a modeled API error into an attempt result.
4. Check elapsed monotonic time against `maxWaitTime`.
5. Evaluate acceptors in declaration order.
6. Return on success or throw on terminal failure.
7. Compute a retry delay and suspend through `Async::delay()`.
8. Start the next attempt.

The same input object is reused for every attempt. Waiters do not modify
operation inputs between polls.

### Implicit transitions

Two transitions apply when no acceptor matches:

- A successful operation response transitions to retry.
- A modeled operation error transitions to failure.

Transport errors, programming errors, and other unmodeled exceptions fail
the waiter immediately. They are wrapped as the previous exception on
`WaiterFailureException`.

### First match wins

Acceptor evaluation stops at the first match. Model authors can place a
specific terminal condition before a broader condition.

The terminal states behave as follows:

| State | Result |
|---|---|
| `SUCCESS` | Return `WaiterResult` with the accepted output or modeled error. |
| `FAILURE` | Throw `WaiterFailureException`. |
| `RETRY` | Delay and poll again. |

## Matcher Evaluation

`Matcher` consolidates the four Smithy matcher forms:

| Matcher | Attempt data | Match |
|---|---|---|
| `success` | Operation outcome | Outcome matches the expected success boolean. |
| `errorType` | Modeled API error | A qualified shape ID matches exactly. An unqualified name matches the shape name. |
| `output` | Successful output | JMESPath result satisfies a comparator. |
| `inputOutput` | Input plus successful output | JMESPath result satisfies a comparator. |

A fully qualified error shape ID is namespace-sensitive and compares exactly.
A relative shape name compares only the portion after `#`.

### JMESPath data

`AttemptContext` converts generated structures through
`ArrayShapeSerializer`. JMESPath sees modeled member names rather than wire
names.

An output matcher receives the serialized output object. An input-output
matcher receives:

```php
[
    'input' => $serializedInput,
    'output' => $serializedOutput
]
```

Input and output conversion is lazy and cached for the attempt. A success or
error matcher does not serialize either structure.

Matcher expressions use `mtdowling/jmespath.php` 2.9.1 or newer. Invalid
expressions encountered at runtime fail acceptor evaluation and become
`WaiterFailureException`.

### Path comparators

Comparator checks are type strict:

| Comparator | Required result | Behavior |
|---|---|---|
| `stringEquals` | String | Exact string equality. |
| `booleanEquals` | Boolean | Expected is `true` or `false`. |
| `allStringEquals` | Non-empty string list | Every value equals expected. |
| `anyStringEquals` | String list | At least one value equals expected. |

An empty list fails both collection comparators. Non-string values fail
string comparisons instead of being coerced.

### Failure messages

A failure acceptor can carry a JMESPath expression in `message`. The
expression runs against a successful operation output after the acceptor
matches.

A string result is appended to the terminal failure message. A list of
strings is joined with `"; "`. Other result types fail the waiter because
they violate the modeled message contract.

## Deadlines and Backoff

`maxWaitTime` covers operation execution and retry delays. The waiter reads
`hrtime(true)` before the first request and after each request. Monotonic
time prevents wall-clock adjustments from changing the deadline.

The post-request deadline check runs before acceptor evaluation. A response
that arrives at or after the deadline cannot complete the waiter
successfully.

`Backoff` implements the specification's full-jitter algorithm:

```text
attemptCeiling = log(maxDelay / minDelay) / log(2) + 1

if attempt > attemptCeiling:
    upperBound = maxDelay
else:
    upperBound = minDelay * 2 ** (attempt - 1)

upperBound = min(upperBound, maxDelay)
delay = random_int(minDelay, upperBound)
```

When subtracting the computed delay would leave at most `minDelay`, the
waiter uses the full remaining budget as the delay. This schedules the
specification's final polling attempt instead of ending after an unused
partial interval.

`WaiterConfig` can replace either modeled delay bound:

```php
$result = $client->waitUntilThingReady(
    $input,
    maxWaitTime: 300.0,
    config: new WaiterConfig(
        minDelay: 5,
        maxDelay: 30,
        requestOverrides: $overrides
    )
);
```

Resolved delay bounds must remain positive and ordered. `maxWaitTime` stays
separate because the specification requires the caller to choose it for
each wait. Request overrides, when supplied, apply to every polling request.

## Async and I/O

Generated waiter methods return synchronously from the caller's perspective.
They use the existing operation pipeline for each request.

`Async::delay()` delegates to the configured event loop. The default
`RevoltEventLoop` schedules a timer, suspends the current fiber, and resumes
it after the delay. Waiters do not call `sleep()` or `usleep()`.

Inside `Async::all()` or `Async::map()`, other fibers continue while a
waiter is delayed or its HTTP request is suspended. A standalone waiter call
drives the same Revolt loop until the timer or request completes.

The deadline check cannot interrupt an HTTP request already in progress.
Future HTTP-client cancellation support can cancel that request when the
deadline expires. The generated waiter signature does not need to change for
that work.

## Results and Failures

`WaiterResult` preserves the value that matched a success acceptor:

- `output` contains a successful operation output.
- `error` contains a modeled API error accepted as success.

Exactly one property is set.

`WaiterFailureException` includes:

- `attempts`, counting polling requests started.
- `elapsedTime`, measured in monotonic seconds.
- The polling, matcher, or message error as the previous exception when one
  exists.

| Condition | Failure |
|---|---|
| Deadline reached after a request | Timed out. |
| Failure acceptor matched | Terminal failure state. |
| Modeled error matched no acceptor | Unmatched polling error. |
| Transport or programming exception | Unexpected polling error. |
| Matcher expression failed | Acceptor evaluation failure. |
| Failure message had an invalid result | Failure-message evaluation failure. |

## Performance

Codegen emits operation classes, delay bounds, acceptors, matchers, paths,
and expected values as PHP literals. A waiter invocation allocates its
runtime objects once before polling.

Acceptor order can avoid later JMESPath work. `AttemptContext` serializes
input or output only when a path matcher or failure message requests it, then
caches the array for the rest of that attempt.

The runtime retains the input and the current attempt result. It does not
accumulate prior outputs or errors.

## Compatibility

Adding a waiter adds one public client method. Changing a waiter's acceptors
or delays changes polling behavior after regeneration.

Waiter names are unique across a service closure because they become public
method suffixes. Generated methods use the operation input type and shared
runtime return type, so modeled output changes do not change the waiter
method return declaration.

## Deliberate Scope

The runtime consumes native `smithy.waiters#waitable` traits. Legacy AWS
waiter conversion remains in the AWS model build backlog.

The API uses a required time budget instead of a maximum-attempt count.
Backoff jitter means an attempt limit would not express the caller's total
wait commitment.

JMESPath static analysis is optional in the Smithy specification. Codegen
validates matcher structure and comparator expectations, then leaves
expression parsing to the JMESPath library.

Waiters share the synchronous generated-client surface. Cooperative
suspension comes from the existing HTTP client and `Async::delay()`.

## Testing

Runtime tests cover acceptor ordering, implicit transitions, successful
outputs, modeled errors accepted as success, terminal failures, deadlines,
final-budget delays, matcher failures, and extracted failure messages.

Matcher tests cover all four matcher forms, absolute error shape IDs, lazy
input-output data, and each path comparator.

Codegen tests cover waiter validation, defaults, service-wide name
uniqueness, event-stream rejection, generated method ordering, all matcher
forms, generated documentation, and PHP syntax.

A generated RPC v2 CBOR client test sends waiter requests through the full
operation pipeline. Provider releases should also normalize their native
waiter corpus and parse the generated client source.

## References

- [Smithy waiter specification](https://smithy.io/2.0/additional-specs/waiters.html)
- [Code generation design](../../codegen/smithy-php-code-generation.md)
- [Pagination runtime design](../pagination/pagination-runtime-design.md)
- [Client runtime](../client-runtime-design.md)
- [JMESPath PHP](https://github.com/jmespath/jmespath.php)
