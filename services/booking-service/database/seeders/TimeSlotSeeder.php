<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Seeder;

// Four daily slots, 200 guests each (assumption A6).
// firstOrCreate makes it safe to run on every deploy.
class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['label' => 'Morning 09:00–12:00', 'start_time' => '09:00:00', 'end_time' => '12:00:00'],
            ['label' => 'Midday 12:00–15:00', 'start_time' => '12:00:00', 'end_time' => '15:00:00'],
            ['label' => 'Afternoon 15:00–18:00', 'start_time' => '15:00:00', 'end_time' => '18:00:00'],
            ['label' => 'Evening 18:00–21:00', 'start_time' => '18:00:00', 'end_time' => '21:00:00'],
        ];

        foreach ($slots as $slot) {
            TimeSlot::firstOrCreate(['label' => $slot['label']], $slot + ['capacity' => 200, 'is_active' => true]);
        }
    }
}
