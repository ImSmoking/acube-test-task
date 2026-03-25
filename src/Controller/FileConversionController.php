<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\FileUploadRequest;
use App\Dto\Response\FileConversionJobResponse;
use App\Dto\Response\FileUploadResponse;
use App\Entity\FileConversionJob;
use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Manager\FileConversionJobManager;
use App\Service\ConversionDispatcherService;
use App\Service\ValidatorService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
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

    /**
     * @throws ExceptionInterface
     */
    #[Route('/upload', name: 'api.file_conversion_job.upload', methods: ['POST'])]
    #[OA\Response(
        response: Response::HTTP_ACCEPTED,
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
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
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
            targetFormats: is_string($request->request->get('target_formats')) ? explode(',', $request->request->get('target_formats')) : []
        );

        // Validating the request
        if (!is_null($errorResponse = $this->validatorService->validateDtoRequest($fileUploadRequest))) {
            return $errorResponse;
        }

        $file = $conversionDispatcherService->dispatchConversionFromUploadRequest($fileUploadRequest);

        return $this->json(
            data: FileUploadResponse::fromEntity($file),
            status: Response::HTTP_ACCEPTED,
            context: ['groups' => ['file:conversion:upload']]
        );
    }


    #[Route('/{file_conversion_job_id}/status', name: 'api.file_conversion_job.status', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'File accepted for conversion',
        content: new OA\JsonContent(
            ref: new Model(type: FileConversionJobResponse::class)
        )
    )]
    public function status(
        #[MapEntity(mapping: ['file_conversion_job_id' => 'id'])]
        FileConversionJob $fileConversionJob
    ): JsonResponse
    {
        return $this->json(
            data: FileConversionJobResponse::fromEntity($fileConversionJob),
            status: Response::HTTP_OK,
            context: ['groups' => ['file:conversion:upload']]
        );
    }


    #[Route('/{file_conversion_job_id}/download', name: 'api.file_conversion_job.download', methods: ['GET'])]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Converted File',
        content: new OA\MediaType(mediaType: 'application/octet-stream')
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'File conversion job not found',
        content: new OA\MediaType(mediaType: 'application/octet-stream')
    )]
    #[OA\Response(
        response: Response::HTTP_UNPROCESSABLE_ENTITY,
        description: 'Conversion not ready',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'code',
                    type: 'int',
                    example: Response::HTTP_UNPROCESSABLE_ENTITY
                ),
                new OA\Property(
                    property: 'error',
                    type: 'string',
                    example: 'File conversion job has failed'
                ),
                new OA\Property(
                    property: 'error_message',
                    type: 'string',
                    example: 'Error message.'
                )
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_CONFLICT,
        description: 'Conversion not ready',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'code',
                    type: 'int',
                    example: Response::HTTP_CONFLICT
                ),
                new OA\Property(
                    property: 'error',
                    type: 'string',
                    example: 'File conversion job is still being processed'
                )
            ]
        )
    )]
    public function download(
        #[MapEntity(mapping: ['file_conversion_job_id' => 'id'])]
        FileConversionJob $fileConversionJob,
        FileConversionJobManager $fileConversionJobManager
    ): Response
    {
        if ($fileConversionJob->getStatus() === FileConversionJobStatusEnum::PROCESSING) {
            return $this->json(
                data: [
                    'status' => Response::HTTP_CONFLICT,
                    'error' => 'File conversion job is still being processed'
                ],
                status: Response::HTTP_CONFLICT,
            );
        }

        if ($fileConversionJob->getStatus() === FileConversionJobStatusEnum::FAILED) {
            return $this->json(
                data: [
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'error' => 'File conversion job is still being processed',
                    'error_message' => $fileConversionJob->getErrorMessage()
                ],
                status: Response::HTTP_CONFLICT,
            );
        }

        return $fileConversionJobManager->buildDownloadResponse($fileConversionJob);
    }
}
