<?php

namespace App\Traits;

use Illuminate\Support\Str;

// Booking references look like WL-20261108-K7Q2ZP (design 5.3).
// The date part is the visit date, so gate staff can see at a glance if the ticket is for today.
trait GeneratesBookingReference
{
    protected function generateBookingReference(string $visitDate): string
    {
        $datePart = str_replace('-', '', $visitDate);
        $randomPart = strtoupper(Str::random(6));

        return "WL-{$datePart}-{$randomPart}";
    }
}
