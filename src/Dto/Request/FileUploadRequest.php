<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

readonly class FileUploadRequest implements RequestDtoInterface
{
    public function __construct(
        #[OA\Property(property: 'file', description: 'File to convert', type: 'string', format: 'binary')]
        #[Assert\NotNull(message: 'Please upload a file.')]
        #[Assert\File(
            maxSize: '100M',
            mimeTypes: [
                'text/csv',
                'application/json',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.oasis.opendocument.spreadsheet'
            ],
            mimeTypesMessage: 'Invalid file type. Supported file types: csv, json, xlsx and ods.',
        )]
        public ?UploadedFile $file = null,

        #[OA\Property(
            property: 'target_formats',
            description: 'Target formats to convert to',
            type: 'array',
            items: new OA\Items(type: 'string', enum: ['json', 'xml']))
        ]
        #[Assert\NotBlank(message: 'Please select at least one format.')]
        #[Assert\All([
            new Assert\Choice(
                choices: ['json', 'xml'],
                message: 'Invalid target format selected. Supported formats: json, xml.',
            )
        ])]
        public array $targetFormats = [],
    )
    {
    }
}
