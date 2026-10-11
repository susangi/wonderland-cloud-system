<?php

namespace App\Console\Commands;

use App\Services\OutboxService;
use Illuminate\Console\Command;

// Resends TICKET_PURCHASE events that could not reach event-service (design 7.3).
// Runs every minute: Kubernetes CronJob in the cloud, "php artisan schedule:work" on the laptop.
class FlushOutboxCommand extends Command
{
    protected $signature = 'outbox:flush';

    protected $description = 'Send unsent outbox events to event-service';

    public function handle(OutboxService $outboxService): int
    {
        $result = $outboxService->flushUnsent();

        $this->info("Outbox flush finished. Sent: {$result['sent']}, failed: {$result['failed']}.");

        return self::SUCCESS;
    }
}
