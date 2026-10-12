<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Tests for the parts that are the same in every service: health, auth modes and error format.
class HealthAndAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_route_is_available_for_kubernetes_probes(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_health_shows_service_and_database_status(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertExactJson(['service' => 'event-service', 'status' => 'ok', 'db' => 'ok']);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_invalid_token_in_cognito_mode_returns_401(): void
    {
        config([
            'wonderland.auth_mode' => 'cognito',
            'wonderland.cognito.region' => 'ap-south-1',
            'wonderland.cognito.user_pool_id' => 'ap-south-1_TEST',
        ]);
        Http::fake(['*' => Http::response(['keys' => []])]);

        $this->postJson('/api/checkins', [], ['Authorization' => 'Bearer not-a-real-token'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_local_headers_are_ignored_in_cognito_mode(): void
    {
        config(['wonderland.auth_mode' => 'cognito']);

        $this->postJson('/api/checkins', [], $this->adminHeaders())
            ->assertUnauthorized();
    }
}
