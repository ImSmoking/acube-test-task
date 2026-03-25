<?php

declare(strict_types=1);


namespace App\Manager;

use App\Entity\File;
use App\Repository\FileRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class FileManager
{
    public function __construct(
        private FileRepository $fileRepository,
        private string $filesUploadFolder
    )
    {
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return File
     */
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
