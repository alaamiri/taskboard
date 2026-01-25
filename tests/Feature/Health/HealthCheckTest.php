<?php

namespace Tests\Feature\Health;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Basic Health Check
    |--------------------------------------------------------------------------
    */

    public function test_up_endpoint_returns_200(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | Health Check Endpoint
    |--------------------------------------------------------------------------
    */

    public function test_health_endpoint_returns_json(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/health');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_health_endpoint_contains_status(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'finishedAt',
            'checkResults',
        ]);
    }

    public function test_health_check_includes_database_status(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/health');

        $response->assertStatus(200);

        $data = $response->json();

        // Check that database check exists in results
        $checkNames = collect($data['checkResults'])->pluck('name')->toArray();

        $this->assertTrue(
            in_array('Database', $checkNames) || in_array('DatabaseCheck', $checkNames) || in_array('database', $checkNames),
            'Database health check should be included'
        );
    }

    public function test_health_check_includes_cache_status(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/health');

        $response->assertStatus(200);

        $data = $response->json();

        // Check that cache check exists in results
        $checkNames = collect($data['checkResults'])->pluck('name')->toArray();

        $this->assertTrue(
            in_array('Cache', $checkNames) || in_array('CacheCheck', $checkNames) || in_array('cache', $checkNames) ||
            in_array('Redis', $checkNames) || in_array('RedisCheck', $checkNames),
            'Cache health check should be included'
        );
    }
}
