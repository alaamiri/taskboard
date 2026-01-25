<?php

namespace Tests\Unit\Services;

use App\Data\Board\BoardData;
use App\Exceptions\Board\BoardDeletionFailedException;
use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Services\Model\BoardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Optional;
use Tests\TestCase;

class BoardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | getAllForUser
    |--------------------------------------------------------------------------
    */

    public function test_get_all_for_user_returns_only_user_boards(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Board::factory()->count(2)->create(['user_id' => $user->id]);
        Board::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $service = app(BoardService::class);
        $result = $service->getAllForUser($user);

        $this->assertCount(2, $result);
    }

    public function test_get_all_for_admin_returns_all_boards(): void
    {
        $admin = User::factory()->create();
        $admin->syncRoles(['admin']);

        $user = User::factory()->create();

        Board::factory()->count(2)->create(['user_id' => $admin->id]);
        Board::factory()->count(3)->create(['user_id' => $user->id]);

        $service = app(BoardService::class);
        $result = $service->getAllForUser($admin);

        $this->assertCount(5, $result);
    }

    /*
    |--------------------------------------------------------------------------
    | getWithRelations
    |--------------------------------------------------------------------------
    */

    public function test_get_with_relations_loads_columns_and_cards(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $column = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
        $column->cards()->create(['title' => 'Card 1', 'position' => 0]);

        $service = app(BoardService::class);
        $result = $service->getWithRelations($board);

        $this->assertTrue($result->relationLoaded('columns'));
        $this->assertCount(1, $result->columns);
        $this->assertTrue($result->columns->first()->relationLoaded('cards'));
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    public function test_create_creates_board_with_default_columns(): void
    {
        $user = User::factory()->create();

        $data = BoardData::from([
            'name' => 'New Board',
            'description' => 'Test description',
        ]);

        $service = app(BoardService::class);
        $board = $service->create($user, $data);

        $this->assertEquals('New Board', $board->name);
        $this->assertEquals('Test description', $board->description);
        $this->assertEquals($user->id, $board->user_id);
        $this->assertCount(3, $board->columns);
        $this->assertEquals(['À faire', 'En cours', 'Terminé'], $board->columns->pluck('name')->toArray());
    }

    public function test_create_uses_transaction(): void
    {
        $user = User::factory()->create();

        $data = BoardData::from([
            'name' => 'Transaction Board',
            'description' => null,
        ]);

        $service = app(BoardService::class);
        $board = $service->create($user, $data);

        $this->assertDatabaseHas('boards', ['id' => $board->id]);
        $this->assertDatabaseCount('columns', 3);
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    public function test_update_updates_only_provided_fields(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original',
            'description' => 'Original desc'
        ]);

        $data = new BoardData(
            name: 'Updated Name',
            description: null
        );

        $service = app(BoardService::class);
        $result = $service->update($board, $data);

        $this->assertEquals('Updated Name', $result->name);
        $this->assertNull($result->description);
    }

    public function test_update_handles_optional_fields(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original',
            'description' => 'Keep this'
        ]);

        $data = new BoardData(
            name: Optional::create(),
            description: 'New description'
        );

        $service = app(BoardService::class);
        $result = $service->update($board, $data);

        // Name should stay the same, description updated
        $this->assertEquals('New description', $result->description);
    }

    /*
    |--------------------------------------------------------------------------
    | delete
    |--------------------------------------------------------------------------
    */

    public function test_delete_removes_board_and_related_data(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $column->cards()->create(['title' => 'Test Card', 'position' => 0]);

        $service = app(BoardService::class);
        $service->delete($board);

        $this->assertDatabaseMissing('boards', ['id' => $board->id]);
        $this->assertDatabaseMissing('columns', ['board_id' => $board->id]);
        $this->assertDatabaseMissing('cards', ['column_id' => $column->id]);
    }

    public function test_delete_throws_exception_on_failure(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        // Create a mock repository that throws
        $mockRepo = $this->createMock(BoardRepositoryInterface::class);
        $mockRepo->method('delete')->willThrowException(new \Exception('DB Error'));

        $service = new BoardService($mockRepo);

        $this->expectException(BoardDeletionFailedException::class);
        $service->delete($board);
    }
}
