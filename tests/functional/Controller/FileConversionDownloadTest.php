<?php

declare(strict_types=1);

namespace App\Tests\functional\Controller;

use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Tests\Factory\FileConversionJobFactory;
use App\Tests\Factory\FileFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests covering GET /api/file-conversions/{file_conversion_job_id}/download.
 */
final class FileConversionDownloadTest extends WebTestCase
{
    use ResetDatabase;

    private const ENDPOINT = '/api/file-conversions/%s/download';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    /**
     * Scenario: Happy path — completed job returns the converted file as attachment
     *
     * @return void
     */
    public function testDownloadReturnsFileWhenCompleted(): void
    {
        $payload = '{"rows":[{"id":1}]}';
        $tempDir = sys_get_temp_dir() . '/ffc_dl_' . uniqid('', true) . '/';
        self::assertTrue(mkdir($tempDir, 0777, true));

        $storedFilename = 'converted.json';
        self::assertNotFalse(file_put_contents($tempDir . $storedFilename, $payload));

        $sourceFile = FileFactory::createOne([
            'originalFilename' => 'report.csv',
        ]);

        $outputFile = FileFactory::createOne([
            'originalFilename' => $storedFilename,
            'storedFilename' => $storedFilename,
            'path' => $tempDir,
            'mimeType' => 'application/json',
            'extension' => 'json',
            'size' => \strlen($payload),
        ]);

        $job = FileConversionJobFactory::createOne([
            'sourceFile' => $sourceFile,
            'outputFile' => $outputFile,
            'status' => FileConversionJobStatusEnum::COMPLETED,
        ]);

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $job->id->toRfc4122()),
        );

        $response = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('report.json', $disposition);

        $this->assertSame($payload, file_get_contents($outputFile->getPath() . $outputFile->getStoredFilename()));
    }

    /**
     * Scenario: Job still processing
     *
     * @return void
     */
    public function testDownloadReturns409WhenProcessing(): void
    {
        $job = FileConversionJobFactory::createOne([
            'status' => FileConversionJobStatusEnum::PROCESSING,
        ]);

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $job->id->toRfc4122()),
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $this->assertSame('File conversion job is still being processed', $data['error']);
    }

    /**
     * Scenario: Job failed — response contains error payload
     *
     * @return void
     */
    public function testDownloadReturns422WhenFailedWithErrorPayload(): void
    {
        $message = 'Converter exited with code 1';

        $job = FileConversionJobFactory::createOne([
            'status' => FileConversionJobStatusEnum::FAILED,
            'errorMessage' => $message,
        ]);

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $job->id->toRfc4122()),
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $data['status']);
        $this->assertSame($message, $data['error_message']);
    }

    /**
     * Scenario: Job not found
     *
     * @return void
     */
    public function testDownloadReturns404WhenJobNotFound(): void
    {
        $unknownId = Uuid::v4()->toRfc4122();

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $unknownId),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
