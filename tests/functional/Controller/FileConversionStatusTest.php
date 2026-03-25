<?php

declare(strict_types=1);

namespace App\Tests\functional\Controller;

use App\Enum\FileConversionJob\FileConversionJobStatusEnum;
use App\Tests\Factory\FileConversionJobFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests covering the GET /api/file-conversions/{file_conversion_job_id}/status endpoint.
 */
final class FileConversionStatusTest extends WebTestCase
{
    use ResetDatabase;

    private const ENDPOINT = '/api/file-conversions/%s/status';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    /**
     * Scenario: Happy path
     *
     * @return void
     */
    public function testGetStatusSuccessfully(): void
    {
        $job = FileConversionJobFactory::createOne();

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $job->id->toRfc4122()),
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(200);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('target_format', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('started_at', $data);
        $this->assertArrayHasKey('complete_at', $data);
        $this->assertArrayHasKey('conversion_time', $data);
        $this->assertArrayHasKey('error_message', $data);

        $this->assertSame($job->id->toRfc4122(), $data['id']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame('json', $data['target_format']);
    }

    /**
     * Scenario: Completed job — started_at, complete_at, and conversion_time must all be populated
     *
     * @return void
     */
    public function testGetStatusForCompletedJobPopulatesTimingFields(): void
    {
        $startedAt = new \DateTimeImmutable('2024-01-15T10:00:00+00:00');
        $completedAt = new \DateTimeImmutable('2024-01-15T10:00:42+00:00');
        $expectedConversionSeconds = $completedAt->getTimestamp() - $startedAt->getTimestamp();

        $job = FileConversionJobFactory::createOne([
            'status' => FileConversionJobStatusEnum::COMPLETED,
            'startedAt' => $startedAt,
            'completedAt' => $completedAt,
        ]);

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $job->id->toRfc4122()),
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('completed', $data['status']);

        $this->assertNotNull($data['started_at']);
        $this->assertNotNull($data['complete_at']);
        $this->assertNotNull($data['conversion_time']);

        $this->assertSame($expectedConversionSeconds, $data['conversion_time']);
    }

    /**
     * Scenario: Failed job — complete_at and conversion_time are null, error_message is set
     *
     * @return void
     */
    public function testGetStatusForFailedJobHasErrorAndNullTimingEndFields(): void
    {
        $errorMessage = 'Conversion failed: source file is corrupted.';

        $job = FileConversionJobFactory::createOne([
            'status' => FileConversionJobStatusEnum::FAILED,
            'errorMessage' => $errorMessage,
            'startedAt' => new \DateTimeImmutable('2024-01-15T10:00:00+00:00'),
            'completedAt' => null,
        ]);

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $job->id->toRfc4122()),
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('failed', $data['status']);

        $this->assertNull($data['complete_at'] ?? null);
        $this->assertNull($data['conversion_time'] ?? null);

        $this->assertArrayHasKey('error_message', $data);
        $this->assertSame($errorMessage, $data['error_message']);
    }

    /**
     * Scenario: Job not found
     *
     * @return void
     */
    public function testGetStatusReturns404WhenJobNotFound(): void
    {
        $unknownId = Uuid::v4()->toRfc4122();

        $this->client->request(
            method: 'GET',
            uri: sprintf(self::ENDPOINT, $unknownId),
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
