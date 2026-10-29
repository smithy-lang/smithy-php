<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Fixtures;

use Smithy\Codegen\CodegenContext;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\Generator\GeneratedFile;
use Smithy\Codegen\Integration\AbstractCodegenIntegration;
use Smithy\Codegen\Model\SmithyModel;

final class CliCodegenIntegration extends AbstractCodegenIntegration
{
    private string $fileName = 'CliIntegration.php';
    private ?string $outputDir = null;

    public function configure(array $settings): void
    {
        $fileName = $settings['fileName'] ?? $this->fileName;
        if (!\is_string($fileName) || $fileName === '') {
            throw new \InvalidArgumentException(
                'fileName must be a non-empty string'
            );
        }

        $this->fileName = $fileName;

        $outputDir = $settings['outputDir'] ?? null;
        if ($outputDir !== null
            && (!\is_string($outputDir) || $outputDir === '')
        ) {
            throw new \InvalidArgumentException(
                'outputDir must be a non-empty string'
            );
        }

        $this->outputDir = $outputDir;
    }

    public function customizeSettings(
        CodegenSettings $settings,
        SmithyModel $model
    ): CodegenSettings {
        return $this->outputDir === null
            ? $settings
            : $settings->copyWith(['outputDir' => $this->outputDir]);
    }

    public function generateAdditionalFiles(CodegenContext $context): iterable
    {
        yield new GeneratedFile(
            $context->settings->outputDir
                . DIRECTORY_SEPARATOR
                . $this->fileName,
            "<?php\n"
        );
    }
}
