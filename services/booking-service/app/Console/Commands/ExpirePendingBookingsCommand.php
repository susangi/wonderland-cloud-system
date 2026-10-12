<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

// Marks unpaid bookings older than 15 minutes as EXPIRED, so their seats go back to other visitors (design 7.4).
// Runs every 5 minutes: Kubernetes CronJob in the cloud, "php artisan schedule:work" on the laptop.
class ExpirePendingBookingsCommand extends Command
{
    protected $signature = 'bookings:expire-pending';

    protected $description = 'Expire unpaid bookings older than 15 minutes';

    public function handle(BookingService $bookingService): int
    {
        $expiredCount = $bookingService->expireUnpaidBookings();

        $this->info("Expired {$expiredCount} unpaid booking(s).");

        return self::SUCCESS;
    }
}
