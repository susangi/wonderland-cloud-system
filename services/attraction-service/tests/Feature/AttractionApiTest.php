<?php

namespace Tests\Feature;

use App\Models\Attraction;
use Database\Seeders\AttractionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttractionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AttractionSeeder::class);
    }

    private function validAttraction(): array
    {
        return [
            'name' => 'Space Drop',
            'description' => 'A free fall tower.',
            'category' => 'rollercoaster',
            'thrill_level' => 'extreme',
            'min_age' => 12,
            'min_height_cm' => 140,
        ];
    }

    public function test_anyone_can_list_active_attractions(): void
    {
        $response = $this->getJson('/api/attractions');

        $response->assertOk()
            ->assertJsonCount(8)
            ->assertJsonStructure([['id', 'name', 'description', 'category', 'thrill_level', 'min_age', 'min_height_cm', 'status']]);
    }

    public function test_list_can_be_filtered_by_category_and_thrill_level(): void
    {
        $response = $this->getJson('/api/attractions?category=water&thrill_level=low');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Lazy River');
    }

    public function test_invalid_filter_returns_422(): void
    {
        $this->getJson('/api/attractions?category=space')
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');
    }

    public function test_anyone_can_view_one_attraction(): void
    {
        $attraction = Attraction::where('name', 'Thunder Coaster')->first();

        $this->getJson("/api/attractions/{$attraction->id}")
            ->assertOk()
            ->assertJsonPath('name', 'Thunder Coaster')
            ->assertJsonPath('min_height_cm', 140);
    }

    public function test_unknown_attraction_returns_404_in_error_format(): void
    {
        $this->getJson('/api/attractions/9999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'ATTRACTION_NOT_FOUND');
    }

    public function test_admin_can_create_attraction(): void
    {
        $this->postJson('/api/attractions', $this->validAttraction(), $this->adminHeaders())
            ->assertCreated()
            ->assertJsonPath('name', 'Space Drop')
            ->assertJsonPath('status', 'active');

        $this->assertDatabaseHas('attractions', ['name' => 'Space Drop']);
    }

    public function test_visitor_cannot_create_attraction(): void
    {
        $this->postJson('/api/attractions', $this->validAttraction(), $this->visitorHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');

        $this->assertDatabaseMissing('attractions', ['name' => 'Space Drop']);
    }

    public function test_create_without_login_returns_401(): void
    {
        $this->postJson('/api/attractions', $this->validAttraction())
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_create_with_invalid_data_returns_422(): void
    {
        $invalid = ['name' => str_repeat('a', 101), 'category' => 'space', 'thrill_level' => 'crazy', 'min_age' => 150];

        $this->postJson('/api/attractions', $invalid, $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'category', 'thrill_level', 'min_age']);
    }

    public function test_admin_can_update_attraction(): void
    {
        $attraction = Attraction::where('name', 'Lazy River')->first();
        $changes = array_merge($this->validAttraction(), ['name' => 'Lazy River Deluxe', 'category' => 'water', 'thrill_level' => 'low']);

        $this->putJson("/api/attractions/{$attraction->id}", $changes, $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('name', 'Lazy River Deluxe');
    }

    public function test_retired_attraction_is_hidden_from_list(): void
    {
        $attraction = Attraction::where('name', 'Pirate Ship')->first();

        $this->patchJson("/api/attractions/{$attraction->id}/retire", [], $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'retired');

        $this->getJson('/api/attractions')
            ->assertOk()
            ->assertJsonCount(7)
            ->assertJsonMissing(['name' => 'Pirate Ship']);

        // Not deleted. Kept for history and analytics.
        $this->assertDatabaseHas('attractions', ['name' => 'Pirate Ship', 'status' => 'retired']);
    }

    public function test_only_admin_sees_retired_attractions_with_include_retired(): void
    {
        Attraction::where('name', 'Pirate Ship')->update(['status' => 'retired']);

        $this->getJson('/api/attractions?include_retired=1', $this->adminHeaders())->assertJsonCount(8);
        $this->getJson('/api/attractions?include_retired=1', $this->visitorHeaders())->assertJsonCount(7);
        $this->getJson('/api/attractions?include_retired=1')->assertJsonCount(7);
    }

    public function test_admin_can_activate_retired_attraction(): void
    {
        $attraction = Attraction::where('name', 'Pirate Ship')->first();
        $attraction->update(['status' => 'retired']);

        $this->patchJson("/api/attractions/{$attraction->id}/activate", [], $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $this->getJson('/api/attractions')->assertJsonCount(8);
    }

    public function test_visitor_cannot_retire_attraction(): void
    {
        $attraction = Attraction::where('name', 'Pirate Ship')->first();

        $this->patchJson("/api/attractions/{$attraction->id}/retire", [], $this->visitorHeaders())
            ->assertForbidden();
    }

    public function test_seeder_can_run_again_without_duplicates(): void
    {
        $this->seed(AttractionSeeder::class);

        $this->assertSame(8, Attraction::count());
    }
}
