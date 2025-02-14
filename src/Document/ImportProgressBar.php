<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document()]
class ImportProgressBar
{
    #[ODM\Id(strategy: 'UUID')]
    private ?string $id = null;

    public function __construct(
        #[ODM\Field(type: 'string')]
        private ?string $file = null,

        #[ODM\Field(type: 'int')]
        private ?int $percentage = null,

    )
    {

    }

    public function updatePercentage(?int $percentage): void
    {
        $this->percentage = $percentage;
    }

    public function getPercentage(): ?int
    {
        return $this->percentage;
    }

    public function isDone(): bool
    {
        return  100 === (int) $this->percentage;
    }
    

}