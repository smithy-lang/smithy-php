# Endpoint Resolution

**Status:** Current as of October 17, 2026

**Packages:** `smithy-php`, `aws-sdk-php`

Smithy-PHP resolves an `Endpoint` from an operation, its input, and client
configuration. The resolved value supplies the base URL, endpoint headers, and
endpoint-selected authentication schemes.

The runtime supports three resolver sources:

- a caller-supplied `EndpointResolverInterface`
- a `StaticEndpointResolver` for an explicit endpoint
- a `BytecodeEndpointResolver` for `smithy.rules#endpointBdd`

Generated clients can load precompiled bytecode from a generated PHP class.
Dynamic clients compile the same BDD trait when the client is created. Both
paths use the same `Bytecode`, `Evaluator`, and endpoint value types.

The trait format is specified by the Endpoint BDD SEP. This document covers
the PHP implementation.

## Architecture

```text
Smithy model
    |
    +-> generated path
    |       EndpointBytecodeGenerator
    |           |
    |           +-> <Service>EndpointBytecode.php
    |           +-> <Service>EndpointKeyBuilder.php, when enabled
    |
    +-> dynamic path
            DynamicClientFactory
                |
                +-> BddCompiler

Bytecode
    |
    v
BytecodeEndpointResolver
    |
    +-> operation and input context
    +-> resolver static context
    +-> client endpoint parameters
    +-> L1 and L2 result caches
    |
    v
Evaluator
    |
    +-> BDD walk
    +-> bytecode dispatch
    |
    v
Endpoint
    |
    +-> URL bound to the operation-relative request
    +-> headers applied before signing
    +-> auth schemes considered by the configured auth resolver
    +-> open properties retained for provider integrations
```

Protocol serialization runs once per operation execution against the
unresolved endpoint `/`, producing an operation-relative request. Endpoint
resolution runs while preparing each request attempt. This lets retries
observe updated endpoint parameters and endpoint-selected auth state without
repeating shape serialization.

## Public Runtime Contracts

### `EndpointResolverInterface`

```php
interface EndpointResolverInterface
{
    public function resolveEndpoint(
        EndpointResolverParams $params
    ): Endpoint;
}
```

`EndpointResolverParams` carries:

- the `OperationInterface`
- the operation input
- the active `ClientConfig`

`EndpointResolverParams::CONTEXT_PARAMETERS` names the
`ClientConfig::$context` entry used for client endpoint parameters:

```php
'smithy.endpointParameters'
```

### `Endpoint`

`Endpoint` is an immutable value with four fields:

| Field | Type | Use |
|---|---|---|
| `url` | `string` | Service endpoint bound to the operation-relative request |
| `authSchemes` | `list<EndpointAuthScheme>` | Endpoint-selected scheme IDs and signer properties |
| `headers` | `array<string, string>` | Headers applied before request signing |
| `properties` | `array<string, mixed>` | Open rules-engine properties available to provider integrations |

The evaluator maps ruleset `properties.authSchemes` entries into
`EndpointAuthScheme` values. Provider runtimes can map short ruleset names such
as `sigv4` to canonical Smithy IDs such as `aws.auth#sigv4`. The original
property map remains available through `Endpoint::$properties`,
`getProperty()`, and `hasProperty()`.

### `StaticEndpointResolver`

`StaticEndpointResolver` returns one `Endpoint` for every operation. Dynamic
clients use it when the caller supplies an explicit endpoint and the modeled
BDD does not consume an `Endpoint` parameter.

## Supported Ruleset Forms

Executable endpoint resolution requires `smithy.rules#endpointBdd`.

`smithy.rules#endpointRuleSet` is supported only for parameter introspection in
the current codegen path. `EndpointRulesetIntrospector` reads parameter
definitions from `endpointBdd` first and falls back to `endpointRuleSet`.
Neither generated nor dynamic clients evaluate a raw `endpointRuleSet`.

The `endpointBdd` trait contains:

| Field | Purpose |
|---|---|
| `parameters` | Ordered endpoint parameter definitions |
| `conditions` | Rules-engine expressions referenced by BDD nodes |
| `results` | Endpoint and error result programs |
| `nodes` | Base64-encoded big-endian `int32` triples |
| `nodeCount` | Number of triples in `nodes` |
| `root` | Signed reference to the first node or result |

Each node is `[conditionIndex, highReference, lowReference]`. Terminals are
`1` and `-1`. `100_000_000` is the result-reference offset. The first
serialized result uses `100_000_001` because index zero represents the
implicit no-match result.

## Compilation Paths

### Generated clients

`CodeGenerator` calls `EndpointBytecodeGenerator::generateAll()` for every
service. The generator returns an empty list when the service has no
`endpointBdd` trait.

The generated files are:

1. `<Service>EndpointBytecode.php`
2. `<Service>EndpointKeyBuilder.php` when
   `CodegenSettings::$generateEndpointKeyBuilder` is enabled

`generate()` remains available for callers that only need the bytecode file.
The main codegen orchestrator uses `generateAll()`.

`generateEndpointKeyBuilder` defaults to `false` in Smithy codegen. The AWS
codegen driver defaults it to `true`. The setting controls emission directly.
It does not inspect `aws.api#service`.

The bytecode generator builds its function registry from:

1. `StdFunctions::all()`
2. `CodegenSettings::$rulesFunctions`

Entries in `rulesFunctions` replace standard functions with the same name.
`CodegenSettings::$extendedOpcodes` supplies provider function-to-opcode
mappings.

Provider codegen owns provider registrations. `AwsCodeGenerator` adds
`AwsFunctions` and the AWS extended opcode map before it invokes Smithy
codegen. `smithy-php` does not probe for AWS classes.

### Dynamic clients

`DynamicClientFactory` selects endpoint behavior in this order:

1. Use a caller-supplied resolver.
2. Compile a modeled `endpointBdd` when no endpoint was supplied or the BDD
   declares an `Endpoint` parameter.
3. Use a caller-supplied static endpoint when the modeled BDD cannot consume
   it.
4. Reject client creation when none of the above is available.

A supplied resolver and static endpoint are mutually exclusive. When the BDD
declares an `Endpoint` parameter, an explicit endpoint string is stored as
that parameter and evaluated by the ruleset.

Dynamic compilation uses the endpoint functions registered by the client
class plus explicit caller registrations. It uses normal function dispatch
instead of generated provider opcode fusions.

`DynamicClient::getDefaultRuntime()` registers the Smithy standard functions.
Provider subclasses extend that internal runtime with their own functions and
endpoint auth-scheme aliases. `AwsDynamicClient` registers `AwsFunctions` and
the AWS auth-scheme mappings.

## Parameter Assembly

`BytecodeEndpointResolver` builds one input map for each execution. It caches
operation context descriptors by `OperationInterface::getRuntimeIdentity()`.

Values are applied in this order:

1. Input members carrying `smithy.rules#contextParam`
2. JMESPath expressions from `smithy.rules#operationContextParams`
3. Values from `smithy.rules#staticContextParams`
4. Resolver `staticContext` supplied at construction
5. Client endpoint parameters from `ClientConfig::$context`

Later sources replace earlier values with the same name.

`contextParam` reads a value directly from the operation input.
`operationContextParams` serializes the input to an array and evaluates each
configured JMESPath expression. Null values are omitted. Operation static
context then replaces values gathered from the input.

Generated provider configs use resolver `staticContext` for client-wide
service parameters. The AWS base config stores `Region`, `UseFIPS`, and
`UseDualStack` in `smithy.endpointParameters`, so those values use the final
precedence level.

The evaluator ignores names absent from `Bytecode::$inputRegisterMap`. A
request-scoped value contributes to evaluation and caching when the ruleset
declares it as an input.

## `BddCompiler`

`BddCompiler::compile()` performs four passes over the trait data:

1. Allocate registers for parameters.
2. Compile conditions into fast-path descriptors or bytecode programs.
3. Compile endpoint and error results into bytecode programs.
4. Decode the BDD node blob.

Condition assignments allocate additional registers. A condition program with
an `assign` field ends in `SET_REG_RETURN`, which stores and returns the
calculated value.

Constants, functions, and registers are interned during compilation. Constant
keys include the PHP value type so values such as `false`, `0`, and `"0"` do
not share a pool entry.

### Fast-path conditions

The BDD walker evaluates these condition forms without entering the bytecode
dispatch loop:

| Rules expression | Condition type | Operand |
|---|---|---|
| `isSet({ref: X})` | `COND_ISSET` | Register index |
| `booleanEquals({ref: X}, true)` | `COND_IS_TRUE` | Register index |
| `booleanEquals({ref: X}, false)` | `COND_IS_FALSE` | Register index |
| `stringEquals({ref: X}, "value")` | `COND_STRING_EQ_REG_CONST` | Packed register and constant indices |

Other conditions use `COND_FULL` and an offset into the bytecode program.
`COND_NOT_SET` remains available in the evaluator and opcode contract, but the
current compiler does not emit it for `not(isSet(...))`.

The compiler synthesizes the implicit `NoMatchRule` into result slot zero as
`LOAD_CONST null; RETURN_VALUE`. Serialized endpoint and error results begin
at slot one. This keeps BDD result references and `resultOffsets` aligned with
Smithy-Java: subtracting `RESULT_OFFSET` produces the direct result-table
index.

## `Bytecode`

`Bytecode` is an immutable data container:

| Property | Type | Purpose |
|---|---|---|
| `code` | `list<int>` | Opcode and operand bytes |
| `constants` | `list<mixed>` | Interned literals and template metadata |
| `functions` | `list<RulesFunctionInterface>` | Generic function dispatch table |
| `registers` | `list<array>` | Parameter and assignment register definitions |
| `defaultRegisters` | `list<mixed>` | Register defaults in index order |
| `inputRegisterMap` | `array<string, int>` | Ruleset input name to register index |
| `bddNodes` | `list<int>` | Flat condition, high, and low triples |
| `bddRoot` | `int` | Signed initial BDD reference |
| `conditionOffsets` | `list<int>` | Full condition program offsets |
| `resultOffsets` | `list<int>` | Result program offsets, including implicit no-match in slot zero |
| `conditionTypes` | `list<int>` | Fast-path condition tags |
| `conditionOperands` | `list<int>` | Fast-path condition operands |

`Bytecode` also provides reference classification helpers. Evaluation logic
lives in `Evaluator`.

`BytecodeSourceGenerator` renders this value as a PHP class with a memoized
`get(): Bytecode` method. Function entries are reconstructed through each
function's `getRegistryClass()`.

## Opcode Set

Smithy-PHP defines 55 assigned opcodes in the numeric range 0 through 55.
Opcode 48 is unused. Provider opcodes start at 56.

### Core operations

| Range | Operations |
|---|---|
| 0-7 | Constants, registers, `NOT`, and set tests |
| 8-17 | List and map construction |
| 18-23 | Templates and generic function calls |
| 24-27 | Property and index access |
| 28-33 | Boolean and equality checks |
| 34-37 | Substring, host label, URL, and URI encoding |
| 38-40 | Error, endpoint, and value returns |
| 41-46 | Split, indexing, and forward jumps |
| 47, 49-55 | Compiler fusions |

### Fused opcodes

| Value | Name | Purpose |
|---:|---|---|
| 47 | `SUBSTRING_EQ` | Compare a register substring with a constant |
| 49 | `SPLIT_GET` | Split a register and select one element |
| 50 | `SELECT_BOOL_REG` | Select a constant from a boolean register |
| 51 | `STRING_EQUALS_REG_CONST` | Compare a string register with a constant |
| 52 | `SET_REG_RETURN` | Store and return an assigned condition value |
| 53 | `BUILD_URI` | Build a URI from scheme, host, and path |
| 54 | `STRUCTN` | Build a property structure |
| 55 | `BUILD_TEMPLATE` | Render a compiled string-template record |

### Condition tags

| Value | Name |
|---:|---|
| 0 | `COND_FULL` |
| 1 | `COND_ISSET` |
| 2 | `COND_IS_TRUE` |
| 3 | `COND_IS_FALSE` |
| 4 | `COND_NOT_SET` |
| 5 | `COND_STRING_EQ_REG_CONST` |

## Evaluation

`Evaluator::evaluate()` performs these steps:

1. Copy `Bytecode::$defaultRegisters`.
2. Copy supplied inputs whose names exist in `inputRegisterMap`.
3. Walk BDD nodes until a terminal or result reference is reached.
4. Execute full condition programs only for `COND_FULL`.
5. Execute the selected result program.
6. Require the result program to return an `Endpoint`.

The BDD walker folds a negative reference into branch selection with the
complement bit. A terminal reference throws `RulesEvaluationException`
because no endpoint matched.

`runBytecode()` keeps the program counter, stack, registers, constants, and
function table in local variables. One switch dispatches core opcodes. The
default arm delegates unknown values to an optional
`OpcodeHandlerInterface`.

`RETURN_ENDPOINT` reads the URL, headers, and properties emitted by the
result program. It extracts `properties.authSchemes`, maps each scheme name
through the configured endpoint auth-scheme aliases, and returns
`EndpointAuthScheme` values with the remaining properties. The complete
rules-engine property map is retained on the resulting `Endpoint`.

## Resolution Tracing

Endpoint decision tracing is opt-in. Configure an
`EndpointResolutionTraceSinkInterface` through
`EndpointResolverParams::CONTEXT_TRACE_SINK`:

```php
$config = $config->withContext(
    EndpointResolverParams::CONTEXT_TRACE_SINK,
    $traceSink
);
```

The sink receives the merged resolver inputs and returns either:

- a fresh `EndpointResolutionTraceInterface` for the resolution
- `null` to use the normal untraced path

An accepted trace receives:

1. Effective modeled input parameters after defaults are applied.
2. Every visited BDD node, its condition result, and selected branch.
3. The matched result index and resolved endpoint.

No-match outcomes use result index `-1` and a null endpoint. Rules errors
report the matched result index with a null endpoint before the evaluator
throws.

The evaluator keeps traced and untraced BDD loops separate. The untraced path
therefore performs no trace callback check per node. A sink that returns null
retains normal endpoint caching. An accepted trace bypasses L1 and L2 cache
reads and writes so its events always describe an actual evaluation.

Trace sinks may be shared, but each returned trace belongs to one resolution.
Callbacks run synchronously on the resolving call. Endpoint parameters can
contain customer-provided values and should not be logged indiscriminately.

## Result Caching

`BytecodeEndpointResolver` caches successful `Endpoint` values. Evaluation
errors are not cached.

### L1 cache

Each resolver stores its most recent unqualified key and endpoint. Consecutive
calls with the same inputs return the stored endpoint after one string
comparison.

### L2 cache

All resolver instances share one process-wide FIFO map. The default insertion
cap is 128 entries. The constructor accepts a different `cacheSize`.

`cacheSize: 0` disables L2 reads and writes for that resolver. L1 remains
active.

Every L2 key starts with a resolver namespace. The namespace identifies:

- the `Bytecode` object
- resolver static context
- endpoint auth-scheme mappings
- the cache-key builder closure
- the optional opcode-handler identity

The bytecode component uses `spl_object_id()`. The remaining configuration is
serialized and hashed with `xxh3`. Each namespace component is length
prefixed.

Resolvers share entries only when those inputs identify the same resolver
configuration. Generated key builders return a memoized closure so clients
using the same generated bytecode can share L2 entries.

### Cache-key encoding

Only names in `Bytecode::$inputRegisterMap` contribute to the key. Values are
encoded in register order.

| Value | Encoding |
|---|---|
| Missing key | `\x00` |
| Explicit null | `\x01` |
| `false` | `\x02` |
| `true` | `\x03` |
| String | `\x04` + decimal byte length + `:` + bytes |
| Integer | `\x05` + decimal value + `;` |
| Other value | `\x06` + serialized byte length + `:` + serialized bytes |

The format has explicit type tags and unambiguous variable-width boundaries.
Embedded control bytes do not create field-boundary collisions.

### Generated key builders

`EndpointKeyBuilderGenerator` emits a closure with one inlined block per
ruleset input. The closure produces the same bytes as the resolver's generic
loop.

`EndpointKeyBuilderGeneratorTest` compares generated closures with a reference
implementation and checks collision cases. Generated service artifacts must
be regenerated when this encoding changes. Older generated builders that use
`\x1F` separators do not implement the current format.

## Pipeline Integration

The request pipeline separates execution-scoped serialization from
attempt-scoped endpoint binding:

```text
before-serialization interceptors
    -> serialize operation-relative transport request
    -> apply request override headers
    -> modify-before-retry-loop interceptors
    -> enter retry loop
        -> select modeled auth and resolve identity
        -> resolve endpoint
        -> reconcile endpoint auth
        -> bind endpoint URL and headers to the relative request
        -> reapply request override headers
        -> sign and transmit
```

Request override headers are visible to post-serialization interceptors and
become part of the retry baseline. They are reapplied after endpoint binding
on every attempt so explicit call values take precedence over endpoint
headers.

Endpoint auth schemes reconcile the modeled selection during every attempt.
When present, the endpoint list is authoritative: the auth resolver reorders
those schemes by customer preference and selects the first registered runtime
implementation. It does not add modeled schemes that the endpoint omitted.
Endpoint properties override signer properties only. If the selected scheme
changes, the pipeline resolves an identity for the replacement scheme.

See [Authentication](../auth/auth-runtime-design.md) for the complete auth selection rules
and [Client Runtime](../client-runtime-design.md) for the complete pipeline lifecycle.

## AWS Integration

AWS endpoint behavior lives in `aws-sdk-php`.

`AwsFunctions` registers:

- `aws.partition`
- `aws.parseArn`
- `aws.isVirtualHostableS3Bucket`
- `aws.stringEqualsIgnoreCase`

Generated AWS bytecode uses dedicated opcodes for the first three functions:

| Opcode | Function |
|---:|---|
| 56 | `aws.parseArn` |
| 57 | `aws.partition` |
| 58 | `aws.isVirtualHostableS3Bucket` |

`aws.stringEqualsIgnoreCase` uses generic function dispatch.

`AwsOpcodeHandler` implements opcodes 56 through 58.
`AbstractAwsClientConfig::bytecodeEndpointResolver()` creates the base
resolver with:

- `AwsOpcodeHandler`
- generated service bytecode
- generated service static context
- the generated key builder when enabled
- AWS endpoint auth-scheme aliases

`AbstractAwsClientConfig` stores `Region`, `UseFIPS`, and `UseDualStack` in the
generic `smithy.endpointParameters` config entry. The resolver has no AWS
type check.

`AwsClientConfigGenerator` uses `EndpointRulesetIntrospector` to generate
service endpoint configuration fields. It can read parameter definitions from
a raw `endpointRuleSet`, but it only wires executable bytecode when the model
also contains `endpointBdd`.

Dynamic AWS clients register `AwsFunctions` through their protected runtime
hook. They use generic function calls because dynamic compilation does not
receive the generated extended-opcode map or an `AwsOpcodeHandler`.

## Extension Points

### Add a rules function

1. Implement `RulesFunctionInterface`.
2. Register it in `CodegenSettings::$rulesFunctions` for generated clients.
3. Pass it through `endpointFunctions` for a direct dynamic client, or add it
   to a provider subclass's default runtime.
4. Return a registry class from `getRegistryClass()` so generated bytecode can
   reconstruct the function.

### Add a generated provider opcode

1. Reserve an opcode value from 56 through 127.
2. Map the function name in `CodegenSettings::$extendedOpcodes`.
3. Implement `OpcodeHandlerInterface`.
4. Pass the handler when constructing `BytecodeEndpointResolver`.

The function implementation must remain registered. Dynamic compilation and
generated runs that omit the extended-opcode mapping use generic function
dispatch. Bytecode compiled with a provider opcode requires the matching
handler.

## File Layout

**Public endpoint contracts**

- `smithy-php/src/Client/Endpoint/Endpoint.php`
- `smithy-php/src/Client/Endpoint/EndpointAuthScheme.php`
- `smithy-php/src/Client/Endpoint/EndpointResolverInterface.php`
- `smithy-php/src/Client/Endpoint/EndpointResolverParams.php`
- `smithy-php/src/Client/Endpoint/StaticEndpointResolver.php`

**Bytecode runtime**

- `smithy-php/src/Client/Endpoint/Bytecode/Bytecode.php`
- `smithy-php/src/Client/Endpoint/Bytecode/BytecodeEndpointResolver.php`
- `smithy-php/src/Client/Endpoint/Bytecode/EndpointResolutionTraceInterface.php`
- `smithy-php/src/Client/Endpoint/Bytecode/EndpointResolutionTraceSinkInterface.php`
- `smithy-php/src/Client/Endpoint/Bytecode/Evaluator.php`
- `smithy-php/src/Client/Endpoint/Bytecode/Opcodes.php`
- `smithy-php/src/Client/Endpoint/Bytecode/OpcodeHandlerInterface.php`
- `smithy-php/src/Client/Endpoint/Bytecode/StdFunctions.php`

**Compiler**

- `smithy-php/src/Client/Endpoint/Bytecode/Compiler/BddCompiler.php`
- `smithy-php/src/Client/Endpoint/Bytecode/Compiler/BytecodeSourceGenerator.php`

**Codegen**

- `smithy-php/src/Codegen/Generator/EndpointBytecodeGenerator.php`
- `smithy-php/src/Codegen/Generator/EndpointKeyBuilderGenerator.php`
- `smithy-php/src/Codegen/Generator/EndpointRulesetIntrospector.php`

**Dynamic clients**

- `smithy-php/src/Client/Dynamic/DynamicClientFactory.php`
- `smithy-php/src/Client/Dynamic/DynamicClientRuntime.php`

**AWS provider implementation**

- `aws-sdk-php/src/Core/Endpoint/AwsFunctions.php`
- `aws-sdk-php/src/Core/Endpoint/Partitions.php`
- `aws-sdk-php/src/Core/Endpoint/Bytecode/AwsOpcodes.php`
- `aws-sdk-php/src/Core/Endpoint/Bytecode/AwsOpcodeHandler.php`
- `aws-sdk-php/src/Core/Client/AbstractAwsClientConfig.php`

## Testing and Validation

The endpoint suites cover:

- BDD compilation and opcode behavior
- standard rules functions
- bytecode source generation and generated value reconstruction
- generated key-builder equivalence and collision resistance
- L1 behavior, L2 sharing, and resolver namespace isolation
- dynamic modeled endpoints
- input, operation, static, resolver, and client context precedence
- endpoint auth-scheme mapping
- AWS client-config generation
- official Lambda endpoint vectors against compiled and generated bytecode

[Endpoint Testing and Validation](testing-and-validation.md) records
the focused suites, benchmark results, and remaining service coverage.

## Related Documents

- [Client Runtime](../client-runtime-design.md)
- [Authentication](../auth/auth-runtime-design.md)
- [Dynamic Client](../dynamic/dynamic-client-design.md)
- [Code Generation](../../codegen/smithy-php-code-generation.md)
- [Endpoint Testing and Validation](testing-and-validation.md)
