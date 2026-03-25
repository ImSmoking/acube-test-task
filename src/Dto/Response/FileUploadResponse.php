<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\File;
use App\Entity\FileConversionJob;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

#[OA\Schema(schema: 'FileUploadResponse')]
readonly class FileUploadResponse
{
    public function __construct(
        #[OA\Property(property: 'id', type: 'string', example: '07f7932b-4478-4457-a9ff-7a32728d9310')]
        #[Groups(['file:conversion:upload'])]
        public string $id,

        #[OA\Property(property: 'original_filename', description: 'original name of the file', type: 'string', example: 'uploaded_filename.csv')]
        #[Groups(['file:conversion:upload'])]
        public string $originalFilename,

        #[OA\Property(property: 'original_filename', type: 'string')]
        #[Groups(['file:conversion:upload'])]
        public string $extension,

        #[OA\Property(property: 'original_filename')]
        #[Groups(['file:conversion:upload'])]
        public int $size,

        #[OA\Property(
            property: 'file_conversion_jobs',
            description: 'Conversions queued',
            type: 'array',
            items: new OA\Items(ref: new Model(type: FileConversionJobResponse::class)),
        )]
        #[Groups(['file:conversion:upload'])]
        public array $fileConversionJobs = []
    )
    {
    }

    public static function fromEntity(File $file): self
    {
        $conversionJobs = [];

        /** @var FileConversionJob $conversionJob */
        foreach ($file->getFileConversionJobs() as $conversionJob) {
            $conversionJobs[] = FileConversionJobResponse::fromEntity($conversionJob);
        }
        return new self(
            id: $file->id->__toString(),
            originalFilename: $file->getOriginalFilename(),
            extension: $file->getExtension(),
            size: $file->getSize(),
            fileConversionJobs: $conversionJobs
        );
    }
}
