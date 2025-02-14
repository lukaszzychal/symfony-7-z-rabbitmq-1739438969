<?php

namespace App\Tests\Controller;

use App\Document\ImportProgressBar;
use App\Message\ImportCsvCustomers;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Zenstruck\Foundry\Test\ResetDatabase;

final class CsvControllerTest extends WebTestCase
{
    use ResetDatabase  ;

    private const UPLOAD_DIR = '/data';

    /** @test */
    public function index(): void
    {
        $client = static::createClient();
        $client->request('GET', '/csv');

        self::assertResponseIsSuccessful();
    }

    /** @test */
    public function  uploadCsvFile(): void
    {
        $client = static::createClient();

        $this->requestSendForm($client);

        $this->assertSendMessageImportCsvCustomers();
    }

    /** @test */
    public function uploadInvalidTypeFile(): void
    {
        $client = static::createClient();

        $this->requestSendInvalidFormatForm($client);

        $this->assertResponseStatusCodeSame(400, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('{"error":"Invalid file format"}', $client->getResponse()->getContent());
    }


    /**  @test */
    public function changePercentWhenNotStartProcess(): void
    {
        $client = static::createClient();
        $fileName = 'data_test_not_exist.csv';

        $client->request('GET', '/import/status/'.$fileName);

        self::assertStringContainsStringIgnoringCase('{"percentage":0}', $client->getResponse()->getContent());
    }

    /**  @test */
    public function changePercent(): void
    {
        $client = static::createClient();
        $fileName = 'data_test.csv';
        $dm = self::getContainer()->get(DocumentManager::class);
        $importProgressBar = new ImportProgressBar($fileName, 10);
        $dm->persist($importProgressBar);
        $dm->flush();
        $client->request('GET', '/import/status/'.$fileName);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(  '{"percentage":10}', $client->getResponse()->getContent());
    }


    /**
     * @param \Symfony\Bundle\FrameworkBundle\KernelBrowser $client
     * @return void
     */
    public function requestSendForm(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request('GET', '/csv');
        $crawler = $client->submitForm('Upload', [
            'file' => __DIR__ . '/../' . self::UPLOAD_DIR . '/data_test.csv',
        ]);
    }

    /**
     * @return void
     */
    public function assertSendMessageImportCsvCustomers(): void
    {
        self::assertResponseIsSuccessful();

        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.async');
        $this->assertCount(1, $transport->getSent());
        $message = $transport->getSent()[0]->getMessage();
        $this->assertInstanceOf(ImportCsvCustomers::class, $message);
        $this->assertStringContainsString('/tmp/import_', $message->filepath);
    }

    private function requestSendInvalidFormatForm(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request('GET', '/csv');
        $crawler = $client->submitForm('Upload', [
            'file' => __DIR__ . '/../' . self::UPLOAD_DIR . '/data_test.txt',
        ]);
    }


}
