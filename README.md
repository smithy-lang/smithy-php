# <img alt="Smithy" src="https://github.com/smithy-lang/smithy/blob/main/docs/_static/smithy-anvil.svg?raw=true" width="32"> Smithy PHP

[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](LICENSE)

[Smithy](https://smithy.io/2.0/index.html) code generators for clients, servers, and shapes for [PHP](https://www.php.net/.

> [!IMPORTANT]
> All interfaces are subject to change.
> All modules are still in developer-preview and may contain bugs.
> No guarantee is made about their API stability. Unstable modules are marked with a warning in their
> `README.md` and with the `@SmithyUnstableApi` annotation..

---

This repository contains two major components:
1. Smithy code generators for PHP
2. Core modules and interfaces for building services and clients in PHP

These components facilitate generating clients, servers stubs, and types for a Smithy service.
The [Codegen](./Codegen) namespace contains the source code for generating clients.

> [!NOTE]
> This repository does not contain any generated clients, such as for S3 or other AWS services.
> These are the tools that facilitate the generation of those clients and non-AWS Smithy clients.

## Getting Started

If this is your first time using Smithy, follow the [Smithy Quickstart guide](https://smithy.io/2.0/quickstart.html)
to learn the basics and create a simple Smithy model.

For a guided introduction to this project, see the **TODO:** write quickstart guide.

## Core modules and Interfaces

**TODO:** write design for package structure

## Security

If you discover a potential security issue in this project we ask that you notify AWS/Amazon Security via our
[vulnerability reporting page](http://aws.amazon.com/security/vulnerability-reporting/).
Please do **not** create a public GitHub issue.

## License

This project is licensed under the Apache-2.0 License.
