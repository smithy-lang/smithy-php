# <img alt="Smithy" src="https://github.com/smithy-lang/smithy/blob/main/docs/_static/smithy-anvil.svg?raw=true" width="32"> Smithy PHP

[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](LICENSE)

[Smithy PHP](https://github.com/smithy-lang/smithy-php) provides code
generation and runtime libraries for building PHP clients and types from
[Smithy](https://smithy.io/2.0/index.html) models.

> [!IMPORTANT]
> Smithy PHP is in developer preview. APIs may change before the first stable
> release.

This repository contains:

- a [code generator](src/Codegen/README.md) for clients, types, operation
  metadata, runtime schemas, and endpoint rules
- a [client runtime](src/Client/README.md) for endpoint resolution,
  authentication, retries, plugins, interceptors, and operation execution
- [protocol](src/Protocol/README.md) and [codec](src/Codec/README.md)
  implementations for converting modeled values to and from HTTP messages
- [HTTP message](src/Http/Message/README.md) and
  [HTTP client](src/Http/Client/README.md) implementations
- [core schema and shape](src/Core/README.md) types and
  [fiber-based concurrency](src/Async/README.md) helpers

> [!NOTE]
> This repository contains the generator and runtime. It does not contain
> generated clients for AWS or other services.

## Getting Started

If you are new to Smithy, follow the
[Smithy quickstart](https://smithy.io/2.0/quickstart.html) to create and build
a model.

See the [code generator guide](src/Codegen/README.md) to generate a client from
a Smithy JSON AST. Each runtime component also has a README with its contracts
and usage.

To install dependencies and run the test suite from a source checkout:

```shell
composer install
composer test
```

## Security

See the [security policy](SECURITY.md) to report a potential vulnerability.

## License

This project is licensed under the Apache-2.0 License.
