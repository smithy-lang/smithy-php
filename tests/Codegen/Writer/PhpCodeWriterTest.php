<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Writer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Writer\PhpCodeWriter;

final class PhpCodeWriterTest extends TestCase
{
    public function testEmptyWriterProducesHeaderOnlyOutput(): void
    {
        $writer = new PhpCodeWriter();

        $this->assertSame(
            "<?php\n\ndeclare(strict_types=1);\n\n",
            $writer->toFileContent()
        );
    }

    public function testNamespacePrintedWhenSet(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');

        $this->assertSame(
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace Aws\\Example;\n\n",
            $writer->toFileContent()
        );
    }

    public function testWriteLineAppendsIndentedLineWithTrailingNewline(): void
    {
        $writer = new PhpCodeWriter();
        $writer->writeLine('$foo = 1;');

        // No indent -> indent string is empty prefix.
        $this->assertStringContainsString("\$foo = 1;\n", $writer->getBuffer());
    }

    public function testWriteLineWithoutArgEmitsBlankLine(): void
    {
        $writer = new PhpCodeWriter();
        $writer->writeLine('a');
        $writer->writeLine();
        $writer->writeLine('b');

        // Blank line is `\n` with no indent, regardless of current indent
        // level.
        $this->assertSame("a\n\nb\n", $writer->getBuffer());
    }

    public function testWriteAppendsWithoutTrailingNewline(): void
    {
        $writer = new PhpCodeWriter();
        $writer->write('return ');
        $writer->write('null;');
        $writer->writeLine();

        $this->assertSame("return null;\n", $writer->getBuffer());
    }

    public function testWriteOnlyIndentsAtLineStart(): void
    {
        $writer = new PhpCodeWriter();
        $writer->pushIndent();
        $writer->write('return ');
        $writer->write('null;');
        $writer->writeLine();

        $this->assertSame("    return null;\n", $writer->getBuffer());
    }

    public function testOpenAndCloseBlockManageIndentation(): void
    {
        $writer = new PhpCodeWriter();
        $writer->openBlock('class Foo');
        $writer->writeLine('public int $x;');
        $writer->closeBlock();

        $this->assertSame(
            "class Foo\n    public int \$x;\n}\n",
            $writer->getBuffer()
        );
    }

    public function testCloseBlockAcceptsCustomClosingLine(): void
    {
        $writer = new PhpCodeWriter();
        $writer->openBlock('$arr = [');
        $writer->writeLine("'a',");
        $writer->closeBlock('];');

        $this->assertSame(
            "\$arr = [\n    'a',\n];\n",
            $writer->getBuffer()
        );
    }

    public function testPushAndPopIndentAdjustLevelWithoutEmittingLines(): void
    {
        $writer = new PhpCodeWriter();
        $writer->writeLine('a');
        $writer->pushIndent();
        $writer->writeLine('b');
        $writer->pushIndent();
        $writer->writeLine('c');
        $writer->popIndent();
        $writer->writeLine('d');
        $writer->popIndent();
        $writer->writeLine('e');

        $this->assertSame(
            "a\n    b\n        c\n    d\ne\n",
            $writer->getBuffer()
        );
    }

    public function testAddImportEmitsUseStatementsAlphabetically(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        // Deliberately added out of order - output must be ksort'd.
        $writer->addImport('Smithy\\Http\\Client\\HttpClientInterface');
        $writer->addImport('Aws\\Core\\Credentials\\AwsCredentials');

        $output = $writer->toFileContent();

        $this->assertStringContainsString(
            "use Aws\\Core\\Credentials\\AwsCredentials;\n"
                . "use Smithy\\Http\\Client\\HttpClientInterface;\n",
            $output
        );
    }

    public function testAddImportDedupesSameFqcn(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Foo\\Bar');
        $writer->addImport('Aws\\Foo\\Bar');
        $writer->addImport('Aws\\Foo\\Bar');

        // Only one `use Aws\Foo\Bar.` line, not three.
        $output = $writer->toFileContent();
        $this->assertSame(1, substr_count($output, 'use Aws\\Foo\\Bar;'));
    }

    public function testAddImportSkipsSameNamespaceImports(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        // Same namespace as the writer - no `use` should be emitted for this.
        $writer->addImport('Aws\\Example\\SiblingClass');
        $writer->addImport('\\Aws\\Example\\RootedSiblingClass');
        $writer->addImport('Aws\\Other\\ExternalClass');

        $output = $writer->toFileContent();

        $this->assertStringNotContainsString('SiblingClass', $output);
        $this->assertStringNotContainsString('RootedSiblingClass', $output);
        $this->assertStringContainsString(
            'use Aws\\Other\\ExternalClass;',
            $output
        );
    }

    public function testAddImportAllowsAliasedSameNamespaceImports(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Example\\Schema', 'ModeledSchema');

        $this->assertStringContainsString(
            'use Aws\\Example\\Schema as ModeledSchema;',
            $writer->toFileContent()
        );
    }

    public function testAddImportStripsLeadingBackslash(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('\\Aws\\Other\\Foo');

        $this->assertStringContainsString(
            'use Aws\\Other\\Foo;',
            $writer->toFileContent()
        );
        $this->assertStringNotContainsString(
            'use \\Aws',
            $writer->toFileContent()
        );
    }

    public function testAddImportEmitsAliasWhenAliasDiffersFromShortName(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Other\\LongClassName', 'ShortName');

        $this->assertStringContainsString(
            'use Aws\\Other\\LongClassName as ShortName;',
            $writer->toFileContent()
        );
    }

    public function testAddImportSkipsAliasWhenAliasEqualsShortName(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Other\\Foo', 'Foo');

        $output = $writer->toFileContent();

        $this->assertStringContainsString("use Aws\\Other\\Foo;", $output);
        $this->assertStringNotContainsString(' as Foo', $output);
    }

    public function testAddImportReferenceAliasesOccupiedShortNames(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Modeled\\ClientConfig');

        $reference = $writer->addImportReference(
            'Aws\\Runtime\\ClientConfig',
            'RuntimeClientConfig'
        );

        $this->assertSame('RuntimeClientConfig', $reference);
        $this->assertStringContainsString(
            'use Aws\\Runtime\\ClientConfig as RuntimeClientConfig;',
            $writer->toFileContent()
        );
    }

    public function testAddImportReferenceAliasesSameNamespaceNames(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Example\\ClientConfig');

        $reference = $writer->addImportReference(
            'Aws\\Runtime\\ClientConfig',
            'RuntimeClientConfig'
        );

        $this->assertSame('RuntimeClientConfig', $reference);
        $this->assertStringContainsString(
            'use Aws\\Runtime\\ClientConfig as RuntimeClientConfig;',
            $writer->toFileContent()
        );
        $this->assertStringNotContainsString(
            'use Aws\\Example\\ClientConfig;',
            $writer->toFileContent()
        );
    }

    public function testAddImportReferenceReusesSameNamespaceClass(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Example\\ClientConfig');

        $reference = $writer->addImportReference(
            'Aws\\Example\\ClientConfig',
            'ConfiguredClientConfig'
        );

        $this->assertSame('ClientConfig', $reference);
        $this->assertStringNotContainsString(
            'use Aws\\Example\\ClientConfig;',
            $writer->toFileContent()
        );
    }

    public function testSameNamespaceImportReferenceReservesItsShortName(): void
    {
        $writer = new PhpCodeWriter('Vendor');

        $configReference = $writer->addImportReference(
            'Vendor\\PluginCollection',
            'ConfiguredClientConfig'
        );
        $pluginReference = $writer->addImportReference(
            'Smithy\\Client\\Plugin\\PluginCollection',
            'RuntimePluginCollection'
        );

        $this->assertSame('PluginCollection', $configReference);
        $this->assertSame('RuntimePluginCollection', $pluginReference);
        $this->assertStringContainsString(
            'use Smithy\\Client\\Plugin\\PluginCollection as '
                . 'RuntimePluginCollection;',
            $writer->toFileContent()
        );
    }

    public function testAddImportReferenceHonorsReservedNames(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');

        $reference = $writer->addImportReference(
            'Aws\\Runtime\\ExampleClient',
            'RuntimeExampleClient',
            ['ExampleClient']
        );

        $this->assertSame('RuntimeExampleClient', $reference);
        $this->assertStringContainsString(
            'use Aws\\Runtime\\ExampleClient as RuntimeExampleClient;',
            $writer->toFileContent()
        );
    }

    public function testWriteDocBlockWrapsBoundaryWithSlashStarSlashLines(): void
    {
        $writer = new PhpCodeWriter();
        $writer->writeDocBlock(['first line', 'second line']);

        $this->assertSame(
            "/**\n * first line\n * second line\n */\n",
            $writer->getBuffer()
        );
    }

    public function testWriteDocBlockEscapesClosingDelimiter(): void
    {
        $writer = new PhpCodeWriter();
        $writer->writeDocBlock(['Text containing */ remains documentation.']);

        $this->assertSame(
            "/**\n * Text containing * / remains documentation.\n */\n",
            $writer->getBuffer()
        );
    }

    public function testExportLiteralPreservesNestedValues(): void
    {
        $this->assertSame(
            "['text' => 'customer\\'s value', 'paths' => ['C:\\\\tmp']]",
            PhpCodeWriter::exportLiteral([
                'text' => "customer's value",
                'paths' => ['C:\\tmp']
            ])
        );
    }

    public function testWriteDocBlockSoftWrapsLongLinesAtWhitespaceBoundary(): void
    {
        $writer = new PhpCodeWriter();
        // 130-char single line - exceeds the 117-char budget and must
        // break on a whitespace boundary within the first 118 chars.
        $long = str_repeat('word ', 26); // "word " x 26 = 130 chars
        $writer->writeDocBlock([$long]);

        $buffer = $writer->getBuffer();
        // Content lines must each fit inside the 117-char budget after the ` *
        // ` prefix.
        foreach (preg_split('/\n/', trim($buffer)) as $line) {
            $prefixLen = str_starts_with($line, ' * ') ? 3 : 0;
            $this->assertLessThanOrEqual(
                PhpCodeWriter::DOC_WRAP_WIDTH + $prefixLen,
                strlen($line),
                "Docblock line exceeds budget: {$line}"
            );
        }
    }

    #[DataProvider('shortLinePassThroughVariants')]
    public function testWrapDocLinePassesShortLinesThrough(string $input): void
    {
        $this->assertSame([$input], PhpCodeWriter::wrapDocLine($input));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function shortLinePassThroughVariants(): array
    {
        return [
            'empty' => [''],
            'single short word' => ['hello'],
            'well under budget' => [
                'This line is well under the docblock width budget.'
            ],
            'exactly at boundary' => [
                str_repeat('x', PhpCodeWriter::DOC_WRAP_WIDTH)
            ]
        ];
    }

    public function testWrapDocLineBreaksOnLastWhitespaceWithinBudget(): void
    {
        // Build a line that requires exactly one break.
        // First 60 chars, then a space, then 60 more - total 121 chars, one
        // whitespace to break on.
        $left = str_repeat('a', 60);
        $right = str_repeat('b', 60);
        $input = $left . ' ' . $right;

        $result = PhpCodeWriter::wrapDocLine($input);

        // Break should land at the space, yielding two lines each within
        // budget.
        $this->assertSame([$left, $right], $result);
    }

    public function testWrapDocLineFallsBackToHardBreakWhenNoWhitespace(): void
    {
        // No whitespace in the whole line - the wrapper must still make
        // progress
        // rather than infinite-loop. A single mid-word break at $width is fine.
        $input = str_repeat('x', 130);
        $result = PhpCodeWriter::wrapDocLine($input, 100);

        // First segment hits the hard boundary, rest fits below.
        $this->assertCount(2, $result);
        $this->assertSame(100, strlen($result[0]));
        $this->assertSame(30, strlen($result[1]));
    }

    public function testResetClearsBufferImportsAndIndentation(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Other\\Foo');
        $writer->pushIndent();
        $writer->writeLine('$x = 1;');

        $writer->reset();

        // Namespace is not reset (it's the constructor arg), but
        // body/imports/indent are.
        $output = $writer->toFileContent();
        $this->assertStringNotContainsString('use Aws\\Other\\Foo', $output);
        $this->assertStringNotContainsString('$x = 1;', $output);
        // Indent-back-to-zero: a fresh writeLine emits with no leading
        // whitespace.
        $writer->writeLine('$y = 2;');
        $this->assertStringContainsString("\$y = 2;\n", $writer->getBuffer());
        $this->assertStringNotContainsString("    \$y", $writer->getBuffer());
    }

    public function testToFileContentComposesHeaderNamespaceImportsAndBody(): void
    {
        $writer = new PhpCodeWriter('Aws\\Example');
        $writer->addImport('Aws\\Other\\Foo');
        $writer->openBlock('final class Example');
        $writer->writeLine('public int $count = 0;');
        $writer->closeBlock();

        $expected = "<?php\n\n"
            . "declare(strict_types=1);\n\n"
            . "namespace Aws\\Example;\n\n"
            . "use Aws\\Other\\Foo;\n\n"
            . "final class Example\n"
            . "    public int \$count = 0;\n"
            . "}\n";

        $this->assertSame($expected, $writer->toFileContent());
    }

    public function testFluentApiReturnsSelf(): void
    {
        // Every mutator returns `$this` - verify chaining works and preserves
        // state.
        $writer = new PhpCodeWriter();
        $result = $writer
            ->writeLine('a')
            ->pushIndent()
            ->write('b')
            ->writeLine()
            ->popIndent()
            ->openBlock('c')
            ->closeBlock();

        $this->assertSame($writer, $result);
    }
}
