<?php

namespace Tests\Feature\Audit;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Board Audit Logging
    |--------------------------------------------------------------------------
    */

    public function test_board_creation_is_logged(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/boards', [
            'name' => 'New Project',
            'description' => 'Project description',
        ]);

        $response->assertStatus(201);

        $activity = Activity::where('subject_type', Board::class)
            ->where('description', 'like', '%created%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($user->id, $activity->causer_id);
    }

    public function test_board_update_is_logged(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $response = $this->putJson("/api/boards/{$board->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);

        $activity = Activity::where('subject_type', Board::class)
            ->where('subject_id', $board->id)
            ->where('description', 'like', '%updated%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($user->id, $activity->causer_id);
    }

    public function test_board_deletion_is_logged(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $boardId = $board->id;

        $response = $this->deleteJson("/api/boards/{$board->id}");

        $response->assertStatus(204);

        $activity = Activity::where('subject_type', Board::class)
            ->where('subject_id', $boardId)
            ->where('description', 'like', '%deleted%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
    }

    /*
    |--------------------------------------------------------------------------
    | Column Audit Logging
    |--------------------------------------------------------------------------
    */

    public function test_column_creation_is_logged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson("/api/boards/{$board->id}/columns", [
            'name' => 'To Do',
        ]);

        $response->assertStatus(201);

        $activity = Activity::where('subject_type', Column::class)
            ->where('description', 'like', '%created%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_column_update_is_logged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->putJson("/api/columns/{$column->id}", [
            'name' => 'Updated Column',
        ]);

        $response->assertStatus(200);

        $activity = Activity::where('subject_type', Column::class)
            ->where('subject_id', $column->id)
            ->where('description', 'like', '%updated%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
    }

    /*
    |--------------------------------------------------------------------------
    | Card Audit Logging
    |--------------------------------------------------------------------------
    */

    public function test_card_creation_is_logged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->postJson("/api/columns/{$column->id}/cards", [
            'title' => 'New Task',
        ]);

        $response->assertStatus(201);

        $activity = Activity::where('subject_type', Card::class)
            ->where('description', 'like', '%created%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_card_update_is_logged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $response = $this->putJson("/api/cards/{$card->id}", [
            'title' => 'Updated Task',
        ]);

        $response->assertStatus(200);

        $activity = Activity::where('subject_type', Card::class)
            ->where('subject_id', $card->id)
            ->where('description', 'like', '%updated%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_card_deletion_is_logged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $cardId = $card->id;

        $response = $this->deleteJson("/api/cards/{$card->id}");

        $response->assertStatus(204);

        $activity = Activity::where('subject_type', Card::class)
            ->where('subject_id', $cardId)
            ->where('description', 'like', '%deleted%')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
    }
}
