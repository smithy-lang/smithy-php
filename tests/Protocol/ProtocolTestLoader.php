<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol;

/**
 * Read client protocol tests from a Smithy JSON AST.
 */
final class ProtocolTestLoader
{
    private array $model;

    public function __construct(string $modelPath)
    {
        $contents = \file_get_contents($modelPath);
        if ($contents === false) {
            throw new \RuntimeException(
                "Unable to read protocol test model: {$modelPath}"
            );
        }

        $this->model = \json_decode(
            $contents,
            true,
            512,
            \JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRequestTests(string $operationId): array
    {
        return $this->getClientTests(
            $operationId,
            'smithy.test#httpRequestTests'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getResponseTests(string $operationId): array
    {
        return $this->getClientTests(
            $operationId,
            'smithy.test#httpResponseTests'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getErrorResponseTests(string $operationId): array
    {
        $operation = $this->model['shapes'][$operationId] ?? null;
        if (!\is_array($operation)) {
            return [];
        }

        $tests = [];
        foreach ($operation['errors'] ?? [] as $error) {
            $errorId = $error['target'] ?? null;
            if (!\is_string($errorId)) {
                continue;
            }

            $shape = $this->model['shapes'][$errorId] ?? null;
            if (!\is_array($shape)) {
                continue;
            }

            $shapeTests = $shape['traits']
                ['smithy.test#httpResponseTests'] ?? [];
            foreach ($shapeTests as $test) {
                if (!$this->appliesToClient($test)) {
                    continue;
                }

                $test['errorShapeId'] = $errorId;
                $tests[] = $test;
            }
        }

        return $tests;
    }

    /**
     * @return list<string>
     */
    public function getOperationIds(): array
    {
        $operations = [];
        foreach ($this->model['shapes'] ?? [] as $id => $shape) {
            if (($shape['type'] ?? null) === 'operation') {
                $operations[] = $id;
            }
        }

        return $operations;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getClientTests(
        string $operationId,
        string $traitId
    ): array
    {
        $shape = $this->model['shapes'][$operationId] ?? null;
        if (!\is_array($shape)) {
            return [];
        }

        $tests = $shape['traits'][$traitId] ?? [];

        return \array_values(\array_filter(
            $tests,
            fn(array $test): bool => $this->appliesToClient($test)
        ));
    }

    private function appliesToClient(array $test): bool
    {
        $appliesTo = $test['appliesTo'] ?? null;

        return $appliesTo === null || $appliesTo === 'client';
    }
}
