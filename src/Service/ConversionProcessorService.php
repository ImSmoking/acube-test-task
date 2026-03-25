<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Repository\FileConversionJobRepository;

readonly class ConversionProcessorService
{
    public function __construct(
        private FileConversionJobRepository $fileConversionJobRepository,
    )
    {
    }

    /**
     * @param string $fileJobConversionId
     * @return void
     */
    public function processConversion(string $fileJobConversionId): void
    {
        try {
            /** @var FileConversionJob $fileJobConversion */
            $fileJobConversion = $this->fileConversionJobRepository->find($fileJobConversionId);
            $this->markFileConversionAsStarted($fileJobConversion);

            sleep(180);

            $this->markFileConversionAsCompleted($fileJobConversion);
        } catch (\Throwable $throwable) {
            $this->markFileConversionAsFailed($fileJobConversion, $throwable);
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
     * @return void
     */
    private function markFileConversionAsCompleted(FileConversionJob $fileJobConversion): void
    {
        $fileJobConversion
            ->setStatus(FileConversionJobStatusEnum::COMPLETED)
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
