<?php

namespace App\Console\Commands;

use App\Services\SimulatorService;
use Illuminate\Console\Command;

// php artisan simulate:tickets-today --count=50
// Creates checked in SAMPLE tickets for today. simulate:burst then scans rides with them.
class SimulateTicketsTodayCommand extends Command
{
    protected $signature = 'simulate:tickets-today {--count=50 : How many tickets to create}';

    protected $description = 'Create checked in sample tickets for today (references start with SIM-)';

    public function handle(SimulatorService $simulatorService): int
    {
        $count = (int) $this->option('count');

        if ($count < 1 || $count > 1000) {
            $this->error('--count must be between 1 and 1000.');

            return self::FAILURE;
        }

        $created = $simulatorService->createCheckedInTicketsForToday($count);

        $this->info("Created {$created} checked in sample tickets for today.");

        return self::SUCCESS;
    }
}
