<?php

namespace App\MessageHandler;

use App\Document\ImportProgressBar;
use App\Message\ProgressBarMessage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProgressBarMessageHandler
{
    public function __construct(private DocumentManager $dm)
    {

    }

    public function __invoke(ProgressBarMessage $message): void
    {

        $importProgressBar = $this->dm->getRepository(ImportProgressBar::class)
            ->findOneBy(['file' => $message->fileName]);

        if ($importProgressBar) {
            if (!$importProgressBar->isDone()) {
                $importProgressBar->updatePercentage($message->percentage);
                $this->dm->flush();
            }

        }
        $importProgressBar = new ImportProgressBar($message->fileName, $message->percentage);
        $this->dm->persist($importProgressBar);
        $this->dm->flush();

    }

    /**
     * @param $importProgressBar
     * @return void
     */
    public function removeIfDone(ImportProgressBar $importProgressBar): void
    {
        if ($importProgressBar->isDone()) {
            $this->dm->remove($importProgressBar);
            $this->dm->flush();
            $this->dm->clear();
        }
    }
}
