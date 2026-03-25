<?php

namespace App\Tests\functional\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Functional tests file covering the POST /api/file-conversions/upload endpoint
 */
final class FileConversionUploadTest extends WebTestCase
{
    use ResetDatabase;

    private const ENDPOINT = '/api/file-conversions/upload';
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = FileConversionUploadTest::createClient();
    }

    /**
     * Scenario: Happy path
     *
     * @return void
     */
    public function testUploadSuccessfully(): void
    {
        $file = $this->createCsvFile();

        $this->client->request(
            method: 'POST',
            uri: self::ENDPOINT,
            parameters: ['target_formats' => 'json,xml'],
            files: [
                'file' => $file,
            ]
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(202);


        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('original_filename', $data);
        $this->assertArrayHasKey('extension', $data);
        $this->assertArrayHasKey('mime_type', $data);
        $this->assertArrayHasKey('size', $data);
        $this->assertArrayHasKey('file_conversion_jobs', $data);

        $this->assertCount(2, $data['file_conversion_jobs']);

        $this->assertArrayHasKey('id', $data['file_conversion_jobs'][0]);
        $this->assertArrayHasKey('target_format', $data['file_conversion_jobs'][0]);
        $this->assertArrayHasKey('created_at', $data['file_conversion_jobs'][0]);
        $this->assertArrayHasKey('complete_at', $data['file_conversion_jobs'][0]);
        $this->assertArrayHasKey('conversion_time', $data['file_conversion_jobs'][1]);
        $this->assertArrayHasKey('error_message', $data['file_conversion_jobs'][0]);

        $this->assertSame('pending', $data['file_conversion_jobs'][0]['status']);


        $this->assertArrayHasKey('id', $data['file_conversion_jobs'][1]);
        $this->assertArrayHasKey('target_format', $data['file_conversion_jobs'][1]);
        $this->assertArrayHasKey('created_at', $data['file_conversion_jobs'][1]);
        $this->assertArrayHasKey('complete_at', $data['file_conversion_jobs'][1]);
        $this->assertArrayHasKey('conversion_time', $data['file_conversion_jobs'][1]);
        $this->assertArrayHasKey('error_message', $data['file_conversion_jobs'][1]);

        $this->assertSame('pending', $data['file_conversion_jobs'][1]['status']);
    }

    public function testUploadFailsWithNoFile(): void
    {
        $this->client->request('POST', self::ENDPOINT);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Scenario: Invalid file Mime Type
     *
     * @return void
     */
    public function testUploadFailsWithInvalidMimeType(): void
    {
        $file = $this->createTxtFile();

        $this->client->request(
            method: 'POST',
            uri: self::ENDPOINT,
            parameters: ['target_formats' => 'json,xml'],
            files: ['file' => $file],
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(422);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('file', $data['errors']);
    }


    /**
     * Scenario: Invalid target format
     *
     * @return void
     */
    public function testUploadFailsWithInvalidTargetFormat(): void
    {
        $file = $this->createCsvFile();

        $this->client->request(
            method: 'POST',
            uri: self::ENDPOINT,
            parameters: ['target_formats' => 'docx'],
            files: ['file' => $file],
        );

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(422);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('targetFormats[0]', $data['errors']);
    }

    private function createCsvFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_file') . '.csv';
        file_put_contents($path, "name,email\nJohn,john@example.com\n");

        return new UploadedFile(
            path: $path,
            originalName: 'test_file.csv',
            mimeType: 'text/csv',
            error: UPLOAD_ERR_OK,
            test: true
        );
    }

    private function createTxtFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test_file') . '.csv';
        file_put_contents($path, 'not a csv');

        return new UploadedFile(
            path: $path,
            originalName: 'test.txt',
            mimeType: 'text/plain',
            error: UPLOAD_ERR_OK,
            test: true
        );
    }

}
