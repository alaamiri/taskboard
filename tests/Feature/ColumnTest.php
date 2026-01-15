<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColumnTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un utilisateur peut créer une colonne dans son board
     */
    public function test_user_can_create_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/boards/{$board->id}/columns", [
            'name' => 'To Do'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('columns', [
            'name' => 'To Do',
            'board_id' => $board->id,
            'position' => 0
        ]);
    }

    /**
     * Le nom de la colonne est obligatoire
     */
    public function test_column_name_is_required(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/boards/{$board->id}/columns", [
            'name' => ''
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Un utilisateur ne peut pas créer une colonne dans le board d'un autre
     */
    public function test_user_cannot_create_column_in_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->post("/boards/{$board->id}/columns", [
            'name' => 'To Do'
        ]);

        $response->assertStatus(403);
    }

    /**
     * Un utilisateur peut modifier sa colonne
     */
    public function test_user_can_update_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user)->put("/columns/{$column->id}", [
            'name' => 'In Progress'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('columns', [
            'id' => $column->id,
            'name' => 'In Progress'
        ]);
    }

    /**
     * Un utilisateur ne peut pas modifier la colonne d'un autre
     */
    public function test_user_cannot_update_others_column(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user)->put("/columns/{$column->id}", [
            'name' => 'Hacked'
        ]);

        $response->assertStatus(403);
    }

    /**
     * Un utilisateur peut supprimer sa colonne
     */
    public function test_user_can_delete_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user)->delete("/columns/{$column->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('columns', ['id' => $column->id]);
    }

    /**
     * La position s'incrémente automatiquement
     */
    public function test_column_position_auto_increments(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post("/boards/{$board->id}/columns", ['name' => 'To Do']);
        $this->actingAs($user)->post("/boards/{$board->id}/columns", ['name' => 'In Progress']);
        $this->actingAs($user)->post("/boards/{$board->id}/columns", ['name' => 'Done']);

        $this->assertDatabaseHas('columns', ['name' => 'To Do', 'position' => 0]);
        $this->assertDatabaseHas('columns', ['name' => 'In Progress', 'position' => 1]);
        $this->assertDatabaseHas('columns', ['name' => 'Done', 'position' => 2]);
    }
}
