<?php

declare(strict_types=1);

namespace App\Services;

class PercentageCalculationService
{


    /**
     * @param int $sizeProgress
     * @param int $sizeRow
     * @return float|int
     */
    public function getPercentage(int $sizeProgress, int $sizeRow): int|float
    {
        return ($sizeProgress * 100) / $sizeRow;
    }

}