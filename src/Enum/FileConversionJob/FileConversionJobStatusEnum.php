<?php

declare(strict_types=1);

namespace App\Enum\FileConversionJob;

enum FileConversionJobStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
