<?php

declare(strict_types=1);

namespace App\Enum;

enum SupportedFileTypeEnum: string
{
    case CSV = 'csv';
    case JSON = 'json';
    case XLSX = 'xlsx';
    case ODS = 'ods';
}
