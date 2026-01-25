<?php

namespace Tests\Unit\Repositories;

use App\Exceptions\Board\BoardNotFoundException;
use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use App\Repositories\Eloquent\BoardRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BoardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private BoardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->repository = new BoardRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | findById
    |--------------------------------------------------------------------------
    */

    public function test_find_by_id_returns_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $result = $this->repository->findById($board->id);

        $this->assertNotNull($result);
        $this->assertEquals($board->id, $result->id);
    }

    public function test_find_by_id_returns_null_for_non_existent(): void
    {
        $result = $this->repository->findById(9999);

        $this->assertNull($result);
    }

    public function test_find_by_id_uses_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        // First call - should cache
        $this->repository->findById($board->id);

        // Verify cache was set
        $this->assertTrue(Cache::tags(['boards', "board.{$board->id}"])->has("board.{$board->id}"));
    }

    /*
    |--------------------------------------------------------------------------
    | findByIdWithRelations
    |--------------------------------------------------------------------------
    */

    public function test_find_by_id_with_relations_loads_columns_and_cards(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $column->cards()->create(['title' => 'Card 1', 'position' => 0]);

        $result = $this->repository->findByIdWithRelations($board->id);

        $this->assertTrue($result->relationLoaded('columns'));
        $this->assertTrue($result->relationLoaded('user'));
        $this->assertCount(1, $result->columns);
        $this->assertCount(1, $result->columns->first()->cards);
    }

    public function test_find_by_id_with_relations_throws_exception_for_non_existent(): void
    {
        $this->expectException(BoardNotFoundException::class);

        $this->repository->findByIdWithRelations(9999);
    }

    /*
    |--------------------------------------------------------------------------
    | Cache invalidation
    |--------------------------------------------------------------------------
    */

    public function test_create_invalidates_user_boards_cache(): void
    {
        $user = User::factory()->create();

        // Prime the cache
        $this->repository->getAllForUser($user);
        $this->assertTrue(Cache::tags(['boards', "user.{$user->id}.boards"])->has("user.{$user->id}.boards"));

        // Create a new board
        $this->repository->create([
            'name' => 'New Board',
            'description' => null,
            'user_id' => $user->id,
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::tags(["user.{$user->id}.boards"])->has("user.{$user->id}.boards"));
    }

    public function test_update_invalidates_board_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        // Prime the cache
        $this->repository->findById($board->id);
        $this->assertTrue(Cache::tags(['boards', "board.{$board->id}"])->has("board.{$board->id}"));

        // Update the board
        $this->repository->update($board, ['name' => 'Updated Name']);

        // Board-specific cache should be invalidated
        $this->assertFalse(Cache::tags(["board.{$board->id}"])->has("board.{$board->id}"));
    }

    public function test_delete_invalidates_caches(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        // Prime caches
        $this->repository->findById($board->id);
        $this->repository->getAllForUser($user);

        $boardId = $board->id;

        // Delete the board
        $this->repository->delete($board);

        // Both caches should be invalidated
        $this->assertFalse(Cache::tags(["board.{$boardId}"])->has("board.{$boardId}"));
        $this->assertFalse(Cache::tags(["user.{$user->id}.boards"])->has("user.{$user->id}.boards"));
    }
}
