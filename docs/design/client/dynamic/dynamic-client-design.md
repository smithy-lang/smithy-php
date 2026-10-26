# Dynamic Client Design

**Status:** Current as of October 13, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

## Summary

The dynamic client executes a Smithy service from a Smithy JSON AST at
runtime. It converts modeled shapes into runtime schemas, creates
document-backed operation values, resolves client dependencies, and sends
each operation through the same `ClientPipeline` used by generated clients.

`Smithy\Client\Dynamic\DynamicClient` owns the provider-neutral
implementation. `Aws\Core\Client\AwsDynamicClient` adds AWS protocols,
configuration, authentication, endpoint functions, retries, and checksum
plugins.

The normative dynamic-client requirements are defined by the Dynamic Client
SEP. These documents describe the PHP implementation across the dynamic
client and provider integration work rather than the contents of one pull
request. Remaining release alignment is tracked in
[Testing and Validation](testing-and-validation.md).

## Goals

1. Execute modeled operations without generated service or shape classes.
2. Preserve Smithy shape types, traits, member indices, and references at
   runtime.
3. Reuse the generated-client protocol, endpoint, authentication, retry,
   interceptor, and transport implementations.
4. Let provider packages register protocol and service behavior without
   adding provider rules to `smithy-php`.
5. Keep runtime-loaded and generated schemas compatible with the same
   protocol and plugin contracts.

## Architecture

```text
Smithy JSON AST
    |
    v
SmithyModel
    |
    +-> DynamicClientFactory
    |       |
    |       +-> service selection
    |       +-> protocol selection
    |       +-> endpoint resolver
    |       +-> ClientConfig
    |
    +-> SchemaConverter -> TypeRegistry
                            |
                            v
                    DynamicService
                            |
                            v
                    DynamicOperation
                            |
                  DocumentFactory
                      (internal)
                            |
                            v
                      StructDocument
                            |
                            v
                      ClientPipeline
                            |
        +-------------------+-------------------+
        |                   |                   |
     protocol             auth              endpoint
        |                   |                   |
        +-------------------+-------------------+
                            |
                            v
                       HTTP client
```

The dynamic client changes how operation metadata and values are created. It
does not add a second execution pipeline.

## Components

| Type | Responsibility |
|---|---|
| `SmithyModel` | Read and query a Smithy JSON AST. |
| `SchemaConverter` | Convert modeled shapes and traits into `Schema` objects. |
| `TypeRegistry` | Resolve and cache schemas by modeled or canonical shape ID. |
| `DynamicService` | Expose service metadata, lazily create operations, and index modeled waiters. |
| `DynamicOperation` | Expose operation schemas, canonical service identity, auth, errors, streaming metadata, and value factories. |
| `DocumentFactory` | Validate caller values and create schema-backed documents for dynamic operations. |
| `DynamicClientRuntime` | Carry internal provider registrations for protocols, auth, endpoints, traits, and protocol preference. |
| `DynamicClientFactory` | Resolve runtime registrations and construct the requested dynamic client. |
| `DynamicClient` | Provide operation lookup, calls, pagination, waiters, overrides, and output validation. |
| `AwsDynamicClient` | Add AWS-owned runtime registrations and client configuration. |

## Construction

`DynamicClient::create()` accepts a `SmithyModel` or a path to a Smithy JSON
AST:

```php
use Smithy\Client\Dynamic\DynamicClient;

$client = DynamicClient::create(
    model: __DIR__ . '/weather.json',
    endpoint: 'https://weather.example.com'
);
```

The resolver performs these steps:

1. Load the model when the caller supplied a file path.
2. Select the explicit service or the model's only service.
3. Merge class registrations, caller registrations, and explicit arguments.
4. Select a modeled protocol implementation.
5. Resolve a caller-supplied endpoint resolver, modeled endpoint BDD, or
   static endpoint.
6. Build the client configuration.
7. Construct the dynamic service and its schema registry.
8. Apply client plugins and create one shared pipeline.

See [Resolution and Pipeline](resolution-and-pipeline.md) for precedence and
failure behavior.

## Invocation

`DynamicClient::call()` is the canonical, collision-free invocation API. It
accepts an operation name and a document-shaped value:

```php
$output = $client->call('GetForecast', [
    'city' => 'Raleigh'
]);
```

PHP method syntax is a convenience that delegates through `__call()`:

```php
$output = $client->getForecast([
    'city' => 'Raleigh'
]);
```

PHP only invokes `__call()` when no real public method matches the name.
Operations such as `Call` or `GetOperation` therefore need to use the
canonical `call('OperationName', ...)` form.

Inputs may be associative arrays, `DocumentInterface` values, or `null`.
`null` is treated as an empty structure input before required-member and
default validation.
`DynamicOperation::createInputFromValue()` validates the value against the
operation input schema before the pipeline runs. It delegates document
creation to the internal `DocumentFactory`. Outputs are returned as
`StructDocument` values.

Request overrides may be passed separately:

```php
$output = $client->call(
    'GetForecast',
    ['city' => 'Raleigh'],
    ['@retries' => 1]
);
```

Keys beginning with `@` in an input array remain modeled input and are not
promoted to request configuration. Request overrides must be passed through
the dedicated overrides argument. Waiter request overrides must be supplied
through `WaiterConfig`.

## Pagination

`DynamicClient::paginate()` creates a paginator for an operation with
effective `@paginated` metadata:

```php
$pages = $client->paginate('ListForecasts', [
    'city' => 'Raleigh',
    'pageSize' => 25,
])->withMaxItems(100);

foreach ($pages as $page) {
    $forecasts = $page->getMember('forecasts')?->asList() ?? [];
}
```

Dynamic clients also recognize the generated-client method convention:

```php
$pages = $client->listForecastsPaginated([
    'city' => 'Raleigh',
    'pageSize' => 25,
]);
```

Paginator construction validates the modeled token, page-size, and item
paths but sends no request. Iteration lazily sends each page through the
shared client pipeline, updates the schema-backed input with the continuation
token, and yields each response as a `StructDocument`.

The optional overrides argument to `paginate()` applies request overrides to
every page. `withMaxItems()` limits the total modeled item count when the
operation defines both item and page-size paths.

## Waiters

`DynamicClient::waitUntil()` executes a modeled waiter by its service-wide
name:

```php
$result = $client->waitUntil(
    waiterName: 'ForecastReady',
    input: ['city' => 'Raleigh'],
    maxWaitTime: 60.0,
);

$forecast = $result->output;
```

The equivalent generated-client method convention is also available:

```php
$result = $client->waitUntilForecastReady(
    ['city' => 'Raleigh'],
    60.0,
);
```

The client validates and coerces waiter input through the same
`DocumentFactory` used by `call()`. It then polls the waiter's operation
through the shared client pipeline and evaluates the modeled acceptors with
the same waiter state machine used by generated clients. `WaiterConfig` can
override modeled delay bounds and apply request overrides to every attempt.
The result retains the output or modeled error that satisfied the success
acceptor.

Waiter names are resolved case-insensitively across the service. Unknown
names and duplicate modeled names fail before polling begins.

`DynamicClient::__call()` recognizes `<operation>Paginated()` and
`waitUntil<Waiter>()` only when the complete method name is not itself a
modeled operation. This preserves access to operations whose modeled names
happen to use either convention. Names matching both convenience conventions
are ambiguous and require the explicit `paginate()` or `waitUntil()` API.

## Shared Pipeline

`DynamicClient` extends `AbstractClient`. Its calls enter
`AbstractClient::executeOperation()` with a `DynamicOperation` and a
`StructDocument`.

The shared pipeline then performs the same work for dynamic and generated
clients:

```text
input modification
    -> operation-relative protocol serialization
    -> retry loop
        -> auth and identity resolution
        -> endpoint resolution and request binding
        -> signing
        -> HTTP transmission
        -> protocol deserialization
    -> output modification
```

Interceptors receive the same context types in both client forms. Plugins
inspect operation schemas and client configuration rather than generated
shape classes.

## Provider Extension

`DynamicClientRuntime` is the internal, provider-neutral registration
container used by dynamic client subclasses. It can contribute:

- protocol implementations keyed by protocol shape ID
- auth scheme implementations
- endpoint rules functions
- endpoint auth scheme aliases
- protocol preference
- schema trait materializers
- error discriminator traits

`AwsDynamicClient` supplies AWS behavior through its protected runtime and
configuration hooks. It adds AWS JSON, RestJson, RestXml, AWS Query, EC2
Query, AWS endpoint functions, SigV4 configuration, AWS traits, and query
error discriminators.

The generic runtime registers RPC v2 CBOR and the standard Smithy endpoint
functions. Explicit `create()` arguments overlay those class defaults.
`ClientConfig` installs no-auth automatically. Applications that
use HTTP bearer authentication register `HttpBearerAuthScheme` with an
identity resolver.

The base client installs idempotency-token handling, request compression, and
required HTTP checksum behavior. Its default registration places request
compression before checksum calculation so the checksum describes the
transmitted body. Normalizing that order when a caller supplies one of those
plugin classes remains a preview-release check. The AWS client then layers
AWS configuration, flexible checksums, and standard retry behavior on top.

## Preview Scope

| Area | Current behavior |
|---|---|
| Model input | Smithy JSON AST object or file path |
| Service selection | Explicit service ID or one modeled service |
| Operation input | Associative array, `DocumentInterface`, or `null` |
| Operation output | `StructDocument` |
| Generic protocol | RPC v2 CBOR |
| AWS protocols | AWS JSON 1.0, AWS JSON 1.1, RestJson, RestXml, AWS Query, EC2 Query, and RPC v2 CBOR |
| Endpoints | Caller resolver, `smithy.rules#endpointBdd`, or static endpoint |
| Authentication | No-auth by default, caller-configured HTTP bearer, and SigV4 in `aws-sdk-php` |
| Errors | Modeled document exceptions and protocol-specific unmodeled exceptions |

The preview rejects operations with streaming input or output members before
input coercion and transmission. This covers streaming blobs and event
streams.

The runtime does not evaluate `smithy.rules#endpointRuleSet`. Dynamic
endpoint execution requires `smithy.rules#endpointBdd`, a static endpoint, or
a caller-supplied resolver.

SigV4a and adaptive AWS retry mode remain outside the preview.

Modeled pagination and waiters are available through the explicit
`DynamicClient::paginate()` and `DynamicClient::waitUntil()` APIs, as well as
the generated-client-style `<operation>Paginated()` and
`waitUntil<Waiter>()` convenience methods.

Provider synchronization and other remaining preview-release checks are
listed in [Testing and Validation](testing-and-validation.md).

## Design Documents

- [Model and Schema](model-and-schema.md)
- [Resolution and Pipeline](resolution-and-pipeline.md)
- [Serde](serde.md)
- [Testing and Validation](testing-and-validation.md)
- [Client Runtime](../client-runtime-design.md)
- [Endpoint Resolution](../endpoint/endpoint-resolution.md)
- [Auth Runtime](../auth/auth-runtime-design.md)
