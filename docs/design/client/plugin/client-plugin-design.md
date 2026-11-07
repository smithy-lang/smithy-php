# Client Plugin Design

**Status:** Current as of September 30, 2026

**Package:** `smithy/smithy-php` (`Smithy\Client\Plugin\`)

## Scope

Plugins package reusable, construction-time or call-scoped transformations of
immutable `ClientConfig`. They are the composition layer for client features.
Features that need request lifecycle behavior normally register an
interceptor from `configure()`.

The namespace [README](../../../../src/Client/Plugin/README.md) is the usage
guide. This document records resolution and ordering invariants.

## Main Types

| Type | Responsibility |
|---|---|
| `PluginInterface` | Configure a client, contribute children, and declare a phase. |
| `AbstractPlugin` | Supply an empty child list and the `Apply` phase. |
| `PluginCollection` | Store one instance per concrete class in registration order. |
| `PluginResolver` | Expand children, filter plugins, deduplicate, and sort them. |
| `PluginState` | Persist registrations, applied classes, and the active predicate. |
| `ResolvedPlugins` | Apply an ordered resolution result to `ClientConfig`. |
| `DefaultPlugin` | Root framework defaults. |
| `AutoPlugin` | Bridge Composer-registered automatic plugins into default resolution. |

## Resolution

`PluginState::defaults()` begins with `DefaultPlugin`. That plugin contributes
idempotency-token behavior and the automatic-plugin parent. Explicit
constructor plugins, generated defaults, and call-scoped plugins are merged
into the same persisted state.

Resolution performs these steps:

1. Merge newly registered plugins into the existing collection.
2. Traverse top-level plugins and their children.
3. Reject a plugin whose concrete class was already applied.
4. Evaluate the configured predicate before traversing that plugin's children.
5. Mark accepted concrete classes as applied.
6. Sort accepted plugins by `PluginPhase`, then discovery sequence.
7. Persist the updated state before calling `configure()` in resolved order.

Marking a class before visiting children prevents cycles and guarantees
at-most-once application. Children retain their own phase and predicate
decision rather than inheriting the parent's placement.

## Deduplication

Concrete plugin class is the identity boundary. The first registered instance
of a class wins in `PluginCollection`, and an applied class remains applied as
configuration is copied or reused.

This policy keeps plugin composition strongly typed without requiring string
keys. It also means parameterized instances of one concrete class are
alternatives, not additive registrations. A feature that needs multiple
independent instances should model those instances inside one plugin or use
distinct plugin classes.

## Ordering

Plugins are sorted by these phases:

1. `First`
2. `BeforeDefaults`
3. `Defaults`
4. `AfterDefaults`
5. `BeforeApply`
6. `Apply`
7. `AfterApply`
8. `Last`

Registration order is stable within a phase. Framework and automatic
registrations are discovered before user and generated registrations. A user
plugin can therefore replace a generated default of the same class when it is
registered first, while an automatic plugin can win the same-class duplicate
before both.

## Automatic Plugins

`ComposerAutoPluginRegistry` stores classes implementing
`AutoPluginInterface`. `AutoPlugin` instantiates the registered no-argument
classes once per process and contributes them as children of the default
plugin.

Disabling automatic plugins is a predicate decision made before those
children are instantiated. Already applied plugins cannot be removed from a
derived configuration.

## Call-Scoped Plugins

`AbstractClient` applies request-override plugins against the client's
resolved `PluginState`. Construction plugins therefore are not applied again.
The resulting `ClientConfig` exists for one call and is processed before
interceptor `modifyBeforeCall()` hooks.

Call-scoped plugins can replace protocols, transports, endpoint resolvers,
retry strategies, auth behavior, or interceptor chains without mutating the
client's stored configuration.

## Built-in Ordering Invariant

Request compression must precede required HTTP checksum calculation so the
checksum describes the transmitted representation. The compression plugin
runs in `BeforeApply`, while the checksum plugin runs in `Apply`, so phase
ordering preserves this invariant regardless of registration order.

## Concurrency

Plugin instances can be shared across clients and calls. Implementations
should be immutable or otherwise safe for concurrent use. Per-call mutable
state belongs in interceptors, contexts, or application-owned request state.

## Validation

Tests cover collection immutability, concrete-class deduplication, child
expansion, cycles, phases, predicates, automatic registration, persisted
state, generated defaults, and call-scoped application.

Run:

```shell
vendor/bin/phpunit tests/Client/Plugin
```

## Related Designs

- [Client Runtime](../client-runtime-design.md)
- [Interceptors](../interceptor/client-interceptor-design.md)
- [Dynamic Client](../dynamic/dynamic-client-design.md)
