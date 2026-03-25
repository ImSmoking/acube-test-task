<?php

declare(strict_types=1);


namespace App\Service;

use App\Dto\Request\FileUploadRequest;
use App\Entity\File;
use App\Manager\FileConversionJobManager;
use App\Manager\FileManager;

readonly class ConversionDispatcherService
{
    public function __construct(
        private FileManager $fileManager,
        private FileConversionJobManager $fileConversionJobManager,
    )
    {
    }

    public function dispatchConversionFromUploadRequest(FileUploadRequest $uploadRequest): File
    {
        $uploadedFile = $uploadRequest->file;
        $targetFormats = $uploadRequest->targetFormats;

        $file = $this->fileManager->createFromUploadedFile($uploadRequest->file);
        $this->fileConversionJobManager->createForMultipleTargetFormats($file, $targetFormats);
        
        $uploadedFile->move($file->getPath(), $file->getStoredFilename());

        return $file;
    }
}
