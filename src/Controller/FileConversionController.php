<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\FileUploadRequest;
use App\Dto\Response\FileUploadResponse;
use App\Service\ConversionDispatcherService;
use App\Service\ValidatorService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/file-conversions')]
#[OA\Tag(name: 'File Conversions')]
final class FileConversionController extends AbstractController
{

    public function __construct(
        private readonly ValidatorService $validatorService,
    )
    {
    }

    #[Route('/upload', name: 'api.files.upload', methods: ['POST'])]
    #[OA\Response(
        response: 202,
        description: 'File accepted for conversion',
        content: new OA\JsonContent(
            ref: new Model(type: FileUploadResponse::class)
        )
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: new Model(type: FileUploadRequest::class))
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Error: Unprocessable Content',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    example: [
                        'file' => ['Invalid file type. Supported file types: csv, json, xlsx and ods.']
                    ]
                )
            ]
        )
    )]
    public function upload(Request $request, ConversionDispatcherService $conversionDispatcherService): JsonResponse
    {
        $fileUploadRequest = new FileUploadRequest(
            file: $request->files->get('file'),
            targetFormats: explode(',', $request->request->get('target_formats')),
        );

        // Validating the request
        if (!is_null($errorResponse = $this->validatorService->validateDtoRequest($fileUploadRequest))) {
            return $errorResponse;
        }

        $file = $conversionDispatcherService->dispatchConversionFromUploadRequest($fileUploadRequest);


        return $this->json(data: [], status: Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/status', name: 'api.files.status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        return $this->json([]);
    }

    #[Route('/{id}', name: 'api.files.download', methods: ['GET'])]
    public function download(): Response
    {
        return new Response();
    }
}
