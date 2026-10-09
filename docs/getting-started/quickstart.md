# Smithy PHP quickstart

This guide builds a generated PHP client from a Smithy model and uses it to
call a local RPCv2 CBOR PetStore service.

## Prerequisites

Install:

- 64-bit PHP 8.2 or later
- Composer
- the PHP cURL extension
- the PHP mbstring extension

The committed JSON model means you do not need the Smithy CLI unless you want
to change the model.

## Install the example

Clone Smithy PHP and enter the example project:

```shell
git clone https://github.com/smithy-lang/smithy-php.git
cd smithy-php/examples/pet-store
composer install
```

The example Composer project uses the Smithy PHP package from the repository
checkout.

## Generate the client

```shell
composer generate
```

This reads `model/pet-store.json` and writes generated PHP to `generated/`.
The generated output includes:

- `PetStoreClient`
- operation input and output classes
- the `PetRecord` resource representation
- enums and union variants
- modeled exceptions
- lazily initialized runtime schemas

Generated files are build artifacts and are not committed.

## Start the local service

```shell
composer serve
```

The service listens on `http://127.0.0.1:8080` and implements RPCv2 CBOR using
the same schemas and codec as the generated client. Its data is stored in
`server/data/pets.json` so create, update, and delete operations persist across
requests.

This server is a development fixture. It binds only to loopback and is not
intended for production deployment.

## Run the CRUD demonstration

In another terminal:

```shell
composer demo
```

The script:

1. creates a pet
2. retrieves it
3. updates its status
4. lists the stored pets
5. deletes the pet
6. confirms that a modeled `PetNotFoundErrorException` is returned

The generated client is configured with the RPCv2 CBOR protocol, cURL
transport, and a static local endpoint:

```php
$client = new PetStoreClient(new ClientConfig(
    protocol: new RpcV2CborProtocol(),
    httpClient: new CurlClient(),
    endpointResolver: new StaticEndpointResolver(
        new Endpoint('http://127.0.0.1:8080')
    )
));
```

The service does not require authentication.

## Construct input from arrays

Generated classes remain strongly typed, while
`ArrayShapeSerializer::fromArray()` makes existing array-oriented code easy
to adapt:

```php
$input = ArrayShapeSerializer::fromArray(CreatePetInput::class, [
    'pet' => [
        'name' => 'Milo',
        'species' => 'dog',
        'traits' => ['playful', 'friendly'],
        'details' => [
            'dog' => [
                'breed' => 'Mixed Breed',
                'favoriteToy' => 'tennis ball'
            ]
        ]
    ]
]);

$output = $client->createPet($input);
```

Nested structures, collections, enums, unions, big numbers, and timestamps
are hydrated according to the generated schemas.

## Inspect output as objects or arrays

Generated output shapes support property access and read-only array access:

```php
echo $output->pet?->name;
// Milo

echo $output['pet']->name;
// Milo
```

Convert the complete modeled value to a recursive array when integrating with
array-based application code:

```php
$data = ArrayShapeSerializer::toArray($output);

echo $data['pet']['species'];
// dog
```

## Run concurrent operations

Smithy PHP uses fibers and a shared event loop for cooperative concurrency.
The generated methods remain ordinary synchronous-looking PHP methods:

```php
$outputs = Async::all(
    static fn() => $client->getPet(
        new GetPetInput(petId: 'pet-1')
    ),
    static fn() => $client->listPets(
        new ListPetsInput(pageSize: 10)
    )
);

echo $outputs[0]->pet?->name;
// Scully
```

Run the included example:

```shell
composer demo:async
```

## Pagination

`ListPets` carries Smithy's `@paginated` trait, so the generated client also
provides `listPetsPaginated()`:

```php
foreach (
    $client->listPetsPaginated(new ListPetsInput(pageSize: 5))
    as $page
) {
    foreach ($page->pets ?? [] as $pet) {
        echo $pet->name . PHP_EOL;
    }
}
```

## Change the Smithy model

The readable model is in `model/main.smithy`. If the Smithy CLI is installed,
rebuild the committed JSON artifact and regenerate:

```shell
composer model
composer generate
```

The model covers Smithy services, resources, operations, structures, unions,
enums, integer enums, scalar numeric types, blobs, timestamps, lists, sparse
lists, maps, sparse maps, pagination, defaults, constraints, and modeled
errors. Document shapes are omitted because RPCv2 CBOR does not permit them in
operation payloads.

## Reset and verify

Reset the seeded store:

```shell
composer reset
```

Run an end-to-end smoke test:

```shell
composer test
```
