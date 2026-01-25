<?php

namespace Tests\Feature\RateLimiting;

use App\Models\Board;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        RateLimiter::clear('api');
        RateLimiter::clear('api-write');
        RateLimiter::clear('sensitive');
    }

    /*
    |--------------------------------------------------------------------------
    | API Rate Limit (60/min for viewer, 120/min for admin)
    |--------------------------------------------------------------------------
    */

    public function test_api_rate_limit_returns_429_when_exceeded(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);

        // Make 60 requests (viewer limit)
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/boards');
        }

        // 61st request should be rate limited
        $response = $this->getJson('/api/boards');

        $response->assertStatus(429);
    }

    public function test_admin_has_higher_api_rate_limit(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);
        Sanctum::actingAs($admin);

        Board::factory()->create(['user_id' => $admin->id]);

        // Make 100 requests (more than viewer limit but less than admin limit)
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/boards');
            if ($response->status() === 429) {
                $this->fail('Admin should have higher rate limit');
            }
        }

        $this->assertTrue(true);
    }

    /*
    |--------------------------------------------------------------------------
    | API-Write Rate Limit (20/min for viewer, 60/min for admin)
    |--------------------------------------------------------------------------
    */

    public function test_api_write_rate_limit_returns_429_when_exceeded(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make 20 write requests (viewer limit)
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/boards', [
                'name' => "Board {$i}",
            ]);
        }

        // 21st request should be rate limited
        $response = $this->postJson('/api/boards', [
            'name' => 'One more board',
        ]);

        $response->assertStatus(429);
    }

    public function test_admin_has_higher_write_rate_limit(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);
        Sanctum::actingAs($admin);

        // Make 30 requests (more than viewer limit but less than admin limit)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/boards', [
                'name' => "Board {$i}",
            ]);
            if ($response->status() === 429) {
                $this->fail('Admin should have higher write rate limit');
            }
        }

        $this->assertTrue(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Sensitive Rate Limit (10/min)
    |--------------------------------------------------------------------------
    */

    public function test_sensitive_rate_limit_returns_429_when_exceeded(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);
        Sanctum::actingAs($admin);

        // Make 10 requests (sensitive limit)
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/audit-logs');
        }

        // 11th request should be rate limited
        $response = $this->getJson('/api/audit-logs');

        $response->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Headers
    |--------------------------------------------------------------------------
    */

    public function test_rate_limit_headers_are_present(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/boards');

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    public function test_rate_limit_remaining_decreases(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response1 = $this->getJson('/api/boards');
        $remaining1 = (int) $response1->headers->get('X-RateLimit-Remaining');

        $response2 = $this->getJson('/api/boards');
        $remaining2 = (int) $response2->headers->get('X-RateLimit-Remaining');

        $this->assertLessThan($remaining1, $remaining2);
    }
}
