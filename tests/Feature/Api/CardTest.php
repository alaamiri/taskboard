<?php

namespace Tests\Feature\Api;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Board $board;
    private Column $column;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->board = Board::factory()->create(['user_id' => $this->user->id]);
        $this->column = Column::factory()->create(['board_id' => $this->board->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE - Créer une carte
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_create_card(): void
    {
        $response = $this->postJson("/api/columns/{$this->column->id}/cards", [
            'title' => 'Ma carte',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_create_card_in_own_board(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/columns/{$this->column->id}/cards", [
            'title' => 'Ma carte',
            'description' => 'Description de la carte',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'position', 'column_id', 'created_at', 'updated_at']
            ])
            ->assertJsonPath('data.title', 'Ma carte')
            ->assertJsonPath('data.position', 0);

        $this->assertDatabaseHas('cards', [
            'title' => 'Ma carte',
            'column_id' => $this->column->id,
        ]);
    }

    public function test_card_position_increments_automatically(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/columns/{$this->column->id}/cards", ['title' => 'Card 1']);
        $this->postJson("/api/columns/{$this->column->id}/cards", ['title' => 'Card 2']);
        $response = $this->postJson("/api/columns/{$this->column->id}/cards", ['title' => 'Card 3']);

        $response->assertJsonPath('data.position', 2);
    }

    public function test_user_cannot_create_card_in_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherBoard = Board::factory()->create(['user_id' => $otherUser->id]);
        $otherColumn = Column::factory()->create(['board_id' => $otherBoard->id]);

        $response = $this->postJson("/api/columns/{$otherColumn->id}/cards", [
            'title' => 'Ma carte',
        ]);

        $response->assertStatus(403);
    }

    public function test_create_card_requires_title(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/columns/{$this->column->id}/cards", [
            'description' => 'Description sans titre',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE - Modifier une carte
    |--------------------------------------------------------------------------
    */

    public function test_user_can_update_card_in_own_board(): void
    {
        Sanctum::actingAs($this->user);

        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $response = $this->putJson("/api/cards/{$card->id}", [
            'title' => 'Nouveau titre',
            'description' => 'Nouvelle description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Nouveau titre')
            ->assertJsonPath('data.description', 'Nouvelle description');
    }

    public function test_user_cannot_update_card_in_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherBoard = Board::factory()->create(['user_id' => $otherUser->id]);
        $otherColumn = Column::factory()->create(['board_id' => $otherBoard->id]);
        $card = Card::factory()->create(['column_id' => $otherColumn->id]);

        $response = $this->putJson("/api/cards/{$card->id}", [
            'title' => 'Nouveau titre',
        ]);

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY - Supprimer une carte
    |--------------------------------------------------------------------------
    */

    public function test_user_can_delete_card_in_own_board(): void
    {
        Sanctum::actingAs($this->user);

        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $response = $this->deleteJson("/api/cards/{$card->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('cards', [
            'id' => $card->id,
        ]);
    }

    public function test_user_cannot_delete_card_in_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherBoard = Board::factory()->create(['user_id' => $otherUser->id]);
        $otherColumn = Column::factory()->create(['board_id' => $otherBoard->id]);
        $card = Card::factory()->create(['column_id' => $otherColumn->id]);

        $response = $this->deleteJson("/api/cards/{$card->id}");

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | MOVE - Déplacer une carte
    |--------------------------------------------------------------------------
    */

    public function test_user_can_move_card_to_another_column(): void
    {
        Sanctum::actingAs($this->user);

        $column2 = Column::factory()->create(['board_id' => $this->board->id]);
        $card = Card::factory()->create(['column_id' => $this->column->id, 'position' => 0]);

        $response = $this->patchJson("/api/cards/{$card->id}/move", [
            'column_id' => $column2->id,
            'position' => 0,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.column_id', $column2->id)
            ->assertJsonPath('data.position', 0);
    }

    public function test_user_can_move_card_within_same_column(): void
    {
        Sanctum::actingAs($this->user);

        $card = Card::factory()->create(['column_id' => $this->column->id, 'position' => 0]);

        $response = $this->patchJson("/api/cards/{$card->id}/move", [
            'column_id' => $this->column->id,
            'position' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.position', 2);
    }

    public function test_user_cannot_move_card_to_another_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherBoard = Board::factory()->create(['user_id' => $this->user->id]);
        $otherColumn = Column::factory()->create(['board_id' => $otherBoard->id]);
        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $response = $this->patchJson("/api/cards/{$card->id}/move", [
            'column_id' => $otherColumn->id,
            'position' => 0,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_move_card_in_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherBoard = Board::factory()->create(['user_id' => $otherUser->id]);
        $otherColumn = Column::factory()->create(['board_id' => $otherBoard->id]);
        $card = Card::factory()->create(['column_id' => $otherColumn->id]);

        $response = $this->patchJson("/api/cards/{$card->id}/move", [
            'column_id' => $otherColumn->id,
            'position' => 0,
        ]);

        $response->assertStatus(403);
    }

    public function test_move_card_requires_column_id(): void
    {
        Sanctum::actingAs($this->user);

        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $response = $this->patchJson("/api/cards/{$card->id}/move", [
            'position' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['column_id']);
    }

    public function test_move_card_requires_position(): void
    {
        Sanctum::actingAs($this->user);

        $card = Card::factory()->create(['column_id' => $this->column->id]);

        $response = $this->patchJson("/api/cards/{$card->id}/move", [
            'column_id' => $this->column->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['position']);
    }
}
