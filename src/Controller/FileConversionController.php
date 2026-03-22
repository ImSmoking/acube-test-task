<?php

namespace App\Controller;

use App\Dto\FileUploadResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/files')]
#[OA\Tag(name: 'File Conversions')]
final class FileConversionController extends AbstractController
{
    #[Route('/upload', name: 'api.files.upload', methods: ['POST'])]
    #[OA\Response(
        response: 202,
        description: 'File accepted for conversion',
        content: new OA\JsonContent(
            ref: new Model(type: FileUploadResponse::class)
        )
    )]
    public function upload(): JsonResponse
    {
        return $this->json([]);
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
