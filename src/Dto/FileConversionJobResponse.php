<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use OpenApi\Attributes as OA;

readonly class FileConversionJobResponse
{
    public function __construct(
        #[OA\Property(type: 'string', readOnly: true, example: 'uuid')]
        public string $id,
        #[OA\Property(type: 'string', enum: FileConversionJobStatusEnum::class, readOnly: true, example: 'pending')]
        public string $status,
        #[OA\Property(type: 'string', enum: FileConversionJobTargetFormatEnum::class, readOnly: true, example: 'csv')]
        public string $targetFormat,
        #[OA\Property(type: 'string', readOnly: true, example: 'error message')]
        public string $errorMessage,
        #[OA\Property(type: 'string',  format: 'date-time', example: '2024-03-21T14:30:00+00:00')]
        public \DateTimeImmutable $createdAt,
        #[OA\Property(type: 'string',  format: 'date-time', example: '2024-03-21T14:30:00+00:00')]
        public \DateTimeImmutable $completeAt,
    )
    {
    }


    public static function fromEntity(FileConversionJob $fileConversionJob): FileConversionJobResponse
    {
        return new self(
            id: $fileConversionJob->id->__toString(),
            status: $fileConversionJob->getStatus()->value,
            targetFormat: $fileConversionJob->getTargetFormat()->value,
            errorMessage: $fileConversionJob->getErrorMessage(),
            createdAt: $fileConversionJob->getCreatedAt(),
            completeAt: $fileConversionJob->getCompletedAt()
        );
    }
}
