<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ImportLoanSqlDump::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('loan-management:collection-automation')->dailyAt('07:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
