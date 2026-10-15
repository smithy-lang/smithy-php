<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodeGenerator;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\GeneratedFile;
use Smithy\Codegen\Model\SmithyModel;

final class CodeGeneratorTest extends TestCase
{
    private SmithyModel $model;
    private CodegenSettings $settings;

    protected function setUp(): void
    {
        $this->model = SmithyModel::fromFile(
            __DIR__ . '/../fixtures/person-model.json'
        );
        $this->settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: sys_get_temp_dir() . '/smithy-php-test-' . uniqid(),
            organization: 'flat'
        );
    }

    public function testGeneratesStructureShapes(): void
    {
        $generator = new CodeGenerator($this->settings, $this->model);
        $files = $generator->generate();

        // Should have generated files for modeled structures.
        // Plus enums: Status, Priority
        // Plus unions: Payload
        // Minus: Event (mixin), HumanList (list), OrderId (string), prelude
        // shapes
        $fileNames = array_map(fn($f) => basename($f->path), $files);

        $this->assertContains('Human.php', $fileNames);
        $this->assertContains('Parents.php', $fileNames);
        $this->assertContains('Address.php', $fileNames);
        $this->assertContains('QueryEvent.php', $fileNames);
        $this->assertContains('NewOrderEvent.php', $fileNames);
        $this->assertContains('ReturnEvent.php', $fileNames);
        $this->assertContains('Status.php', $fileNames);
        $this->assertContains('Priority.php', $fileNames);
        $this->assertContains('Payload.php', $fileNames);

        // Should NOT generate mixin shapes
        $this->assertNotContains('Event.php', $fileNames);
    }

    public function testGeneratesValidPhpForReservedShapeNames(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Class' => ['type' => 'structure'],
                'smithy.example#Function' => ['type' => 'structure'],
                'smithy.example#Array' => ['type' => 'structure'],
                'smithy.example#Default' => ['type' => 'structure'],
                'smithy.example#Namespace' => ['type' => 'structure']
            ]
        ], JSON_THROW_ON_ERROR));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );

        $files = (new CodeGenerator($settings, $model))->generate();
        $fileNames = \array_map(
            static fn(GeneratedFile $file): string => \basename($file->path),
            $files
        );

        foreach ([
            'ExampleClass.php',
            'ExampleFunction.php',
            'ExampleArray.php',
            'ExampleDefault.php',
            'ExampleNamespace.php'
        ] as $fileName) {
            $this->assertContains($fileName, $fileNames);
            $file = $this->findFile($files, $fileName);
            $this->assertNotNull($file);
            $this->assertValidPhp($file->content);
        }
    }

    public function testStructureHasCorrectContent(): void
    {
        $generator = new CodeGenerator($this->settings, $this->model);
        $files = $generator->generate();

        $humanFile = $this->findFile($files, 'Human.php');
        $this->assertNotNull($humanFile);

        $content = $humanFile->content;

        // Check PHP file structure
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString(
            'namespace Smithy\\Example;',
            $content
        );

        // Check class declaration - current codegen implements
        // SerializableStructInterface (universal struct contract - the
        // legacy `HasSchemaInterface` was split into these two roles).
        $this->assertStringContainsString(
            'final class Human implements SerializableStructInterface',
            $content
        );

        // Every member is nullable with a `= null` default. Service-required
        // members keep their position at the front of the constructor. their
        // type-system distinction (non-nullable, no default) is gone.
        // Validation
        // lives on the service per the spec.
        $this->assertStringContainsString(
            'public readonly ?string $name = null,',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?int $age = null,',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?Address $address = null,',
            $content
        );
        $this->assertStringContainsString('?array $children = null,', $content);
        $this->assertStringContainsString('?Parents $parents = null', $content);

        // Check schema() method - current codegen pulls the Schema instance
        // from the centralized {@see Schemas} class with lazy caching.
        $this->assertStringContainsString('public static function schema(): Schema', $content);
        $this->assertStringContainsString('Schemas::human()', $content);

        // Codegen imports `FromMembersTrait` and emits a `use` line rather
        // than a per-shape `fromMembers()` body. The trait's implementation
        // is `return new static(...$members).` - verified by the trait's
        // own unit coverage, not by re-asserting on every generated shape.
        $this->assertStringContainsString('use FromMembersTrait;', $content);
        $this->assertStringContainsString(
            'use Smithy\\Core\\Shape\\FromMembersTrait;',
            $content
        );


    }

    public function testStructureWithDocumentation(): void
    {
        $generator = new CodeGenerator($this->settings, $this->model);
        $files = $generator->generate();

        $queryEventFile = $this->findFile($files, 'QueryEvent.php');
        $this->assertNotNull($queryEventFile);

        $content = $queryEventFile->content;

        // Should have documentation
        $this->assertStringContainsString(
            'Request for item availability',
            $content
        );

        // Timestamp members are emitted as `float|null` (epoch seconds,
        // nullable per the every-member-optional rule) - codegen no longer
        // emits `\DateTimeImmutable`, mirroring the runtime's
        // {@see TimestampFormat} contract.
        $this->assertStringContainsString(
            'public readonly ?float $timestamp',
            $content
        );
    }

    public function testEnumGeneration(): void
    {
        $generator = new CodeGenerator($this->settings, $this->model);
        $files = $generator->generate();

        // Codegen emits enums as final classes (not native PHP `enum: string`)
        // so unknown wire values can be represented as fresh non-singleton
        // instances at runtime - important for forward-compat with newer
        // service versions that add cases this client doesn't yet know about.

        // String enum
        $statusFile = $this->findFile($files, 'Status.php');
        $this->assertNotNull($statusFile);

        $content = $statusFile->content;
        $this->assertStringContainsString(
            'final class Status implements EnumInterface',
            $content
        );
        $this->assertStringContainsString("public static function active(): self", $content);
        $this->assertStringContainsString("new self('ACTIVE')", $content);
        $this->assertStringContainsString("new self('INACTIVE')", $content);
        $this->assertStringContainsString("new self('PENDING')", $content);
        $this->assertStringContainsString(
            'The item is pending review',
            $content
        );

        // Int enum
        $priorityFile = $this->findFile($files, 'Priority.php');
        $this->assertNotNull($priorityFile);

        $content = $priorityFile->content;
        $this->assertStringContainsString(
            'final class Priority implements EnumInterface',
            $content
        );
        $this->assertStringContainsString(
            'public readonly int $value;',
            $content
        );
        $this->assertStringContainsString('public static function low(): self', $content);
        $this->assertStringContainsString('public static function medium(): self', $content);
        $this->assertStringContainsString('public static function high(): self', $content);
        $this->assertStringContainsString('new self(1)', $content);
        $this->assertStringContainsString('new self(2)', $content);
        $this->assertStringContainsString('new self(3)', $content);
    }

    public function testUnionGeneration(): void
    {
        $generator = new CodeGenerator($this->settings, $this->model);
        $files = $generator->generate();

        // Base class
        $payloadFile = $this->findFile($files, 'Payload.php');
        $this->assertNotNull($payloadFile);

        $content = $payloadFile->content;

        // Check abstract base class implements UnionInterface
        $this->assertStringContainsString(
            'abstract readonly class Payload implements UnionInterface',
            $content
        );

        // Check factory methods
        $this->assertStringContainsString(
            'public static function stringValue(string $value): self',
            $content
        );
        $this->assertStringContainsString(
            'public static function intValue(int $value): self',
            $content
        );
        $this->assertStringContainsString(
            'public static function event(NewOrderEvent $value): self',
            $content
        );

        // Check unknown variant factory
        $this->assertStringContainsString(
            'public static function unknown(string $tag, mixed $value): self',
            $content
        );

        // Check documentation
        $this->assertStringContainsStringIgnoringCase(
            'tagged union',
            $content
        );

        // Check that variant classes are in separate files
        $stringValueFile = $this->findFile($files, 'PayloadStringValue.php');
        $this->assertNotNull($stringValueFile);
        $this->assertStringContainsString(
            'final readonly class PayloadStringValue extends Payload',
            $stringValueFile->content
        );

        $intValueFile = $this->findFile($files, 'PayloadIntValue.php');
        $this->assertNotNull($intValueFile);
        $this->assertStringContainsString(
            'final readonly class PayloadIntValue extends Payload',
            $intValueFile->content
        );

        $unknownFile = $this->findFile($files, 'PayloadUnknown.php');
        $this->assertNotNull($unknownFile);
        $this->assertStringContainsString(
            'final readonly class PayloadUnknown extends Payload',
            $unknownFile->content
        );
    }

    public function testCategorizedOrganization(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\MyService',
            outputDir: sys_get_temp_dir() . '/smithy-php-test-' . uniqid(),
            organization: 'categorized'
        );

        $generator = new CodeGenerator($settings, $this->model);
        $files = $generator->generate();

        // All shapes in the test model are "Shape" category (no operations
        // defined)
        $humanFile = $this->findFile($files, 'Human.php');
        $this->assertNotNull($humanFile);

        $content = $humanFile->content;
        $this->assertStringContainsString(
            'namespace Aws\\MyService\\Shape;',
            $content
        );
    }

    public function testArrayAccessOnOutputShapes(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Sts',
            outputDir: sys_get_temp_dir() . '/smithy-php-test-' . uniqid(),
            organization: 'flat',
            arrayAccess: [
                'enabled' => true,
                'categories' => ['shape']
            ]
        );

        $generator = new CodeGenerator($settings, $this->model);
        $files = $generator->generate();

        // All shapes in this model are "Shape" category (no operations)
        $humanFile = $this->findFile($files, 'Human.php');
        $this->assertNotNull($humanFile);

        $content = $humanFile->content;

        // Should implement ArrayAccess on top of the per-direction interfaces
        // (codegen splits the old HasSchemaInterface into Serializable +
        // Deserializable. ArrayAccess is appended after both).
        $this->assertStringContainsString(
            'implements SerializableStructInterface, \\ArrayAccess',
            $content
        );
        $this->assertStringContainsString(
            'use ReadonlyArrayAccessTrait;',
            $content
        );
    }

    public function testArrayAccessDisabledByDefault(): void
    {
        $generator = new CodeGenerator($this->settings, $this->model);
        $files = $generator->generate();

        $humanFile = $this->findFile($files, 'Human.php');
        $this->assertNotNull($humanFile);

        $content = $humanFile->content;

        // Should NOT have ArrayAccess when disabled
        $this->assertStringNotContainsString('\\ArrayAccess', $content);
        $this->assertStringNotContainsString(
            'ReadonlyArrayAccessTrait',
            $content
        );
    }

    public function testWritesToDisk(): void
    {
        $outputDir = sys_get_temp_dir() . '/smithy-php-test-' . uniqid();
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: $outputDir,
            organization: 'flat'
        );

        $generator = new CodeGenerator($settings, $this->model);
        $count = $generator->generateAndWrite();

        $this->assertGreaterThan(0, $count);
        $this->assertFileExists(
            $outputDir . DIRECTORY_SEPARATOR . 'Human.php'
        );
        $this->assertFileExists(
            $outputDir . DIRECTORY_SEPARATOR . 'Address.php'
        );
        $this->assertFileExists(
            $outputDir . DIRECTORY_SEPARATOR . 'Status.php'
        );
        $this->assertFileExists(
            $outputDir . DIRECTORY_SEPARATOR . 'Priority.php'
        );
        $this->assertFileExists(
            $outputDir . DIRECTORY_SEPARATOR . 'Payload.php'
        );

        // Cleanup
        $this->removeDir($outputDir);
    }

    public function testDefaultTraitMembers(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Defaults',
            outputDir: sys_get_temp_dir() . '/smithy-php-test-' . uniqid(),
            organization: 'flat'
        );

        $generator = new CodeGenerator($settings, $model);
        $files = $generator->generate();

        $settingsFile = $this->findFile($files, 'Settings.php');
        $this->assertNotNull($settingsFile);
        $content = $settingsFile->content;

        // Service-required member: nullable with `= null`, position first.
        // Required-ness is no longer enforced through PHP's type system.
        $this->assertStringContainsString(
            'public readonly ?string $name = null,',
            $content
        );

        // Top-level input defaults are ignored in client mode.
        $this->assertStringContainsString(
            'public readonly ?int $count = null,',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?string $label = null,',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?bool $enabled = null,',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?float $ratio = null,',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?array $tags = null,',
            $content
        );

        // Pure-optional member: nullable with `= null`.
        $this->assertStringContainsString(
            'public readonly ?string $description = null,',
            $content
        );

        // No per-shape array conversion helpers are emitted.
        $this->assertStringNotContainsString("name: \$data['name'],", $content);
        $this->assertStringNotContainsString('public function toArray()', $content);
        $this->assertStringNotContainsString(
            'public static function fromArray(array $data)',
            $content
        );

        // Input enum defaults are also ignored until explicitly provided.
        $this->assertStringContainsString(
            'public readonly ?Status $status;',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?Priority $priority;',
            $content
        );
        $this->assertStringContainsString(
            'public readonly ?Status $marker;',
            $content
        );

        $this->assertStringContainsString(
            'Status|string|null $status = null,',
            $content
        );
        $this->assertStringContainsString(
            'Priority|int|null $priority = null,',
            $content
        );
        $this->assertStringContainsString(
            'Status|string|null $marker = null',
            $content
        );

        $this->assertStringContainsString('if ($status !== null) {', $content);
        $this->assertStringContainsString('if ($priority !== null) {', $content);
        $this->assertStringContainsString('if ($marker !== null) {', $content);
        $this->assertStringContainsString(
            '$this->marker = $marker instanceof Status '
                . '? $marker : Status::fromValue($marker);',
            $content
        );



        // The runtime trait's fromArray() passes the wire scalar straight
        // through to the constructor, which normalizes via fromValue(). No
        // per-shape `$data[...]` lines are emitted any more.
        $this->assertStringNotContainsString(
            "status: \$data['status']",
            $content
        );
        $this->assertStringNotContainsString(
            "priority: \$data['priority']",
            $content
        );
        $this->assertStringNotContainsString(
            "marker: \$data['marker']",
            $content
        );

        // All input members are optional and retain model order.
        $namePos = strpos($content, 'public readonly ?string $name');
        $countPos = strpos($content, 'public readonly ?int $count');
        $descPos = strpos($content, '$description = null');
        $this->assertNotFalse($namePos);
        $this->assertNotFalse($countPos);
        $this->assertNotFalse($descPos);
        $this->assertLessThan($countPos, $namePos);
        $this->assertLessThan($descPos, $countPos);
    }

    public function testGenerateAndWriteFailsBeforeWritingAnyFiles(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#BrokenService' => [
                    'type' => 'service',
                    'version' => '2026-08-25',
                    'operations' => [],
                    'traits' => [
                        'smithy.rules#endpointBdd' => [
                            'parameters' => [],
                            'conditions' => [],
                            'results' => [],
                            'nodes' => '%',
                            'nodeCount' => 1,
                            'root' => 0
                        ]
                    ]
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $outputDirectory = \sys_get_temp_dir()
            . '/smithy-php-test-'
            . \uniqid();
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Broken',
            outputDir: $outputDirectory,
            organization: 'flat'
        );
        $generator = new CodeGenerator($settings, $model);

        try {
            $generator->generateAndWrite();
            $this->fail('Expected code generation to fail');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(
                'Invalid base64 BDD node blob',
                $exception->getMessage()
            );
            $this->assertDirectoryDoesNotExist($outputDirectory);
        }
    }

    public function testAtomicRegenerationPreservesMemberOrder(): void
    {
        $outputDirectory = \sys_get_temp_dir()
            . '/smithy-php-test-'
            . \bin2hex(\random_bytes(8));
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: $outputDirectory,
            organization: 'flat'
        );
        $modelPath = __DIR__ . '/../fixtures/defaults-model.json';

        try {
            (new CodeGenerator(
                $settings,
                SmithyModel::fromFile($modelPath)
            ))->generateAndWrite();
            $settingsPath = $outputDirectory
                . DIRECTORY_SEPARATOR
                . 'Settings.php';
            $originalSettings = \file_get_contents($settingsPath);
            $this->assertIsString($originalSettings);
            \file_put_contents(
                $outputDirectory . DIRECTORY_SEPARATOR . 'Stale.php',
                'stale'
            );

            $model = \json_decode(
                (string) \file_get_contents($modelPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            $members = $model['shapes']['smithy.example#Settings']['members'];
            $model['shapes']['smithy.example#Settings']['members'] =
                \array_reverse($members, true);

            (new CodeGenerator(
                $settings,
                SmithyModel::fromJson(
                    \json_encode($model, JSON_THROW_ON_ERROR)
                )
            ))->generateAndWrite();

            $this->assertSame(
                $originalSettings,
                \file_get_contents($settingsPath)
            );
            $this->assertFileDoesNotExist(
                $outputDirectory
                    . DIRECTORY_SEPARATOR
                    . 'Stale.php'
            );
        } finally {
            $this->removeDir($outputDirectory);
        }
    }

    public function testExternalDriversCanOwnClientArtifactGeneration(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat',
            generateClient: false,
            generateClientConfig: false
        );

        $files = (new CodeGenerator($settings, $model))->generate();
        $fileNames = \array_map(
            static fn(GeneratedFile $file): string => \basename($file->path),
            $files
        );

        $this->assertNotContains('ExampleClient.php', $fileNames);
        $this->assertNotContains('ExampleClientConfig.php', $fileNames);
        $this->assertContains('ExampleService.php', $fileNames);
    }

    public function testOmittedOperationOutputGeneratesUnit(): void
    {
        $model = SmithyModel::fromFile(
            __DIR__ . '/../fixtures/defaults-model.json'
        );
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/out',
            organization: 'flat'
        );

        $files = (new CodeGenerator($settings, $model))->generate();
        $fileNames = \array_map(
            static fn(GeneratedFile $file): string => \basename($file->path),
            $files
        );

        $this->assertContains('Unit.php', $fileNames);
    }

    public function testRejectsModelsWithMultipleServices(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#FirstService' => [
                    'type' => 'service',
                    'version' => '2026-08-25',
                    'operations' => []
                ],
                'smithy.example#SecondService' => [
                    'type' => 'service',
                    'version' => '2026-08-25',
                    'operations' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));
        $generator = new CodeGenerator($this->settings, $model);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Code generation requires at most one service shape'
        );

        $generator->generate();
    }

    /**
     * @param \Smithy\Codegen\Generator\GeneratedFile[] $files
     */
    private function findFile(array $files, string $name): ?GeneratedFile
    {

        foreach ($files as $file) {
            if (basename($file->path) === $name) {
                return $file;
            }
        }
        return null;
    }

    private function assertValidPhp(string $source): void
    {
        $process = \proc_open(
            [PHP_BINARY, '-l'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );
        $this->assertIsResource($process);

        \fwrite($pipes[0], $source);
        \fclose($pipes[0]);
        $stdout = \stream_get_contents($pipes[1]);
        $stderr = \stream_get_contents($pipes[2]);
        \fclose($pipes[1]);
        \fclose($pipes[2]);

        $this->assertSame(
            0,
            \proc_close($process),
            $stdout . $stderr
        );
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
