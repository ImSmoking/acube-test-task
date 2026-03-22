<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\File;
use App\Entity\FileConversionJob;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'FileUploadResponse')]
readonly class FileUploadResponse
{
    public function __construct(
        #[OA\Property(type: 'string', example: 'uuid')]
        public string $id,

        #[OA\Property(
            description: 'Conversions queued',
            type: 'array',
            items: new OA\Items(ref: new Model(type: FileConversionJobResponse::class))
        )]
        public array $fileConversionJobs
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
            fileConversionJobs: $conversionJobs
        );
    }
}
