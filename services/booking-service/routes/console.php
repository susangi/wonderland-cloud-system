<?php

use Illuminate\Support\Facades\Schedule;

// Laptop only: "php artisan schedule:work" runs these (docs/05).
// In Kubernetes the same two commands run as CronJobs instead (design 7.4).
Schedule::command('outbox:flush')->everyMinute()->withoutOverlapping();
Schedule::command('bookings:expire-pending')->everyFiveMinutes()->withoutOverlapping();
