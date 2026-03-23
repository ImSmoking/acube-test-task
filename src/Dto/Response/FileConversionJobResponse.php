<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use OpenApi\Attributes as OA;

readonly class FileConversionJobResponse
{
    public function __construct(
        #[OA\Property(property: 'id', type: 'string', readOnly: true, example: '07f7932b-4478-4457-a9ff-7a32728d9310')]
        public string $id,
        #[OA\Property(property: 'status', type: 'string', enum: FileConversionJobStatusEnum::class, readOnly: true, example: 'pending')]
        public string $status,
        #[OA\Property(property: 'target_format', type: 'string', enum: FileConversionJobTargetFormatEnum::class, readOnly: true, example: 'csv')]
        public string $targetFormat,
        #[OA\Property(property: 'error_message', type: 'string', readOnly: true, example: 'error message')]
        public string $errorMessage,
        #[OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-03-21T14:30:00+00:00')]
        public \DateTimeImmutable $createdAt,
        #[OA\Property(property: 'completed_at', type: 'string', format: 'date-time', example: '2024-03-21T14:30:00+00:00')]
        public \DateTimeImmutable $completeAt,
    )
    {
    }


    public static function fromEntity(FileConversionJob $fileConversionJob): self
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
