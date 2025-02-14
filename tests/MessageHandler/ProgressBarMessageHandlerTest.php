<?php
declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Document\ImportProgressBar;
use App\Message\ImportCsvCustomers;
use App\Message\ProgressBarMessage;
use App\MessageHandler\ImportCsvCustomersHandler;
use App\MessageHandler\ProgressBarMessageHandler;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;
use Zenstruck\Foundry\Test\ResetDatabase;

class ProgressBarMessageHandlerTest extends KernelTestCase
{
  use ResetDatabase;

    /** @test */
    public function updateProgressWhenNotYet()
    {
        /** @var DocumentManager $dm */
        $dm = self::getContainer()->get(DocumentManager::class);
        $file = new \SplFileObject(__DIR__ . '/../data/data_test.csv');
        $handler = new ProgressBarMessageHandler($dm);
        $message = new ProgressBarMessage($file->getFilename(), 10);
        $progressBarNotExist = $dm->getRepository(ImportProgressBar::class)->findOneBy(['file' => $message->fileName]);


        $handler($message);

        $progressBar = $dm->getRepository(ImportProgressBar::class)->findOneBy(['file' => $message->fileName]);

        self::assertNull($progressBarNotExist);
        self::assertSame(10, $progressBar->getPercentage());
    }

    /** @test */
    public function updateProgressWhenAlready()
    {
        /** @var DocumentManager $dm */
        $dm = self::getContainer()->get(DocumentManager::class);
        $file = new \SplFileObject(__DIR__ . '/../data/data_test.csv');
        $progressBarInit = new ImportProgressBar($file->getFilename(), 10);
        $dm->persist($progressBarInit);
        $dm->flush();
        $handler = new ProgressBarMessageHandler($dm);
        $message = new ProgressBarMessage($file->getFilename(), 30);

        $handler($message);

        $progressBar = $dm->getRepository(ImportProgressBar::class)->findOneBy(['file' => $message->fileName]);

        self::assertSame(30, $progressBar->getPercentage());
    }

}
