<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document()]
class ImportReport
{
    #[ODM\Id(strategy: 'UUID')]
    private ?string $id = null;

    #[ODM\Field()]
    private float $percentage = 0;

    #[ODM\Field()]
    private array $errors = [];

    #[ODM\Field()]
    private int $sizeProgress = 0;

    public function getSizeProgress(): int
    {
        return $this->sizeProgress;
    }

    public function __construct(
        #[ODM\Field()]
        private string $file
    )
    {

    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function updatePercentage(float $percentage): void
    {
        if ($percentage > $this->percentage) {
            $this->percentage = $percentage;
        }

    }

    public function getPercentage(): float
    {
        return (float) number_format($this->percentage, 2);
    }

    public function isDone(): bool
    {
        return 100 === (int) $this->percentage;
    }

    public function addError(array $error): void
    {
        $this->errors[] = $error;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setSizeProgress(int $sizeProgress): void
    {
        $this->sizeProgress = $sizeProgress;
    }


}