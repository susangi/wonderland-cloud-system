<?php

namespace App\Console\Commands;

use App\Repositories\TicketRepository;
use App\Services\SimulatorService;
use App\Traits\UsesParkTime;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

// php artisan simulate:burst --count=2000 --concurrency=50 --base-url=<api url> --token=<admin token>
// Sends many ride scans through the REAL API at the same time, to show the HPA adding pods (NFR-SC-03).
// Without --token it sends the local admin headers (only works when the target runs AUTH_MODE=local).
class SimulateBurstCommand extends Command
{
    use UsesParkTime;

    protected $signature = 'simulate:burst
        {--count=2000 : Total ride scans to send}
        {--concurrency=50 : Scans sent at the same time}
        {--base-url=http://127.0.0.1:8005 : API base URL (API Gateway URL in the cloud)}
        {--token= : Admin access token (Cognito)}';

    protected $description = 'Send a burst of ride scans through the API (uses tickets from simulate:tickets-today)';

    public function handle(TicketRepository $ticketRepository, SimulatorService $simulatorService): int
    {
        $references = $ticketRepository->listSimulatedCheckedIn($this->parkToday())->pluck('booking_reference')->all();

        if (empty($references)) {
            $this->error('No checked in sample tickets for today. Run "php artisan simulate:tickets-today" first.');

            return self::FAILURE;
        }

        $rideIds = array_column($simulatorService->activeRides(), 'id');
        $total = max(1, (int) $this->option('count'));
        $concurrency = max(1, (int) $this->option('concurrency'));
        $url = rtrim($this->option('base-url'), '/').'/api/ride-scans';

        $results = ['success' => 0, 'failed' => 0];
        $startedAt = microtime(true);

        for ($sent = 0; $sent < $total; $sent += $concurrency) {
            $batchSize = min($concurrency, $total - $sent);
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn () => $pool->withHeaders($this->authHeaders())->acceptJson()->timeout(10)->post($url, [
                    'booking_reference' => $references[array_rand($references)],
                    'attraction_id' => $rideIds[array_rand($rideIds)],
                ]),
                range(1, $batchSize),
            ));

            foreach ($responses as $response) {
                $this->isSuccess($response) ? $results['success']++ : $results['failed']++;
            }
        }

        $seconds = max(0.001, microtime(true) - $startedAt);
        $this->info(sprintf('Sent %d ride scans in %.1f s (%.0f requests per second).', $total, $seconds, $total / $seconds));
        $this->info("Success: {$results['success']}, failed: {$results['failed']}.");

        return self::SUCCESS;
    }

    private function authHeaders(): array
    {
        $token = $this->option('token');

        return $token
            ? ['Authorization' => "Bearer {$token}"]
            : ['X-Local-User' => 'local-admin-1', 'X-Local-Groups' => 'admins'];
    }

    // A pool item is a Response, or an exception when the connection failed.
    private function isSuccess(Response|Throwable $response): bool
    {
        return $response instanceof Response && $response->successful();
    }
}
