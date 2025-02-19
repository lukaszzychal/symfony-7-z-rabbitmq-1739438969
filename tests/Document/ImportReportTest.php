<?php
declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\ImportReport;
use PHPUnit\Framework\TestCase;

class ImportReportTest extends TestCase
{
        /** @test  */
    public function importReportWhen0PercentageThenNotDone(): void
    {
        $report = new ImportReport('test');

        $this->assertSame(0.0, $report->getPercentage());
        $this->assertFalse($report->isDone());
    }

    /** @test  */
    public function importReportWhenUpdate40PercentageThenNotDone(): void
    {
        $report = new ImportReport('test');

        $report->updatePercentage(40);

        $this->assertSame(40.0, $report->getPercentage());
        $this->assertFalse($report->isDone());
    }

    /** @test  */
    public function importReportUpadateWith100ThenDone(): void
    {
        $report = new ImportReport('test');

        $report->updatePercentage(100);

        $this->assertSame(100.0, $report->getPercentage());
        $this->assertTrue($report->isDone());
    }
}
