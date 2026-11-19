# Smithy PHP Design Documentation

The design tree mirrors the runtime namespace hierarchy. Namespace READMEs
under `src/` explain how to use a component. Documents here explain ownership,
invariants, lifecycle, performance decisions, and extension boundaries.

## Components

- [Async runtime](async/async-runtime-and-http-concurrency.md)
- [Authentication](auth/auth-runtime-design.md)
- [Client runtime](client/client-runtime-design.md)
  - [Dynamic client](client/dynamic/dynamic-client-design.md)
  - [Endpoint resolution](client/endpoint/endpoint-resolution.md)
  - [Interceptors](client/interceptor/client-interceptor-design.md)
  - [Pagination](client/pagination/pagination-runtime-design.md)
  - [Plugins](client/plugin/client-plugin-design.md)
  - [Retries](client/retry/client-retry-design.md)
  - [Waiters](client/waiter/waiter-runtime-design.md)
- [Codecs](codec/serialization-codecs.md)
- [Code generation](codegen/smithy-php-code-generation.md)
  - [Shape to PHP type mapping](codegen/smithy-php-shape-to-php-type-mapping.md)
- [Core schemas, shapes, and documents](core/core-runtime-design.md)
- [HTTP runtime](http/http-runtime-design.md)
  - [Curl client](http/client/curl-http-transport.md)
  - [Messages and streaming](http/message/http-messages-and-streaming.md)
- [Protocols](protocol/protocol-design.md)
- [Serialization and deserialization](serde/serialization-and-deserialization.md)

Evidence and follow-up documents live beside the architecture they support.
Historical or superseded proposals are intentionally not treated as canonical
component designs.
