# PetStore example

This example generates a Smithy PHP client for an RPCv2 CBOR `PetStore`
service and runs it against a small local PHP server. It demonstrates:

- generated clients, shapes, schemas, enums, unions, and modeled errors
- create, read, update, list, and delete resource operations
- RPCv2 CBOR request and response handling
- paginated operations
- typed objects, array access, and array conversion
- concurrent calls with `Smithy\Async\Async`

The local server stores its data in `server/data/pets.json`. It is intended
only for development, demonstrations, and security testing.

## Requirements

- 64-bit PHP 8.2 or later
- Composer
- the PHP cURL extension
- the PHP mbstring extension
- the Smithy CLI only when changing the `.smithy` model

## Setup

From this directory:

```shell
composer install
composer generate
```

The generator reads the committed `model/pet-store.json` artifact, so normal
users do not need the Smithy CLI.

## Run the server

```shell
composer serve
```

The service listens on `http://127.0.0.1:8080`.

In another terminal:

```shell
composer demo
composer demo:async
```

Reset the local data at any time:

```shell
composer reset
```

## Change the model

Edit `model/main.smithy`, then rebuild the committed JSON model and generated
PHP:

```shell
composer model
composer generate
```

`SMITHY_CLI` can name a non-default Smithy executable:

```shell
SMITHY_CLI=/path/to/smithy composer model
```

Generated PHP, Composer dependencies, Smithy build output, and local server
data are intentionally excluded from Git.

## Verify the example

The smoke test starts the local server on an available port and exercises the
generated client end to end:

```shell
composer test
```
