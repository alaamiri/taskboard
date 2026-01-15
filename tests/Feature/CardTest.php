<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un utilisateur peut créer une carte dans sa colonne
     */
    public function test_user_can_create_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user)->post("/columns/{$column->id}/cards", [
            'title' => 'Ma première tâche',
            'description' => 'Description de la tâche'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cards', [
            'title' => 'Ma première tâche',
            'column_id' => $column->id
        ]);
    }

    /**
     * Le titre de la carte est obligatoire
     */
    public function test_card_title_is_required(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user)->post("/columns/{$column->id}/cards", [
            'title' => '',
            'description' => 'Description'
        ]);

        $response->assertSessionHasErrors('title');
    }

    /**
     * Un utilisateur ne peut pas créer une carte dans la colonne d'un autre
     */
    public function test_user_cannot_create_card_in_others_column(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user)->post("/columns/{$column->id}/cards", [
            'title' => 'Hack',
            'description' => 'Tentative de hack'
        ]);

        $response->assertStatus(403);
    }

    /**
     * Un utilisateur peut modifier sa carte
     */
    public function test_user_can_update_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $response = $this->actingAs($user)->put("/cards/{$card->id}", [
            'title' => 'Titre modifié',
            'description' => 'Nouvelle description'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'title' => 'Titre modifié'
        ]);
    }

    /**
     * Un utilisateur ne peut pas modifier la carte d'un autre
     */
    public function test_user_cannot_update_others_card(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $response = $this->actingAs($user)->put("/cards/{$card->id}", [
            'title' => 'Hacked'
        ]);

        $response->assertStatus(403);
    }

    /**
     * Un utilisateur peut supprimer sa carte
     */
    public function test_user_can_delete_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $response = $this->actingAs($user)->delete("/cards/{$card->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    /**
     * Un utilisateur peut déplacer une carte vers une autre colonne
     */
    public function test_user_can_move_card_to_another_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column1 = Column::factory()->create(['board_id' => $board->id]);
        $column2 = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column1->id]);

        $response = $this->actingAs($user)->patch("/cards/{$card->id}/move", [
            'column_id' => $column2->id,
            'position' => 0
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'column_id' => $column2->id,
            'position' => 0
        ]);
    }

    /**
     * Un utilisateur ne peut pas déplacer une carte vers un autre board
     */
    public function test_user_cannot_move_card_to_another_board(): void
    {
        $user = User::factory()->create();
        $board1 = Board::factory()->create(['user_id' => $user->id]);
        $board2 = Board::factory()->create(['user_id' => $user->id]);
        $column1 = Column::factory()->create(['board_id' => $board1->id]);
        $column2 = Column::factory()->create(['board_id' => $board2->id]);
        $card = Card::factory()->create(['column_id' => $column1->id]);

        $response = $this->actingAs($user)->patch("/cards/{$card->id}/move", [
            'column_id' => $column2->id,
            'position' => 0
        ]);

        $response->assertStatus(403);
    }

    /**
     * La description de la carte est optionnelle
     */
    public function test_card_description_is_optional(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $response = $this->actingAs($user)->post("/columns/{$column->id}/cards", [
            'title' => 'Tâche sans description'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cards', [
            'title' => 'Tâche sans description',
            'description' => null
        ]);
    }
}
