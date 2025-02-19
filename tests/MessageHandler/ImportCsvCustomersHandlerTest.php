<?php
declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Document\Customer;
use App\Message\ImportCsvCustomers;
use App\Message\ProgressBarMessage;
use App\MessageHandler\ImportCsvCustomersHandler;
use App\Repository\CustomerRepository;
use App\Repository\ImportReportRepository;
use App\Services\PercentageCalculationService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\ResetDatabase;
use Doctrine\ODM\MongoDB\DocumentManager;

class ImportCsvCustomersHandlerTest extends KernelTestCase
{
    use ResetDatabase;

     private LoggerInterface $logger;
     private CustomerRepository $customerRepository;
    private ImportReportRepository $importReportRepository;
     private ValidatorInterface $validator;
     private MessageBusInterface $bus;
     private ImportCsvCustomersHandler $handler;
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = self::getContainer()->get(LoggerInterface::class);
        $this->customerRepository = self::getContainer()->get(CustomerRepository::class);
        $this->importReportRepository = self::getContainer()->get(ImportReportRepository::class);
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
        $this->bus = self::getContainer()->get(MessageBusInterface::class);
        $percentageCalculationService = self::getContainer()->get(PercentageCalculationService::class);
        $this->handler = new ImportCsvCustomersHandler(
            $this->logger,
            $this->customerRepository,
            $this->importReportRepository,
            $this->validator,
            $percentageCalculationService,
            30
        );
    }

    /** @test  */
    public function file_not_exist(): void
    {
        $message = new ImportCsvCustomers('data/not_exist.csv', 'not_exist.csv');

        $this->expectExceptionMessage("File not found: data/not_exist.csv" );
        $this->handler->__invoke($message);
    }

    /** @test  */
    public function process_duplicate_row(): void
    {
        $file = new File(__DIR__ . '/../data/data_duplicate_row.csv')  ;
        $message = new ImportCsvCustomers($file->getPathname(), $file->getFilename());

        $this->handler->__invoke($message);

        $customers = $this->customerRepository->findBy(['email' => 'email@email.test']);
        $reports = $this->importReportRepository->findBy(['file' => $file->getFilename()]);
        self::assertCount(1, $customers);
        self::assertCount(1, $reports);
        $this->assertSame(100.0, $reports[0]->getPercentage());
    }

    /** @test */
    public function process_100_percent(): void
    {
        $file = new File(__DIR__ . '/../data/data_test.csv')  ;
        $message = new ImportCsvCustomers($file->getPathname(), $file->getFilename());

        $this->handler->__invoke($message);

        $reports = $this->importReportRepository->findBy(['file' => $file->getFilename()]);
        self::assertCount(1, $reports);
        $this->assertSame(100.0, $reports[0]->getPercentage());
    }



}
