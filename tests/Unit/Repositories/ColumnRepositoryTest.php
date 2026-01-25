<?php

namespace Tests\Unit\Repositories;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use App\Repositories\Eloquent\ColumnRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ColumnRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ColumnRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->repository = new ColumnRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | findById
    |--------------------------------------------------------------------------
    */

    public function test_find_by_id_returns_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $result = $this->repository->findById($column->id);

        $this->assertNotNull($result);
        $this->assertEquals($column->id, $result->id);
    }

    public function test_find_by_id_uses_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        // First call - should cache
        $this->repository->findById($column->id);

        // Verify cache was set
        $this->assertTrue(Cache::has("column.{$column->id}"));
    }

    /*
    |--------------------------------------------------------------------------
    | Cache invalidation
    |--------------------------------------------------------------------------
    */

    public function test_create_invalidates_board_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        // Prime the board cache
        Cache::put("board.{$board->id}", $board, now()->addMinutes(30));
        Cache::put("board.{$board->id}.with_relations", $board, now()->addMinutes(30));

        // Create a column
        $this->repository->create([
            'name' => 'New Column',
            'board_id' => $board->id,
            'position' => 0,
        ]);

        // Board cache should be invalidated
        $this->assertFalse(Cache::has("board.{$board->id}"));
        $this->assertFalse(Cache::has("board.{$board->id}.with_relations"));
    }

    public function test_delete_invalidates_column_and_board_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        // Prime caches
        $this->repository->findById($column->id);
        Cache::put("board.{$board->id}", $board, now()->addMinutes(30));

        $columnId = $column->id;

        // Delete the column
        $this->repository->delete($column);

        // Both caches should be invalidated
        $this->assertFalse(Cache::has("column.{$columnId}"));
        $this->assertFalse(Cache::has("board.{$board->id}"));
    }
}
