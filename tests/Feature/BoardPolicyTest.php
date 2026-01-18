<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un utilisateur peut voir son propre board
     */
    public function test_user_can_view_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('view', $board));
    }

    /**
     * Un utilisateur ne peut pas voir le board d'un autre
     */
    public function test_user_cannot_view_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('view', $board));
    }

    /**
     * Un utilisateur peut créer un board
     */
    public function test_user_can_create_board(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', Board::class));
    }

    /**
     * Un utilisateur peut modifier son propre board
     */
    public function test_user_can_update_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $board));
    }

    /**
     * Un utilisateur ne peut pas modifier le board d'un autre
     */
    public function test_user_cannot_update_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('update', $board));
    }

    /**
     * Un utilisateur peut supprimer son propre board
     */
    public function test_user_can_delete_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('delete', $board));
    }

    /**
     * Un utilisateur ne peut pas supprimer le board d'un autre
     */
    public function test_user_cannot_delete_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($user->can('delete', $board));
    }

    /**
     * Test via HTTP : accès refusé au board d'un autre
     */
    public function test_http_access_denied_to_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get("/boards/{$board->id}");

        $response->assertStatus(403);
    }

    /**
     * Test via HTTP : accès autorisé à son propre board
     */
    public function test_http_access_allowed_to_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/boards/{$board->id}");

        $response->assertStatus(200);
    }
}
