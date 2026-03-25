<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

readonly class FileConversionJobResponse
{
    public function __construct(
        #[OA\Property(property: 'id', type: 'string', readOnly: true, example: '07f7932b-4478-4457-a9ff-7a32728d9310')]
        #[Groups(['file:conversion:upload'])]
        public string $id,

        #[OA\Property(property: 'status', type: 'string', enum: FileConversionJobStatusEnum::class, readOnly: true, example: 'pending')]
        #[Groups(['file:conversion:upload'])]
        public string $status,

        #[OA\Property(property: 'target_format', type: 'string', enum: FileConversionJobTargetFormatEnum::class, readOnly: true, example: 'csv')]
        #[Groups(['file:conversion:upload'])]
        public string $targetFormat,

        #[OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-03-21T14:30:00+00:00')]
        #[Groups(['file:conversion:upload'])]
        public \DateTimeImmutable $createdAt,

        #[OA\Property(property: 'completed_at', type: 'string', format: 'date-time', example: '2024-03-21T14:30:00+00:00')]
        #[Groups(['file:conversion:upload'])]
        public ?\DateTimeImmutable $completeAt = null,

        #[OA\Property(property: 'error_message', type: 'string', readOnly: true, example: 'error message')]
        #[Groups(['file:conversion:upload'])]
        public ?string $errorMessage = null,
    )
    {
    }


    public static function fromEntity(FileConversionJob $fileConversionJob): self
    {
        return new self(
            id: $fileConversionJob->id->__toString(),
            status: $fileConversionJob->getStatus()->value,
            targetFormat: $fileConversionJob->getTargetFormat()->value,
            createdAt: $fileConversionJob->getCreatedAt(),
            completeAt: $fileConversionJob->getCompletedAt(),
            errorMessage: $fileConversionJob->getErrorMessage()
        );
    }
}
