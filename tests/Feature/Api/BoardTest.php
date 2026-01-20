<?php

namespace Tests\Feature\Api;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BoardTest extends ApiTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole('viewer');
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX - Liste des boards
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_list_boards(): void
    {
        $response = $this->getJson('/api/boards');

        $response->assertStatus(401);
    }

    public function test_user_can_list_own_boards(): void
    {
        Sanctum::actingAs($this->user);

        Board::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/boards');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description', 'created_at', 'updated_at']
                ]
            ]);
    }

    public function test_user_cannot_see_others_boards_in_list(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        Board::factory()->count(2)->create(['user_id' => $this->user->id]);
        Board::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/boards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | STORE - Créer un board
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_create_board(): void
    {
        $response = $this->postJson('/api/boards', [
            'name' => 'Mon Board',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_create_board(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/boards', [
            'name' => 'Mon Board',
            'description' => 'Description du board',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'created_at', 'updated_at']
            ])
            ->assertJsonPath('data.name', 'Mon Board')
            ->assertJsonPath('data.description', 'Description du board');

        $this->assertDatabaseHas('boards', [
            'name' => 'Mon Board',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_create_board_without_description(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/boards', [
            'name' => 'Mon Board',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Mon Board')
            ->assertJsonPath('data.description', null);
    }

    public function test_create_board_requires_name(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/boards', [
            'description' => 'Description sans nom',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_board_name_max_length(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/boards', [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW - Afficher un board
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_view_board(): void
    {
        $board = Board::factory()->create();

        $response = $this->getJson("/api/boards/{$board->id}");

        $response->assertStatus(401);
    }

    public function test_user_can_view_own_board(): void
    {
        Sanctum::actingAs($this->user);

        $board = Board::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/boards/{$board->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'description', 'columns', 'created_at', 'updated_at']
            ])
            ->assertJsonPath('data.id', $board->id);
    }

    public function test_user_cannot_view_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/boards/{$board->id}");

        $response->assertStatus(403);
    }

    public function test_view_nonexistent_board_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/boards/99999');

        $response->assertStatus(404);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE - Modifier un board
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_update_board(): void
    {
        $board = Board::factory()->create();

        $response = $this->putJson("/api/boards/{$board->id}", [
            'name' => 'Nouveau nom',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_update_own_board(): void
    {
        Sanctum::actingAs($this->user);

        $board = Board::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/boards/{$board->id}", [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nouveau nom')
            ->assertJsonPath('data.description', 'Nouvelle description');

        $this->assertDatabaseHas('boards', [
            'id' => $board->id,
            'name' => 'Nouveau nom',
        ]);
    }

    public function test_user_can_update_board_partially(): void
    {
        Sanctum::actingAs($this->user);

        $board = Board::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Ancien nom',
            'description' => 'Ancienne description',
        ]);

        $response = $this->putJson("/api/boards/{$board->id}", [
            'name' => 'Nouveau nom',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nouveau nom');
    }

    public function test_user_cannot_update_others_board(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/boards/{$board->id}", [
            'name' => 'Nouveau nom',
        ]);

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY - Supprimer un board
    |--------------------------------------------------------------------------
    */

    public function test_guest_cannot_delete_board(): void
    {
        $board = Board::factory()->create();

        $response = $this->deleteJson("/api/boards/{$board->id}");

        $response->assertStatus(401);
    }

    public function test_user_can_delete_own_board(): void
    {
        // Utilise un admin car viewer ne peut pas supprimer
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Sanctum::actingAs($admin);

        $board = Board::factory()->create(['user_id' => $admin->id]);

        $response = $this->deleteJson("/api/boards/{$board->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('boards', [
            'id' => $board->id,
        ]);
    }

    public function test_user_cannot_delete_others_board(): void
    {
        // Même un admin ne peut pas supprimer le board d'un autre (selon ta logique)
        // Ou alors on teste qu'un viewer ne peut pas supprimer
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/boards/{$board->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('boards', [
            'id' => $board->id,
        ]);
    }
}
