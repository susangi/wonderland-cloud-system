<?php

namespace Tests\Feature;

use App\Models\TicketTier;
use Database\Seeders\TicketTierSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTierApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TicketTierSeeder::class);
    }

    private function family(): TicketTier
    {
        return TicketTier::where('code', 'FAMILY')->first();
    }

    private function validUpdate(array $changes = []): array
    {
        return array_merge(['name' => 'Family', 'min_guests' => 1, 'max_guests' => 4, 'price' => 150, 'is_active' => true], $changes);
    }

    public function test_anyone_can_list_active_tiers_from_the_brief(): void
    {
        $this->getJson('/api/tiers')
            ->assertOk()
            ->assertJsonCount(4)
            ->assertJsonPath('0.code', 'INDIVIDUAL')
            ->assertJsonPath('0.price', 40)
            ->assertJsonFragment(['code' => 'FAMILY', 'min_guests' => 1, 'max_guests' => 4, 'price' => 140, 'currency' => 'USD']);
    }

    public function test_inactive_tier_is_hidden_from_visitors_but_admin_can_see_it(): void
    {
        TicketTier::where('code', 'GROUP')->update(['is_active' => false]);

        $this->getJson('/api/tiers')->assertJsonCount(3);
        $this->getJson('/api/tiers?include_inactive=1', $this->visitorHeaders())->assertJsonCount(3);
        $this->getJson('/api/tiers?include_inactive=1', $this->adminHeaders())->assertJsonCount(4);
    }

    public function test_anyone_can_view_one_tier(): void
    {
        $this->getJson("/api/tiers/{$this->family()->id}")
            ->assertOk()
            ->assertJsonPath('code', 'FAMILY')
            ->assertJsonPath('is_active', true);
    }

    public function test_unknown_tier_returns_404(): void
    {
        $this->getJson('/api/tiers/999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'TIER_NOT_FOUND');
    }

    public function test_admin_can_update_price(): void
    {
        $this->putJson("/api/tiers/{$this->family()->id}", $this->validUpdate(['price' => 150]), $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('price', 150);

        $this->assertDatabaseHas('ticket_tiers', ['code' => 'FAMILY', 'price' => 150]);
    }

    public function test_admin_can_deactivate_tier(): void
    {
        $this->putJson("/api/tiers/{$this->family()->id}", $this->validUpdate(['is_active' => false]), $this->adminHeaders())
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->getJson('/api/tiers')->assertJsonMissing(['code' => 'FAMILY']);
    }

    public function test_max_guests_below_min_guests_returns_422(): void
    {
        $this->putJson("/api/tiers/{$this->family()->id}", $this->validUpdate(['min_guests' => 4, 'max_guests' => 2]), $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors('max_guests');
    }

    public function test_invalid_guests_and_price_return_422(): void
    {
        $invalid = $this->validUpdate(['min_guests' => 0, 'max_guests' => 11, 'price' => 0]);

        $this->putJson("/api/tiers/{$this->family()->id}", $invalid, $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_guests', 'max_guests', 'price']);
    }

    public function test_visitor_cannot_update_price(): void
    {
        $this->putJson("/api/tiers/{$this->family()->id}", $this->validUpdate(['price' => 1]), $this->visitorHeaders())
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN');

        $this->assertDatabaseHas('ticket_tiers', ['code' => 'FAMILY', 'price' => 140]);
    }

    public function test_update_without_login_returns_401(): void
    {
        $this->putJson("/api/tiers/{$this->family()->id}", $this->validUpdate())
            ->assertUnauthorized();
    }

    public function test_admin_can_create_tier_with_upper_case_code(): void
    {
        $newTier = ['code' => 'school', 'name' => 'School Trip', 'min_guests' => 5, 'max_guests' => 10, 'price' => 250];

        $this->postJson('/api/tiers', $newTier, $this->adminHeaders())
            ->assertCreated()
            ->assertJsonPath('code', 'SCHOOL')
            ->assertJsonPath('currency', 'USD')
            ->assertJsonPath('is_active', true);
    }

    public function test_duplicate_tier_code_returns_422(): void
    {
        $duplicate = ['code' => 'FAMILY', 'name' => 'Family 2', 'min_guests' => 1, 'max_guests' => 4, 'price' => 100];

        $this->postJson('/api/tiers', $duplicate, $this->adminHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_seeder_can_run_again_without_overwriting_prices(): void
    {
        $this->family()->update(['price' => 199]);

        $this->seed(TicketTierSeeder::class);

        $this->assertSame(4, TicketTier::count());
        $this->assertDatabaseHas('ticket_tiers', ['code' => 'FAMILY', 'price' => 199]);
    }
}
