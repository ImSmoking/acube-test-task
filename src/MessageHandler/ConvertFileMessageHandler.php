<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ConvertFileMessage;
use App\Service\ConversionProcessorService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class ConvertFileMessageHandler
{
    public function __construct(
        private ConversionProcessorService $conversionProcessorService,
    )
    {
    }

    public function __invoke(ConvertFileMessage $message): void
    {
        $this->conversionProcessorService->processConversion($message->fileConversionJobId);
    }
}
