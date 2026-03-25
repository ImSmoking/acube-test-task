<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Manager\FileManager;
use App\Repository\FileConversionJobRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class ConversionProcessorService
{
    public function __construct(
        private FileConversionJobRepository $fileConversionJobRepository,
        private FileConversionService $fileConversionService,
        private FileManager $fileManager,
    )
    {
    }

    /**
     * @param string $fileJobConversionId
     * @return void
     * @throws \Throwable
     */
    public function processConversion(string $fileJobConversionId): void
    {
        try {
            /** @var FileConversionJob $fileJobConversion */
            $fileJobConversion = $this->fileConversionJobRepository->find($fileJobConversionId);
            $this->markFileConversionAsStarted($fileJobConversion);

            $convertedFilepath = $this->fileConversionService->convertFromFileConversionJob($fileJobConversion);

            $this->markFileConversionAsCompleted($fileJobConversion, $convertedFilepath);
        } catch (\Throwable $throwable) {
            $this->markFileConversionAsFailed($fileJobConversion, $throwable);

            throw $throwable;
        }
    }

    /**
     * @param FileConversionJob $fileJobConversion
     * @return void
     */
    private function markFileConversionAsStarted(FileConversionJob $fileJobConversion): void
    {
        $fileJobConversion
            ->setStatus(FileConversionJobStatusEnum::PROCESSING)
            ->incrementRetryCount()
            ->setStartedAt(new \DateTimeImmutable());

        $this->fileConversionJobRepository->save($fileJobConversion);
    }

    /**
     * @param FileConversionJob $fileJobConversion
     * @param string $convertedFilepath
     * @return void
     */
    private function markFileConversionAsCompleted(FileConversionJob $fileJobConversion, string $convertedFilepath): void
    {

        $uploadedFile = new UploadedFile($convertedFilepath, basename($convertedFilepath));
        $convertedFile = $this->fileManager->createFromUploadedFile($uploadedFile);
        rename($convertedFilepath, $convertedFile->getPath() . $convertedFile->getStoredFilename());


        $fileJobConversion
            ->setStatus(FileConversionJobStatusEnum::COMPLETED)
            ->setOutputFile($convertedFile)
            ->setCompletedAt(new \DateTimeImmutable());

        $this->fileConversionJobRepository->save($fileJobConversion);
    }

    /**
     * @param FileConversionJob $fileJobConversion
     * @param \Throwable $throwable
     * @return void
     */
    private function markFileConversionAsFailed(FileConversionJob $fileJobConversion, \Throwable $throwable): void
    {
        $fileJobConversion
            ->setStatus(FileConversionJobStatusEnum::FAILED)
            ->setErrorMessage($throwable->getMessage());

        $this->fileConversionJobRepository->save($fileJobConversion);
    }
}
