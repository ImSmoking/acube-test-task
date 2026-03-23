<?php

declare(strict_types=1);


namespace App\Manager;

use App\Entity\File;
use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use App\Repository\FileConversionJobRepository;

class FileConversionJobManager
{
    public function __construct(
        private readonly FileConversionJobRepository $fileConversionJobRepository,
    )
    {
    }

    public function createForMultipleTargetFormats(File $file, array $targetFormats): void
    {
        $lastTargetFormat = end($targetFormats);
        $persist = false;
        foreach ($targetFormats as $targetFormat) {
            $conversionJob = new FileConversionJob()
                ->setSourceFile($file)
                ->setTargetFormat(FileConversionJobTargetFormatEnum::from($targetFormat));

            if ($lastTargetFormat === $targetFormat) {
                $persist = true;
            }
            $this->fileConversionJobRepository->save($conversionJob, $persist);
        }
    }
}