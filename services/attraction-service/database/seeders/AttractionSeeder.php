<?php

namespace Database\Seeders;

use App\Models\Attraction;
use Illuminate\Database\Seeder;

// Starting rides for the park (design 5.1). Values match the sample data in design/Screens.md section 2.
// The Kubernetes migrate Job runs "migrate --seed" on every deploy, so we use firstOrCreate.
// It adds missing rides but never overwrites changes an admin made.
class AttractionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->attractions() as $attraction) {
            Attraction::firstOrCreate(['name' => $attraction['name']], $attraction);
        }
    }

    private function attractions(): array
    {
        return [
            [
                'name' => 'Thunder Coaster',
                'description' => 'Our tallest and fastest rollercoaster with a 40 metre drop and three loops.',
                'category' => 'rollercoaster',
                'thrill_level' => 'extreme',
                'min_age' => 14,
                'min_height_cm' => 140,
            ],
            [
                'name' => 'Dragon Loop',
                'description' => 'A twisting coaster that flies through the dragon\'s mouth and around a full loop.',
                'category' => 'rollercoaster',
                'thrill_level' => 'medium',
                'min_age' => 8,
                'min_height_cm' => 120,
            ],
            [
                'name' => 'Splash Mountain',
                'description' => 'A log flume ride that ends with a big splash down the mountain. You will get wet.',
                'category' => 'water',
                'thrill_level' => 'high',
                'min_age' => 10,
                'min_height_cm' => 120,
            ],
            [
                'name' => 'Lazy River',
                'description' => 'Relax on a tube and float slowly around the park. Good for the whole family.',
                'category' => 'water',
                'thrill_level' => 'low',
                'min_age' => 0,
                'min_height_cm' => 0,
            ],
            [
                'name' => 'Teacup Twirl',
                'description' => 'Spinning teacups for little ones. Turn the wheel to spin faster.',
                'category' => 'kids',
                'thrill_level' => 'low',
                'min_age' => 3,
                'min_height_cm' => 90,
            ],
            [
                'name' => 'Laser Maze',
                'description' => 'Move through a maze of laser beams without touching them. Beat the clock.',
                'category' => 'interactive',
                'thrill_level' => 'medium',
                'min_age' => 7,
                'min_height_cm' => 0,
            ],
            [
                'name' => 'Pirate Ship',
                'description' => 'A giant swinging pirate ship that goes higher with every swing.',
                'category' => 'rollercoaster',
                'thrill_level' => 'high',
                'min_age' => 10,
                'min_height_cm' => 130,
            ],
            [
                'name' => 'Bumper Cars',
                'description' => 'Drive your own bumper car and bump into friends and family.',
                'category' => 'interactive',
                'thrill_level' => 'medium',
                'min_age' => 6,
                'min_height_cm' => 0,
            ],
        ];
    }
}
