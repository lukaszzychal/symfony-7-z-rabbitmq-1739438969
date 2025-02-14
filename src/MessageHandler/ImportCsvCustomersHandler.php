<?php

namespace App\MessageHandler;

use App\Document\Customer;
use App\Message\ImportCsvCustomers;
use App\Message\ProgressBarMessage;
use App\Repository\CustomerRepository;
use App\Services\PercentageCalculationService;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Driver\Exception\BulkWriteException;
use Psr\Log\LoggerInterface;
use SplFileObject;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
final class ImportCsvCustomersHandler
{
    private const INVALID_DATA_LOG_FILE = __DIR__ . '/../invalid_report.log';


    public function __construct(
        private LoggerInterface     $logger,
        private CustomerRepository     $customerRepository,
        private ValidatorInterface  $validator,
        private MessageBusInterface $bus,
        private PercentageCalculationService $percentageCalculationService
    )
    {

    }

    public function __invoke(ImportCsvCustomers $message): void
    {
        ;
        if (!file_exists($message->filepath)) {
            throw new \RuntimeException('File not found: ' . $message->filepath);
        }

        $fileObj = new SplFileObject($message->filepath);
        $fileObj->setFlags(
            SplFileObject::READ_CSV
            | SplFileObject::READ_AHEAD
            | SplFileObject::SKIP_EMPTY
            | SplFileObject::DROP_NEW_LINE
        );

        $batchSize = 20;
        $sizeProgress = 0;
        $fileObj->seek(PHP_INT_MAX);
        $sizeRow = $fileObj->key();
        foreach ($fileObj as $key => $row) {
            $sizeProgress++;
            $percent = $this->percentageCalculationService->getPercentage($sizeProgress, $sizeRow);
            if ($key === 0) {
                continue;
            }
            [$id, $fullName, $email, $city] = $row;
            $customer = new Customer($fullName, $email, $city);;
            $constraints = $this->validator->validate($customer);
            if (count($constraints) > 0) {
                $this->invalidRow($constraints, $customer);
                continue;
            }

            $this->customerRepository->persist($customer);
            if (($sizeProgress % $batchSize) === 0) {

                $this->logger->info('Progress: ' . $percent . '%');
                $this->bus->dispatch(new ProgressBarMessage($fileObj->getFilename(), $percent));
                $this->flushAndClear();
            }
        }
        $this->flushAndClear();
        $this->bus->dispatch(new ProgressBarMessage($fileObj->getFilename(), 100));
        $percent = $this->percentageCalculationService->getPercentage($sizeProgress, $sizeRow);
        $this->logger->info('Progress: ' . $percent . '%');

        unset($file);

    }


    private function invalidRow(ConstraintViolationListInterface $constraints, Customer $customer): void
    {
        $this->logger->warning(
            "Invalid Data:", $customer->toArray()
        );

        file_put_contents(
            self::INVALID_DATA_LOG_FILE,
            "Invalid Data:" . json_encode($customer->toArray()) . PHP_EOL,
            FILE_APPEND
        );


        foreach ($constraints as $constraint) {

            $message = $constraint->getMessage();
            $property = $constraint->getPropertyPath();
            $invalidValue = $constraint->getInvalidValue();
            $this->logger->warning(
                "Invalid value: Message: {$message}, Property: {$property}, InvalidValue: {$invalidValue}"
            );

            file_put_contents(
                self::INVALID_DATA_LOG_FILE,
                "Invalid value: Message: {$message}, Property: {$property}, InvalidValue: {$invalidValue}" . PHP_EOL,
                FILE_APPEND
            );
        }

    }

    /**
     * @return void
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \Throwable
     */
    private function flushAndClear(): void
    {
        try {
            $this->customerRepository->flushAndClear();
        } catch (BulkWriteException $e) {
            $this->logger->warning($e->getMessage());
        }

    }
}
