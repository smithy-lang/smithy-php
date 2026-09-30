# Pagination Runtime Design

**Status:** Current as of September 30, 2026

**Package:** `smithy-php` (`Smithy\Client\Pagination\`, `Smithy\Codegen\`)

## Summary

Smithy-PHP generates a typed paginator method for each operation with an
effective `@paginated` trait. The generated method creates a shared
`Paginator` that sends requests through the normal client pipeline and yields
each modeled output page.

The paginator preserves the original input parameters across requests. It
changes only the continuation token and, when requested by the caller, the
modeled page size. Iteration stops when the service omits the output token,
returns an empty token, or returns the same token in back-to-back responses.

## Architecture

```
Smithy model
    |
    |  resolve and validate @paginated
    v
SmithyModel
    |
    +--> OperationGenerator --> operation schema pagination metadata
    |
    +--> StructureGenerator --> paginated input contracts
    |
    +--> ClientGenerator -----> <operation>Paginated() method
                                      |
                                      v
                            AbstractClient::createPaginator()
                                      |
                                      v
                                  Paginator
                                      |
                                      v
                            normal operation pipeline
```

Codegen maps model-specific member names to shared runtime contracts.
`Paginator` does not know whether a service calls its token `nextToken`,
`marker`, or another modeled name.

## Model Resolution

### Trait inheritance

An operation opts into pagination by carrying `@paginated`. A service-level
trait supplies defaults to paginated operations in that service closure. The
operation trait overrides fields inherited from the service.

The service closure includes:

- Operations listed directly on the service.
- Resource lifecycle operations.
- Resource operations and collection operations.
- Operations on nested resources.

The resource walk tracks visited resources and operations. Cyclic resource
graphs terminate without duplicating generated methods.

### Codegen validation

`SmithyModel::getValidatedPaginationTrait()` validates fields needed by the
generated runtime:

| Field | Location | Accepted target |
|---|---|---|
| `inputToken` | Top-level input member | String or map |
| `outputToken` | Output member path | String or map |
| `pageSize` | Top-level input member | Byte, short, integer, or long |
| `items` | Output member path | List or map |

`inputToken` and `outputToken` must be present after service defaults and
operation settings are merged. `pageSize` and `items` are optional.

Output paths use dot-separated structure member names. Codegen verifies each
path component and the final target type. Input fields name direct members
because the paginator must update them on generated request objects.

The Smithy model validator enforces requiredness constraints, including the
rule that `inputToken` must not be required. PHP codegen consumes a validated
model and does not duplicate that validation.

## Generated Code

### Operation metadata

`OperationGenerator` stores the effective pagination settings in the
operation schema:

```php
PaginationSchemaExtensionProvider::TRAIT => [
    'inputToken' => 'nextToken',
    'outputToken' => 'result.nextToken',
    'items' => 'result.items',
    'pageSize' => 'maxResults'
]
```

`PaginationSchemaExtensionProvider` converts this packed trait into a
`PaginationSchemaExtension` on first access. The operation schema caches the
extension for subsequent paginator instances.

### Input contracts

Every paginated operation input implements `PaginatedRequestInterface`:

```php
interface PaginatedRequestInterface extends SerializableStructInterface
{
    public function getPaginationToken(): string|array|null;

    public function withPaginationToken(string|array|null $token): self;
}
```

Inputs with a modeled `pageSize` also implement
`PageSizePaginatedRequestInterface`:

```php
interface PageSizePaginatedRequestInterface
    extends PaginatedRequestInterface
{
    public function getPaginationPageSize(): ?int;

    public function withPaginationPageSize(int $pageSize): self;
}
```

The interfaces hide model-specific property names from the shared paginator.
Token values support Smithy string and map pagination tokens.

Generated update methods use copy-on-write:

```php
public function withPaginationToken(string|array|null $token): self
{
    $copy = clone $this;
    $copy->nextToken = $token;

    return $copy;
}
```

Pagination token and page-size properties are private and writable so cloned
instances can update them. Other generated properties remain readonly.

Codegen keeps these members in their existing constructor positions. It
declares the properties separately instead of promoting them, which preserves
constructor ABI while allowing clone updates.

If one input shape serves multiple paginated operations, each operation must
name the same token member and page-size member. Conflicting mappings fail
code generation.

### Client methods

`ClientGenerator` emits one typed paginator method beside the original
operation method:

```php
/**
 * Create a paginator for {@see self::listThings()}.
 *
 * @return Paginator<ListThingsOutput>
 */
public function listThingsPaginated(
    ListThingsInput $input
): Paginator
{
    return $this->createPaginator(
        ListThingsOperation::instance(),
        $input
    );
}
```

`AbstractClient::createPaginator()` centralizes construction. Generated
clients expose the typed method while the base client owns the shared runtime
dependency.

`Paginator::withRequestOverrides()` applies one `RequestOverrideConfig` to
every page request without changing the generated method signature.

## Runtime Iteration

`Paginator` implements `IteratorAggregate`. Creating one sends no request.
Iteration executes this loop:

1. Start with the original input object.
2. Apply a reduced page size when an item limit requires one.
3. Execute the operation through `ClientInterface::executeOperation()`.
4. Yield the complete output page.
5. Count modeled items when an item limit is active.
6. Read the output token through the precomputed schema path.
7. Stop on an empty token or a repeated response token.
8. Clone the request input with the new token and send the next request.

Each call to `getIterator()` starts from the original input. A paginator can
therefore be iterated more than once.

All requests use the standard operation pipeline. Endpoint resolution,
serialization, signing, retries, transport, and deserialization behave the
same as calls to the generated non-paginated operation method.

### Path extraction

The constructor resolves `outputToken` and `items` paths into lists of
`Schema` objects. Per-page extraction walks those schema objects and calls
`SerializableStructInterface::getMemberValue()`.

Resolving paths once avoids splitting strings and looking up members for
every page. Runtime path validation remains in place for manually assembled
operation schemas.

### Empty tokens

The paginator accepts `string|array|null` tokens and treats these values as
empty:

- `null`
- An empty string.
- An empty array.

This supports Smithy string tokens and map tokens. Tokens remain opaque to
the paginator.

### Repeated tokens

The paginator compares every response token with the token used for that
request. This includes an initial resume token supplied by the caller. It
yields the response that repeats the request token, then stops before sending
a duplicate request.

String tokens use strict equality. Map tokens are compared recursively by key
and value, independent of associative key order. Nested object values compare
by PHP value equality.

## Item Limits

`withMaxItems()` returns a cloned paginator with a positive modeled-item
limit:

```php
$pages = $client
    ->listThingsPaginated($input)
    ->withMaxItems(100);
```

Item limits require both `items` and `pageSize` metadata. The input must
implement `PageSizePaginatedRequestInterface`.

Before each request, the paginator sets page size to the smaller of:

- The positive page size from the original input.
- The number of items remaining.

If the original page size is null or less than one, the remaining item count
becomes the request page size. The paginator subtracts the count of the
modeled list or map after each response and stops when the remainder reaches
zero.

The paginator yields complete service responses. It does not trim a response
that contains more items than requested. A service that exceeds the requested
page size can therefore cause the yielded item count to exceed `maxItems`.

An empty filtered page does not end pagination when it carries a continuation
token. Its item count is zero, so the paginator continues with the next
request.

## Failure Behavior

Codegen rejects pagination metadata that references missing members, invalid
paths, or unsupported target types.

The runtime throws `LogicException` when:

- An operation schema has no pagination extension.
- An output path cannot be resolved.
- `withMaxItems()` is used without modeled `items` and `pageSize` fields.

`withMaxItems()` throws `InvalidArgumentException` for values below one.
Exceptions from operation execution pass through unchanged.

## Performance

Requests begin when iteration starts. The runtime retains the current output
page, the original input, and the next cloned input unless the caller collects
the iterator.

Paginator construction resolves output paths once. Per-page work consists of
pipeline execution, schema-indexed member reads, token comparison, and one
input clone when another request is needed.

Generated copy methods update one private property after cloning. They avoid
reconstructing input objects from constructor arguments or associative
arrays.

## Compatibility

The generated constructor keeps the model member order recorded by codegen
state. Making pagination properties writable does not move their constructor
parameters or change positional call sites.

The Smithy specification classifies changes to `inputToken`, `outputToken`,
and `items` as backward incompatible. Removing or changing `pageSize` is also
backward incompatible. Adding `pageSize` is compatible, but regenerated
inputs gain `PageSizePaginatedRequestInterface`.

## Deliberate Scope

The paginator's output unit is one service response page. Callers that need
one collection can iterate over each page's modeled `items` member.

The runtime supports one Smithy continuation-token member. Services that need
a composite cursor model it as a map token.

Service-side token expiry, filtering constraints, ordering, and authorization
remain service responsibilities. The client preserves original filtering
parameters because subsequent requests clone the prior request input.

## Testing

Runtime tests cover lazy iteration, string and map tokens, nested output
paths, empty tokens, repeated tokens, resumed pagination, item limits, and
invalid runtime metadata.

Generated-client integration tests send paginated requests through the full
RPC v2 CBOR pipeline. They verify string tokens, map tokens, operations
without page size, and preservation of input values between requests.

Codegen tests cover trait inheritance, metadata validation, generated input
contracts, typed client methods, constructor ABI, and paginated operations
reached through resource closures.

## References

- [Smithy `paginated` trait](https://smithy.io/2.0/spec/behavior-traits.html#smithy-api-paginated-trait)
- AWS pagination SEP
- [Code generation design](../../codegen/smithy-php-code-generation.md)
- [Schema extensions](../../core/schema-extensions.md)
- [Client runtime](../client-runtime-design.md)
