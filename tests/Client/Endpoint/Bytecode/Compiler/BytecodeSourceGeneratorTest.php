<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Smithy\Client\Endpoint\Bytecode\{
    Bytecode,
    Compiler\BytecodeSourceGenerator,
    RulesFunctionInterface,
    StdFunctions
};

#[CoversClass(BytecodeSourceGenerator::class)]
final class BytecodeSourceGeneratorTest extends TestCase
{
    public function testGeneratedSourceReconstructsBytecodeSingleton(): void
    {
        $bytecode = new Bytecode(
            code: \range(0, 20),
            constants: [
                null,
                true,
                7,
                1.25,
                "quoted'value",
                'array (',
                ['nested' => [1, false]]
            ],
            functions: [
                StdFunctions::get('not'),
                StdFunctions::get('stringEquals')
            ],
            registers: [
                [
                    'name' => 'Region',
                    'builtIn' => 'smithy.example#Region',
                    'default' => 'us-east-1',
                    'required' => true,
                    'type' => 'string'
                ]
            ],
            inputRegisterMap: ['Region' => 0],
            bddNodes: [0, 100_000_000, -1],
            bddRoot: 2,
            conditionOffsets: [0],
            resultOffsets: [4],
            conditionTypes: [1],
            conditionOperands: [0]
        );
        $source = (new BytecodeSourceGenerator())->generate(
            $bytecode,
            'Smithy\\Tests\\Generated\\Endpoint',
            'GeneratedBytecodeFixture',
            'models/example.json'
        );

        self::assertStringContainsString(
            '@source models/example.json',
            $source
        );
        self::assertStringContainsString(
            '\\Smithy\\Client\\Endpoint\\Bytecode\\StdFunctions::get',
            $source
        );
        self::assertStringNotContainsString(
            'use Smithy\\Client\\Endpoint\\Bytecode\\StdFunctions;',
            $source
        );

        eval($this->withoutOpeningTag($source));

        $class = 'Smithy\\Tests\\Generated\\Endpoint'
            . '\\GeneratedBytecodeFixture';
        $first = $class::get();
        $second = $class::get();

        self::assertSame($first, $second);
        self::assertSame($bytecode->code, $first->code);
        self::assertSame($bytecode->constants, $first->constants);
        self::assertSame($bytecode->registers, $first->registers);
        self::assertSame(
            ['not', 'stringEquals'],
            \array_map(
                static fn($function) => $function->getName(),
                $first->functions
            )
        );
        self::assertSame($bytecode->bddNodes, $first->bddNodes);
        self::assertSame($bytecode->bddRoot, $first->bddRoot);
        self::assertSame(
            $bytecode->conditionOffsets,
            $first->conditionOffsets
        );
        self::assertSame($bytecode->resultOffsets, $first->resultOffsets);
        self::assertSame(
            $bytecode->conditionTypes,
            $first->conditionTypes
        );
        self::assertSame(
            $bytecode->conditionOperands,
            $first->conditionOperands
        );
    }

    public function testGeneratedSourceUsesFullyQualifiedRegistryCalls(): void
    {
        $bytecode = new Bytecode(
            code: [],
            constants: [],
            functions: [
                $this->functionFromRegistry('VendorA\\Functions', 'one'),
                $this->functionFromRegistry('VendorB\\Functions', 'two')
            ],
            registers: [],
            inputRegisterMap: [],
            bddNodes: [],
            bddRoot: 1,
            conditionOffsets: [],
            resultOffsets: [],
            conditionTypes: [],
            conditionOperands: []
        );

        $source = (new BytecodeSourceGenerator())->generate(
            $bytecode,
            'Smithy\\Tests\\Generated\\Endpoint',
            'RegistryCollisionBytecodeFixture'
        );

        self::assertStringContainsString(
            "\\VendorA\\Functions::get('one')",
            $source
        );
        self::assertStringContainsString(
            "\\VendorB\\Functions::get('two')",
            $source
        );
        self::assertStringNotContainsString('use VendorA\\Functions;', $source);
        self::assertStringNotContainsString('use VendorB\\Functions;', $source);
    }

    public function testOmitsSourceAndUsesCompactEmptyTables(): void
    {
        $source = (new BytecodeSourceGenerator())->generate(
            $this->emptyBytecode(),
            'Smithy\\Tests\\Generated\\Endpoint',
            'EmptyBytecodeFixture'
        );

        self::assertStringNotContainsString('@source', $source);
        self::assertStringContainsString('code: []', $source);
        self::assertStringContainsString('constants: []', $source);
        self::assertStringNotContainsString('use StdFunctions;', $source);
    }

    public function testRejectsUnsupportedConstantType(): void
    {
        $bytecode = new Bytecode(
            code: [],
            constants: [new \stdClass()],
            functions: [],
            registers: [],
            inputRegisterMap: [],
            bddNodes: [],
            bddRoot: 1,
            conditionOffsets: [],
            resultOffsets: [],
            conditionTypes: [],
            conditionOperands: []
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Unsupported constant type: stdClass'
        );

        (new BytecodeSourceGenerator())->generate(
            $bytecode,
            'Smithy\\Tests\\Generated\\Endpoint',
            'InvalidBytecodeFixture'
        );
    }

    private function emptyBytecode(): Bytecode
    {
        return new Bytecode(
            code: [],
            constants: [],
            functions: [],
            registers: [],
            inputRegisterMap: [],
            bddNodes: [],
            bddRoot: 1,
            conditionOffsets: [],
            resultOffsets: [],
            conditionTypes: [],
            conditionOperands: []
        );
    }

    private function withoutOpeningTag(string $source): string
    {
        return (string) \preg_replace('/^<\?php\s*/', '', $source);
    }

    private function functionFromRegistry(
        string $registry,
        string $name
    ): RulesFunctionInterface
    {
        return new class($registry, $name) implements RulesFunctionInterface {
            public function __construct(
                private readonly string $registry,
                private readonly string $name
            ) {}

            public function getName(): string
            {
                return $this->name;
            }

            public function getRegistryClass(): string
            {
                return $this->registry;
            }

            public function getArgumentCount(): int
            {
                return 0;
            }

            public function apply0(): mixed
            {
                return null;
            }

            public function apply1(mixed $a0): mixed
            {
                return null;
            }

            public function apply2(mixed $a0, mixed $a1): mixed
            {
                return null;
            }

            public function apply3(mixed $a0, mixed $a1, mixed $a2): mixed
            {
                return null;
            }

            /**
             * @param list<mixed> $args
             */
            public function apply(array $args): mixed
            {
                return null;
            }
        };
    }
}
