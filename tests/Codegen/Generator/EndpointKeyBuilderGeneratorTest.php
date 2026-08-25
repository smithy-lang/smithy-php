<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Generator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Endpoint\Bytecode\Bytecode;
use Smithy\Client\Endpoint\Bytecode\Compiler\BddCompiler;
use Smithy\Client\Endpoint\Bytecode\StdFunctions;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\EndpointKeyBuilderGenerator;

/**
 * Verifies the generated per-service key-builder closure produces
 * byte-identical output to the resolver's generic loop.
 *
 * The class under test emits PHP source. Rather than parse the emitted
 * text back into an AST, we evaluate the closure it emits (via `eval`)
 * against a matrix of representative input maps and compare the result
 * to a hand-rolled reference implementation of the same wire format.
 * If the two ever diverge, the L2 cache would silently key on stale
 * data - this test locks that shape down.
 */
#[CoversClass(EndpointKeyBuilderGenerator::class)]
final class EndpointKeyBuilderGeneratorTest extends TestCase
{
    /**
     * @return list<array{list<string>, array<string, mixed>}>
     */
    public static function fieldMatrix(): array
    {
        return [
            // (input-name order, sample input map)
            // Trivial: single string.
            [['Region'], ['Region' => 'us-east-1']],
            // Two strings.
            [
                ['Region', 'Bucket'],
                ['Region' => 'us-west-2', 'Bucket' => 'my-bucket']
            ],
            // Boolean handling.
            [['Region', 'UseFIPS', 'UseDualStack'], [
                'Region' => 'us-east-1',
                'UseFIPS' => true,
                'UseDualStack' => false
            ]],
            // Missing values (map lacks the key) - must encode to \x00.
            [['Region', 'Bucket', 'ForcePathStyle'], ['Region' => 'us-east-1']],
            // Explicit-null values have their own encoding.
            [['Region', 'Bucket'], ['Region' => 'eu-west-1', 'Bucket' => null]],
            // Full S3 shape.
            [
                [
                    'Region', 'Bucket', 'UseFIPS', 'UseDualStack',
                    'ForcePathStyle', 'UseArnRegion', 'Accelerate',
                    'UseGlobalEndpoint', 'Endpoint'
                ],
                [
                    'Region' => 'us-west-2',
                    'Bucket' => 'my-bucket',
                    'UseFIPS' => false,
                    'UseDualStack' => false,
                    'ForcePathStyle' => false,
                    'UseArnRegion' => false,
                    'Accelerate' => false,
                    'UseGlobalEndpoint' => false,
                    'Endpoint' => null
                ]
            ],
            // Ordering matters: the same value in a different slot
            // must produce a different key. Cover with a shuffled
            // input map.
            [['Alpha', 'Beta'], ['Beta' => 'first', 'Alpha' => 'second']],
            // Composite values use a length-prefixed serialized form.
            [['Tags'], ['Tags' => ['one', 'two']]]
        ];
    }

    /**
     * @param list<string>          $inputNames
     * @param array<string, mixed>  $inputs
     */
    #[DataProvider('fieldMatrix')]
    public function testGeneratedClosureMatchesReference(
        array $inputNames,
        array $inputs
    ): void
    {
        $closure = $this->buildGeneratedClosure($inputNames);
        $actual = $closure($inputs);
        $expected = $this->referenceKeyBuilder($inputNames, $inputs);

        $this->assertSame(
            $expected,
            $actual,
            'Generated closure output diverged from reference for input '
                . \var_export($inputs, true)
        );
    }

    public function testEmptyInputRegisterMapEmitsEmptyKey(): void
    {
        $closure = $this->buildGeneratedClosure([]);
        $this->assertSame('', $closure([]));
        $this->assertSame('', $closure(['ignored' => 'value']));
    }

    /**
     * @return iterable<string, array{
     *     list<string>,
     *     array<string, mixed>,
     *     array<string, mixed>
     * }>
     */
    public static function distinctInputMaps(): iterable
    {
        yield 'embedded separator' => [
            ['Alpha', 'Beta'],
            ['Alpha' => "a\x1Fb", 'Beta' => 'c'],
            ['Alpha' => 'a', 'Beta' => "b\x1Fc"]
        ];
        yield 'missing versus null' => [
            ['Alpha'],
            [],
            ['Alpha' => null]
        ];
        yield 'boolean versus control byte' => [
            ['Alpha'],
            ['Alpha' => false],
            ['Alpha' => "\x02"]
        ];
    }

    /**
     * @param list<string> $inputNames
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     *
     * @return void
     */
    #[DataProvider('distinctInputMaps')]
    public function testGeneratedKeysDoNotCollide(
        array $inputNames,
        array $left,
        array $right
    ): void
    {
        $closure = $this->buildGeneratedClosure($inputNames);

        $this->assertNotSame($closure($left), $closure($right));
    }

    /**
     * Compile-then-eval the generated closure text and hand back the
     * closure it produces. Uses a trivial `Bytecode` whose only meaningful
     * shape is `inputRegisterMap` - that's the only thing the generator
     * consults.
     *
     * @param list<string> $inputNames
     * @return \Closure(array<string, mixed>): string
     */
    private function buildGeneratedClosure(array $inputNames): \Closure
    {
        $bytecode = $this->trivialBytecodeWithInputs($inputNames);
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Tests\\Codegen\\Tmp',
            outputDir: '/tmp'
        );
        $generator = new EndpointKeyBuilderGenerator($settings);
        $file = $generator->generate('TestSvc', $bytecode);

        // Strip `<?php` and the namespace declaration so we can eval
        // the class body directly under a unique test-run namespace.
        // Uniqueness matters: PHPUnit reuses the process, and PHP
        // rejects re-declaration of a class with the same FQCN, so we
        // use a monotonic counter to give each call a fresh
        // sub-namespace even when the same input-name tuple is re-run
        // (which the data provider legitimately does for related cases).
        static $counter = 0;
        $suffix = 'N' . (++$counter);
        $source = $file->content;
        $source = \preg_replace('/^<\?php\s*/', '', $source);
        $source = \preg_replace(
            '/^declare\(strict_types=1\);\s*/m',
            '',
            $source
        );
        $source = \preg_replace(
            '/^namespace [^;]+;/m',
            "namespace Smithy\\Tests\\Codegen\\Tmp\\Run{$suffix};",
            $source
        );

        eval($source);

        /** @var class-string $fqcn */
        $fqcn = "Smithy\\Tests\\Codegen\\Tmp\\Run{$suffix}\\"
            . 'TestSvcEndpointKeyBuilder';

        return $fqcn::build();
    }

    /**
     * Reference implementation of the wire format the generated closure
     * has to produce. Kept intentionally simple so a bug in the
     * generator surfaces as a diff against clear PHP.
     *
     * @param list<string>         $inputNames
     * @param array<string, mixed> $inputs
     */
    private function referenceKeyBuilder(array $inputNames, array $inputs): string
    {
        $key = '';
        foreach ($inputNames as $name) {
            if (!\array_key_exists($name, $inputs)) {
                $key .= "\x00";
                continue;
            }

            $value = $inputs[$name];
            if ($value === null) {
                $key .= "\x01";
            } elseif ($value === false) {
                $key .= "\x02";
            } elseif ($value === true) {
                $key .= "\x03";
            } elseif (\is_string($value)) {
                $key .= "\x04" . \strlen($value) . ':' . $value;
            } elseif (\is_int($value)) {
                $key .= "\x05" . $value . ';';
            } else {
                $encoded = \serialize($value);
                $key .= "\x06" . \strlen($encoded) . ':' . $encoded;
            }
        }

        return $key;
    }

    /**
     * Build a `Bytecode` whose `inputRegisterMap` matches the given
     * order. Everything else (nodes, code, results) is minimal - the
     * generator only touches `inputRegisterMap`.
     *
     * @param list<string> $inputNames
     */
    private function trivialBytecodeWithInputs(array $inputNames): Bytecode
    {
        $parameters = [];
        foreach ($inputNames as $name) {
            $parameters[$name] = ['type' => 'String'];
        }

        $nodeBytes = \pack('N*', 0, 1, -1);
        $trait = [
            'parameters' => $parameters,
            'conditions' => [],
            'results' => [
                [
                    'type' => 'endpoint',
                    'endpoint' => ['url' => 'https://example.com']
                ]
            ],
            'nodes' => \base64_encode($nodeBytes),
            'nodeCount' => 1,
            'root' => 100_000_001
        ];

        return (new BddCompiler(StdFunctions::all()))->compile($trait);
    }
}
