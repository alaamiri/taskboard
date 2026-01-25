<?php

namespace Tests\Unit\Repositories;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Repositories\Eloquent\CardRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CardRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->repository = new CardRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | findById
    |--------------------------------------------------------------------------
    */

    public function test_find_by_id_returns_card(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $result = $this->repository->findById($card->id);

        $this->assertNotNull($result);
        $this->assertEquals($card->id, $result->id);
    }

    public function test_find_by_id_uses_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        // First call - should cache
        $this->repository->findById($card->id);

        // Verify cache was set
        $this->assertTrue(Cache::has("card.{$card->id}"));
    }

    /*
    |--------------------------------------------------------------------------
    | Cache invalidation
    |--------------------------------------------------------------------------
    */

    public function test_create_invalidates_column_and_board_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        // Prime board cache
        Cache::put("board.{$board->id}", $board, now()->addMinutes(30));
        Cache::put("board.{$board->id}.with_relations", $board, now()->addMinutes(30));
        Cache::put("column.{$column->id}", $column, now()->addMinutes(30));

        // Create a card
        $this->repository->create([
            'title' => 'New Card',
            'description' => null,
            'column_id' => $column->id,
            'position' => 0,
            'user_id' => null,
        ]);

        // Board and column caches should be invalidated
        $this->assertFalse(Cache::has("board.{$board->id}"));
        $this->assertFalse(Cache::has("board.{$board->id}.with_relations"));
        $this->assertFalse(Cache::has("column.{$column->id}"));
    }

    public function test_update_with_column_change_invalidates_both_columns(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column1 = Column::factory()->create(['board_id' => $board->id]);
        $column2 = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column1->id]);

        // Prime caches
        Cache::put("column.{$column1->id}", $column1, now()->addMinutes(30));
        Cache::put("column.{$column2->id}", $column2, now()->addMinutes(30));

        // Move card to different column
        $this->repository->update($card, [
            'column_id' => $column2->id,
            'position' => 0,
        ]);

        // Both column caches should be invalidated
        $this->assertFalse(Cache::has("column.{$column1->id}"));
        $this->assertFalse(Cache::has("column.{$column2->id}"));
    }

    public function test_delete_invalidates_card_column_and_board_cache(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        // Prime caches
        $this->repository->findById($card->id);
        Cache::put("column.{$column->id}", $column, now()->addMinutes(30));
        Cache::put("board.{$board->id}", $board, now()->addMinutes(30));

        $cardId = $card->id;

        // Delete the card
        $this->repository->delete($card);

        // All related caches should be invalidated
        $this->assertFalse(Cache::has("card.{$cardId}"));
        $this->assertFalse(Cache::has("column.{$column->id}"));
        $this->assertFalse(Cache::has("board.{$board->id}"));
    }
}
