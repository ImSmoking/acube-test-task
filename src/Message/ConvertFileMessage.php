<?php

declare(strict_types=1);

namespace App\Message;

final readonly class ConvertFileMessage
{
    public function __construct(
        public string $fileConversionJobId,
    )
    {
    }
}
