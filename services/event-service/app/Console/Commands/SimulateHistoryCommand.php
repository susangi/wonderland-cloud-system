<?php

namespace App\Console\Commands;

use App\Services\SimulatorService;
use Illuminate\Console\Command;

// php artisan simulate:history --days=30
// Fills park_events with SAMPLE data for past days so Metabase and the admin view have something to show.
class SimulateHistoryCommand extends Command
{
    protected $signature = 'simulate:history {--days=30 : How many past days to fill}';

    protected $description = 'Insert sample purchases, check-ins and ride uses for past days (source = simulated)';

    public function handle(SimulatorService $simulatorService): int
    {
        $days = (int) $this->option('days');

        if ($days < 1 || $days > 365) {
            $this->error('--days must be between 1 and 365.');

            return self::FAILURE;
        }

        $counts = $simulatorService->generateHistory($days);

        $this->info("Sample data for the last {$days} days added (source = simulated):");
        $this->table(['Event type', 'Rows'], collect($counts)->map(fn ($rows, $type) => [$type, $rows])->values()->all());

        return self::SUCCESS;
    }
}
