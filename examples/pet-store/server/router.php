<?php

declare(strict_types=1);

use Smithy\Examples\PetStore\Server\{
    PetRepository,
    PetStoreApplication
};

require \dirname(__DIR__) . '/vendor/autoload.php';

$headers = [];
foreach (\getallheaders() as $name => $value) {
    $headers[\strtolower($name)] = $value;
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = \parse_url($requestUri, PHP_URL_PATH);
$body = \file_get_contents('php://input');

$dataFile = \getenv('PET_STORE_DATA_FILE');
if ($dataFile === false || $dataFile === '') {
    $dataFile = __DIR__ . '/data/pets.json';
}

$response = (new PetStoreApplication(
    new PetRepository($dataFile)
))->handle(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    \is_string($path) ? $path : '/',
    $headers,
    $body === false ? '' : $body
);

\ini_set('default_mimetype', '');
\http_response_code($response->statusCode);
\header_remove('Content-Type');
foreach ($response->headers as $name => $value) {
    \header("{$name}: {$value}");
}

echo $response->body;
