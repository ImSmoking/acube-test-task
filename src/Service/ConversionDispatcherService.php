<?php

declare(strict_types=1);


namespace App\Service;

use App\Dto\Request\FileUploadRequest;
use App\Entity\File;
use App\Entity\FileConversionJob;
use App\Manager\FileConversionJobManager;
use App\Manager\FileManager;
use App\Message\ConvertFileMessage;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ConversionDispatcherService
{
    public function __construct(
        private FileManager $fileManager,
        private FileConversionJobManager $fileConversionJobManager,
        private MessageBusInterface $messageBus,
    )
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function dispatchConversionFromUploadRequest(FileUploadRequest $uploadRequest): File
    {
        $uploadedFile = $uploadRequest->file;
        $targetFormats = $uploadRequest->targetFormats;

        $file = $this->fileManager->createFromUploadedFile($uploadRequest->file);
        $this->fileConversionJobManager->createForMultipleTargetFormats($file, $targetFormats);

        /** @var FileConversionJob $fileConversionJob */
        foreach ($file->getFileConversionJobs() as $fileConversionJob) {
            $this->messageBus->dispatch(new ConvertFileMessage($fileConversionJob->id->__toString()));
        }

        $uploadedFile->move($file->getPath(), $file->getStoredFilename());

        return $file;
    }

}
