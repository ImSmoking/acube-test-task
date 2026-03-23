<?php

declare(strict_types=1);


namespace App\Manager;

use App\Entity\File;
use App\Repository\FileRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileManager
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly string $filesUploadFolder
    )
    {
    }

    public function createFromUploadedFile(UploadedFile $uploadedFile): File
    {
        $file = new File()
            ->setOriginalFilename($uploadedFile->getClientOriginalName())
            ->setExtension($uploadedFile->getClientOriginalExtension())
            ->setMimeType($uploadedFile->getMimeType())
            ->setPath($this->filesUploadFolder)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setSize($uploadedFile->getSize());

        $storedFilename = $file->id . '.' . $file->getExtension();

        $file->setStoredFilename($storedFilename);

        $this->fileRepository->save($file);

        return $file;
    }
}