<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\File;
use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use App\Manager\FileManager;
use App\Repository\FileConversionJobRepository;
use App\Service\ConversionProcessorService;
use App\Service\FileConversionService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests covering ConversionProcessorService::processConversion
 */
final class ConversionProcessorServiceTest extends TestCase
{
    /**
     * Scenario: Happy path — full conversion flow from pending to completed
     *
     * @return void
     */
    public function testProcessConversionHappyPath(): void
    {
        $job = $this->createPendingJob();
        $jobId = $job->id->toRfc4122();

        $convertedTmpPath = $this->createTempConvertedFile();
        $outputFile = $this->createOutputFile();

        $repository = $this->createMock(FileConversionJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with($jobId)
            ->willReturn($job);

        $conversionService = $this->createMock(FileConversionService::class);
        $conversionService
            ->expects($this->once())
            ->method('convertFromFileConversionJob')
            ->with($job)
            ->willReturn($convertedTmpPath);

        $fileManager = $this->createMock(FileManager::class);
        $fileManager
            ->expects($this->once())
            ->method('createFromUploadedFile')
            ->willReturn($outputFile);

        $saveCalls = 0;
        $repository
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (FileConversionJob $savedJob) use (&$saveCalls): void {
                $saveCalls++;
                if ($saveCalls === 1) {
                    $this->assertSame(FileConversionJobStatusEnum::PROCESSING, $savedJob->getStatus());
                    $this->assertSame(1, $savedJob->getRetryCount());
                    $this->assertNotNull($savedJob->getStartedAt());
                }
                if ($saveCalls === 2) {
                    $this->assertSame(FileConversionJobStatusEnum::COMPLETED, $savedJob->getStatus());
                    $this->assertNotNull($savedJob->getCompletedAt());
                    $this->assertNotNull($savedJob->getOutputFile());
                }
            });

        $service = new ConversionProcessorService($repository, $conversionService, $fileManager);
        $service->processConversion($jobId);

        $this->assertSame(FileConversionJobStatusEnum::COMPLETED, $job->getStatus());
        $this->assertNotNull($job->getStartedAt());
        $this->assertNotNull($job->getCompletedAt());
        $this->assertNotNull($job->getOutputFile());
        $this->assertSame(1, $job->getRetryCount());
    }

    /**
     * Scenario: Status is set to processing before conversion begins
     *
     * @return void
     */
    public function testProcessConversionSetsStatusToProcessingBeforeConversion(): void
    {
        $job = $this->createPendingJob();
        $jobId = $job->id->toRfc4122();

        $repository = $this->createStub(FileConversionJobRepository::class);
        $repository->method('find')->willReturn($job);

        $conversionService = $this->createMock(FileConversionService::class);
        $conversionService
            ->expects($this->once())
            ->method('convertFromFileConversionJob')
            ->willReturnCallback(function (FileConversionJob $j): string {
                $this->assertSame(FileConversionJobStatusEnum::PROCESSING, $j->getStatus());
                $this->assertSame(1, $j->getRetryCount());
                $this->assertNotNull($j->getStartedAt());

                return $this->createTempConvertedFile();
            });

        $fileManager = $this->createStub(FileManager::class);
        $fileManager->method('createFromUploadedFile')->willReturn($this->createOutputFile());

        $service = new ConversionProcessorService($repository, $conversionService, $fileManager);
        $service->processConversion($jobId);
    }

    /**
     * Scenario: Retry count is incremented on each processing attempt
     *
     * @return void
     */
    public function testProcessConversionIncrementsRetryCountOnEachCall(): void
    {
        $job = $this->createPendingJob();
        $job->setRetryCount(2);
        $jobId = $job->id->toRfc4122();

        $repository = $this->createStub(FileConversionJobRepository::class);
        $repository->method('find')->willReturn($job);

        $conversionService = $this->createStub(FileConversionService::class);
        $conversionService->method('convertFromFileConversionJob')->willReturn($this->createTempConvertedFile());

        $fileManager = $this->createStub(FileManager::class);
        $fileManager->method('createFromUploadedFile')->willReturn($this->createOutputFile());

        $service = new ConversionProcessorService($repository, $conversionService, $fileManager);
        $service->processConversion($jobId);

        $this->assertSame(3, $job->getRetryCount());
    }

    /**
     * Scenario: Conversion failure — job marked as failed and exception is re-thrown
     *
     * @return void
     */
    public function testProcessConversionMarksAsFailedAndRethrowsOnException(): void
    {
        $job = $this->createPendingJob();
        $jobId = $job->id->toRfc4122();
        $exceptionMessage = 'CSV parsing error on line 42';

        $repository = $this->createMock(FileConversionJobRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($job);

        $conversionService = $this->createStub(FileConversionService::class);
        $conversionService
            ->method('convertFromFileConversionJob')
            ->willThrowException(new \RuntimeException($exceptionMessage));

        $saveCalls = 0;
        $repository
            ->expects($this->exactly(2))
            ->method('save')
            ->willReturnCallback(function (FileConversionJob $savedJob) use (&$saveCalls, $exceptionMessage): void {
                $saveCalls++;
                if ($saveCalls === 2) {
                    $this->assertSame(FileConversionJobStatusEnum::FAILED, $savedJob->getStatus());
                    $this->assertSame($exceptionMessage, $savedJob->getErrorMessage());
                }
            });

        $fileManager = $this->createStub(FileManager::class);

        $service = new ConversionProcessorService($repository, $conversionService, $fileManager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $service->processConversion($jobId);
    }

    /**
     * Scenario: Failed job has no completedAt or outputFile
     *
     * @return void
     */
    public function testProcessConversionFailedJobHasNoCompletedAtOrOutputFile(): void
    {
        $job = $this->createPendingJob();
        $jobId = $job->id->toRfc4122();

        $repository = $this->createStub(FileConversionJobRepository::class);
        $repository->method('find')->willReturn($job);

        $conversionService = $this->createStub(FileConversionService::class);
        $conversionService
            ->method('convertFromFileConversionJob')
            ->willThrowException(new \RuntimeException('boom'));

        $fileManager = $this->createStub(FileManager::class);

        $service = new ConversionProcessorService($repository, $conversionService, $fileManager);

        try {
            $service->processConversion($jobId);
        } catch (\RuntimeException) {
        }

        $this->assertSame(FileConversionJobStatusEnum::FAILED, $job->getStatus());
        $this->assertNull($job->getCompletedAt());
        $this->assertNull($job->getOutputFile());
        $this->assertSame('boom', $job->getErrorMessage());
    }


    private function createPendingJob(): FileConversionJob
    {
        $sourceFile = (new File())
            ->setOriginalFilename('data.csv')
            ->setStoredFilename('abc.csv')
            ->setPath(sys_get_temp_dir() . '/')
            ->setMimeType('text/csv')
            ->setExtension('csv')
            ->setSize(128);

        return (new FileConversionJob())
            ->setSourceFile($sourceFile)
            ->setTargetFormat(FileConversionJobTargetFormatEnum::JSON)
            ->setStatus(FileConversionJobStatusEnum::PENDING);
    }

    private function createTempConvertedFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'conv_') . '.json';
        file_put_contents($path, '{"ok":true}');

        return $path;
    }

    private function createOutputFile(): File
    {
        return (new File())
            ->setOriginalFilename('converted.json')
            ->setStoredFilename('output-stored.json')
            ->setPath(sys_get_temp_dir() . '/')
            ->setMimeType('application/json')
            ->setExtension('json')
            ->setSize(42);
    }
}
