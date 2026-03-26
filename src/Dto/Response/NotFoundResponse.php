<?php

declare(strict_types=1);

namespace App\Dto\Response;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Schema(schema: 'NotFoundResponse')]
readonly class NotFoundResponse
{
    public function __construct(
        #[OA\Property(property: 'error', type: 'string', example: 'Resource not found')]
        public string $error,

        #[OA\Property(property: 'status', type: 'integer', example: Response::HTTP_NOT_FOUND)]
        public int $status,

        #[OA\Property(
            property: 'trace',
            description: 'Stack trace (only available in dev environment)',
            type: 'array',
            items: new OA\Items(type: 'string'),
            nullable: true,
            example: null
        )]
        public ?array $trace = null,
    ) {
    }
}
