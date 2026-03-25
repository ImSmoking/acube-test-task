<?php

declare(strict_types=1);


namespace App\Manager;

use App\Entity\File;
use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use App\Repository\FileConversionJobRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

readonly class FileConversionJobManager
{
    public function __construct(
        private FileConversionJobRepository $fileConversionJobRepository,
    )
    {
    }

    /**
     * @param File $file
     * @param array $targetFormats
     * @return void
     */
    public function createForMultipleTargetFormats(File $file, array $targetFormats): void
    {
        $lastTargetFormat = end($targetFormats);
        $persist = false;
        foreach ($targetFormats as $targetFormat) {
            $conversionJob = new FileConversionJob()
                ->setSourceFile($file)
                ->setTargetFormat(FileConversionJobTargetFormatEnum::from($targetFormat));

            if ($lastTargetFormat === $targetFormat) {
                $persist = true;
            }
            $file->addConversionJob($conversionJob);
            $this->fileConversionJobRepository->save($conversionJob, $persist);
        }
    }

    /**
     * @param FileConversionJob $fileConversionJob
     * @return BinaryFileResponse
     */
    public function buildDownloadResponse(FileConversionJob $fileConversionJob): BinaryFileResponse
    {
        $outputFile = $fileConversionJob->getOutputFile();
        $sourceFile = $fileConversionJob->getSourceFile();

        $response = new BinaryFileResponse($outputFile->getPath() . $outputFile->getStoredFilename());

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf(
                '%s.%s',
                pathinfo($sourceFile->getOriginalFilename(), PATHINFO_FILENAME),
                $fileConversionJob->getTargetFormat()->value
            )
        );

        $response->headers->set('Content-Type', $outputFile->getMimeType());

        return $response;
    }
}
