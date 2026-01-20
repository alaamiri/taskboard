<?php

namespace Tests\Feature\Api;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ColumnTest extends ApiTestCase
{
    private User $user;
    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->assignRole('viewer');
        $this->board = Board::factory()->create(['user_id' => $this->user->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE - Créer une colonne
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_create_column(): void
    {
        $response = $this->postJson("/api/boards/{$this->board->id}/columns", [
            'name' => 'To Do',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_create_column_in_own_board(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/boards/{$this->board->id}/columns", [
            'name' => 'To Do',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'position', 'board_id', 'created_at', 'updated_at']
            ])
            ->assertJsonPath('data.name', 'To Do')
            ->assertJsonPath('data.position', 0);

        $this->assertDatabaseHas('columns', [
            'name' => 'To Do',
            'board_id' => $this->board->id,
        ]);
    }

    public function test_column_position_increments_automatically(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/boards/{$this->board->id}/columns", ['name' => 'To Do']);
        $this->postJson("/api/boards/{$this->board->id}/columns", ['name' => 'In Progress']);
        $response = $this->postJson("/api/boards/{$this->board->id}/columns", ['name' => 'Done']);

        $response->assertJsonPath('data.position', 2);
    }

    public function test_user_cannot_create_column_in_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherBoard = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson("/api/boards/{$otherBoard->id}/columns", [
            'name' => 'To Do',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_column_requires_name(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/boards/{$this->board->id}/columns", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE - Modifier une colonne
    |--------------------------------------------------------------------------
    */

    public function test_user_can_update_column_in_own_board(): void
    {
        Sanctum::actingAs($this->user);

        $column = Column::factory()->create(['board_id' => $this->board->id]);

        $response = $this->putJson("/api/columns/{$column->id}", [
            'name' => 'Nouveau nom',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nouveau nom');
    }

    public function test_user_can_update_column_position(): void
    {
        Sanctum::actingAs($this->user);

        $column = Column::factory()->create([
            'board_id' => $this->board->id,
            'position' => 0,
        ]);

        $response = $this->putJson("/api/columns/{$column->id}", [
            'name' => $column->name,  // Ajoute le name car required dans le DTO
            'position' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.position', 2);
    }

    public function test_user_cannot_update_column_in_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherBoard = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $otherBoard->id]);

        $response = $this->putJson("/api/columns/{$column->id}", [
            'name' => 'Nouveau nom',
        ]);

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY - Supprimer une colonne
    |--------------------------------------------------------------------------
    */

    public function test_user_can_delete_column_in_own_board(): void
    {
        // Utilise un admin car viewer ne peut pas supprimer
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $board = Board::factory()->create(['user_id' => $admin->id]);

        Sanctum::actingAs($admin);

        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->deleteJson("/api/columns/{$column->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('columns', [
            'id' => $column->id,
        ]);
    }

    public function test_user_cannot_delete_column_in_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherBoard = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $otherBoard->id]);

        $response = $this->deleteJson("/api/columns/{$column->id}");

        $response->assertStatus(403);
    }

    public function test_deleting_column_deletes_its_cards(): void
    {
        // Utilise un admin car viewer ne peut pas supprimer
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $board = Board::factory()->create(['user_id' => $admin->id]);

        Sanctum::actingAs($admin);

        $column = Column::factory()->create(['board_id' => $board->id]);
        $column->cards()->createMany([
            ['title' => 'Card 1', 'position' => 0],
            ['title' => 'Card 2', 'position' => 1],
        ]);

        $this->assertDatabaseCount('cards', 2);

        $this->deleteJson("/api/columns/{$column->id}");

        $this->assertDatabaseCount('cards', 0);
    }
}
