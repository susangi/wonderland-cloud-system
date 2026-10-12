<?php

namespace Database\Seeders;

use App\Models\TicketTier;
use Illuminate\Database\Seeder;

// Tiers and prices from the assignment brief (design 5.2).
// firstOrCreate makes it safe to run on every deploy. It never overwrites a price an admin changed.
class TicketTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['code' => 'INDIVIDUAL', 'name' => 'Individual', 'min_guests' => 1, 'max_guests' => 1, 'price' => 40.00],
            ['code' => 'COUPLE', 'name' => 'Couple', 'min_guests' => 2, 'max_guests' => 2, 'price' => 75.00],
            ['code' => 'FAMILY', 'name' => 'Family', 'min_guests' => 1, 'max_guests' => 4, 'price' => 140.00],
            ['code' => 'GROUP', 'name' => 'Group', 'min_guests' => 1, 'max_guests' => 5, 'price' => 165.00],
        ];

        foreach ($tiers as $tier) {
            TicketTier::firstOrCreate(['code' => $tier['code']], $tier + ['currency' => 'USD', 'is_active' => true]);
        }
    }
}
