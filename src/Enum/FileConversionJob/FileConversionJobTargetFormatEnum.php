<?php

declare(strict_types=1);

namespace App\Enum\FileConversionJob;

enum FileConversionJobTargetFormatEnum: string
{
    case JSON = 'json';
    case XML = 'xml';
}
