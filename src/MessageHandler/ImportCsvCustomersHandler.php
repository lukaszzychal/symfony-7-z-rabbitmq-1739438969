<?php

namespace App\MessageHandler;

use App\Document\Customer;
use App\Document\ImportReport;
use App\Message\ImportCsvCustomers;
use App\Repository\CustomerRepository;
use App\Repository\ImportReportRepository;
use App\Services\PercentageCalculationService;
use Psr\Log\LoggerInterface;
use SplFileObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final class ImportCsvCustomersHandler
{
    public function __construct(
        private LoggerInterface              $logger,
        private CustomerRepository           $customerRepository,
        private ImportReportRepository       $importReportRepository,
        private ValidatorInterface           $validator,
        private PercentageCalculationService $percentageCalculationService,
        #[Autowire('%env(int:BATCH_SIZE)%')]
        private int                          $batchSize
    )
    {

    }

    public function __invoke(ImportCsvCustomers $message): void
    {
        if (!file_exists($message->filepath)) {
            throw new \RuntimeException('File not found: ' . $message->filepath);
        }

        $fileObj = $this->createSplCsvFileObject($message);
        $report = $this->createOrFindReport($fileObj);
        $this->importReportRepository->persistAndFlush($report);
        $t1 = microtime(true);
        $sizeProgress = 0;
        $sizeRow = $fileObj->key();
        $fileObj->rewind();

        foreach ($fileObj as $key => $row) {
            ++$sizeProgress;
            if ($this->isHeaders($key)) {
                continue;
            }

            [$id, $fullName, $email, $city] = $row;
            $customer = $this->getOrCreateCustomer($email, $fullName, $city);
            $isValid = $this->validateCustomer($customer);
            if (!$isValid) {
                $report->addError(['message' => 'Invalid data row',
                    'data' => json_encode($row)]);
                continue;
            }

            $this->bulkInserts($sizeProgress, $this->batchSize, $sizeRow, $report);

        }
        $report->updatePercentage(100);
        $report->setSizeProgress($sizeProgress);
        $this->importReportRepository->getDocumentManager()->merge($report);
        $this->customerRepository->flush();
        $this->customerRepository->clear();

        $t2 = microtime(true);
        $this->logger->info('Progress: ' . $report->getPercentage() . '% - ' . ($t2 - $t1) . 's');
        unset($file);
    }

    /**
     * @param ImportCsvCustomers $message
     * @return SplFileObject
     */
    public function createSplCsvFileObject(ImportCsvCustomers $message): SplFileObject
    {
        $fileObj = new SplFileObject($message->filepath);
        $fileObj->setFlags(
            SplFileObject::READ_CSV
            | SplFileObject::READ_AHEAD
            | SplFileObject::SKIP_EMPTY
            | SplFileObject::DROP_NEW_LINE
        );
        $fileObj->seek(PHP_INT_MAX);
        return $fileObj;
    }

    /**
     * @param SplFileObject $fileObj
     * @return ImportReport
     */
    public function createOrFindReport(SplFileObject $fileObj): ImportReport
    {
        $report = $this->importReportRepository->findOneBy(['file' => $fileObj->getFilename()]);
        if ($report) {
            return $report;
        }

        return new ImportReport($fileObj->getFilename());
    }

    /**
     * @param int|string $key
     * @return bool
     */
    public function isHeaders(int $key): bool
    {
        return $key === 0;
    }

    /**
     * @param $email
     * @param $fullName
     * @param $city
     * @return Customer|array|null
     */
    public
    function getOrCreateCustomer($email, $fullName, $city): Customer|array|null
    {
        $customer = $this->customerRepository->findCustomerByEmail($email);
        $scheduledDocumentInsertions = $this->customerRepository->getDocumentManager()->getUnitOfWork()->getScheduledDocumentInsertions();
        $exist = array_values(array_filter($scheduledDocumentInsertions, static fn(Customer $document) => $document->getEmail() === $email));
        $customer = $customer ?? $exist[0] ?? null;
        if (!$customer && !$exist) {
            $customer = new Customer($fullName, $email, $city);
            $this->customerRepository->persist($customer);
        }
        return $customer;
    }

    private function validateCustomer(Customer $customer): bool
    {
        $constraints = $this->validator->validate($customer);
        return !count($constraints);
    }

    /**
     * @param int $sizeProgress
     * @param int $batchSize
     * @param int $sizeRow
     * @param ImportReport $report
     * @return void
     * @throws \Doctrine\ODM\MongoDB\LockException
     */
    public function bulkInserts(int $sizeProgress, int $batchSize, int $sizeRow, ImportReport $report): void
    {
        if ($this->readyBulkInserts($sizeProgress, $batchSize)) {
            $percent = $this->percentageCalculationService->getPercentage($sizeProgress, $sizeRow);
            $report->updatePercentage($percent);
            $this->logger->info($report->getFile().' '.$percent. '%');
            $this->customerRepository->flush();
            $this->customerRepository->clear();
            $this->importReportRepository->getDocumentManager()->merge($report);
        }
    }

    /**
     * @param int $sizeProgress
     * @param int $batchSize
     * @return bool
     */
    public function readyBulkInserts(int $sizeProgress, int $batchSize): bool
    {
        return ($sizeProgress % $batchSize) === 0;
    }
}
