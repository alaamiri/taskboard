<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;  // Recrée la DB avant chaque test

    /**
     * Un utilisateur non connecté ne peut pas voir les boards
     */
    public function test_guest_cannot_access_boards(): void
    {
        $response = $this->get('/boards');

        $response->assertRedirect('/login');
    }

    /**
     * Un utilisateur connecté peut voir ses boards
     */
    public function test_user_can_view_boards(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/boards');

        $response->assertStatus(200);
    }

    /**
     * Un utilisateur peut créer un board
     */
    public function test_user_can_create_board(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/boards', [
            'name' => 'Mon projet',
            'description' => 'Description du projet'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('boards', [
            'name' => 'Mon projet',
            'user_id' => $user->id
        ]);
    }

    /**
     * Le nom du board est obligatoire
     */
    public function test_board_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/boards', [
            'name' => '',
            'description' => 'Description'
        ]);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Un utilisateur peut voir son propre board
     */
    public function test_user_can_view_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/boards/{$board->id}");

        $response->assertStatus(200);
    }

    /**
     * Un utilisateur ne peut pas voir le board d'un autre
     */
    public function test_user_cannot_view_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/boards/{$board->id}");

        $response->assertStatus(403);
    }

    /**
     * Un utilisateur peut modifier son board
     */
    public function test_user_can_update_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put("/boards/{$board->id}", [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('boards', [
            'id' => $board->id,
            'name' => 'Nouveau nom'
        ]);
    }

    /**
     * Un utilisateur peut supprimer son board
     */
    public function test_user_can_delete_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/boards/{$board->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
    }
}
