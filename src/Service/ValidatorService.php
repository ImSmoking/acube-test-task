<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\RequestDtoInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatorService
{
    public function __construct(
        private readonly ValidatorInterface $validator
    )
    {
    }

    public function validateDtoRequest(RequestDtoInterface $requestDto): ?JsonResponse
    {
        $validationErrors = $this->validator->validate($requestDto);

        if ($validationErrors->count() > 0) {
            $errors = [];
            foreach ($validationErrors as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            return new JsonResponse(data: ['errors' => $errors], status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return null;
    }
}