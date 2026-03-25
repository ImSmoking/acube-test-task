<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Enum\FileConversionJob\FileConversionJobTargetFormatEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<FileConversionJob>
 */
final class FileConversionJobFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return FileConversionJob::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'sourceFile' => FileFactory::new(),
            'targetFormat' => FileConversionJobTargetFormatEnum::JSON,
            'status' => FileConversionJobStatusEnum::PENDING,
        ];
    }
}
