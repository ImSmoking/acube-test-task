<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;

readonly class FileConversionService
{
    public function __construct(
        private string $filesUploadFolder
    )
    {
    }

    /**
     * @param FileConversionJob $fileConversionJob
     * @return string
     */
    public function convertFromFileConversionJob(FileConversionJob $fileConversionJob): string
    {
        sleep(180);
        $outputPath = $this->buildOutputPath($fileConversionJob);

        $this->ensureDirectoryExists(dirname($outputPath));

        $converted = match ($fileConversionJob->getTargetFormat()) {
            FileConversionJobTargetFormatEnum::JSON => $this->convertToJson($fileConversionJob),
            FileConversionJobTargetFormatEnum::XML => $this->convertToXml($fileConversionJob),
        };

        file_put_contents($outputPath, $converted);

        return $outputPath;
    }

    /**
     * @param FileConversionJob $fileConversionJob
     * @return string
     */
    private function buildOutputPath(FileConversionJob $fileConversionJob): string
    {
        $sourceFile = $fileConversionJob->getSourceFile();
        $filename = pathinfo($sourceFile->getOriginalFilename(), PATHINFO_FILENAME);
        $extension = $fileConversionJob->getTargetFormat()->value;

        return sprintf(
            '%s/converted/%s.%s',
            $this->filesUploadFolder,
            $filename,
            $extension
        );
    }

    /**
     * @param string $directory
     * @return void
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true); // true = recursive, creates nested dirs
        }
    }

    /**
     * @param FileConversionJob $fileConversionJob
     * @return string
     */
    private function convertToJson(FileConversionJob $fileConversionJob): string
    {
        return json_encode([
            ['id' => 1, 'name' => 'Luke Skywalker', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Darth Vader', 'email' => 'dart.vader@empire.com']
        ], JSON_PRETTY_PRINT);
    }

    /**
     * @param FileConversionJob $fileConversionJob
     * @return string
     */
    private function convertToXml(FileConversionJob $fileConversionJob): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <rows>
            <row>
                <id>1</id>
                <name>Luke Skywalker</name>
                <email>luke.skywalker@rebelion.com</email>
            </row>
            <row>
                <id>2</id>
                <name>Darth Vader</name>
                <email>dart.vader@empire.com</email>
            </row>
        </rows>
        XML;
    }
}
