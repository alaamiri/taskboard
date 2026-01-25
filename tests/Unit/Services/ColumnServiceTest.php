<?php

namespace Tests\Unit\Services;

use App\Data\Column\ColumnData;
use App\Events\ColumnDeleted;
use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use App\Services\Model\ColumnService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelData\Optional;
use Tests\TestCase;

class ColumnServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | create
    |--------------------------------------------------------------------------
    */

    public function test_create_sets_position_automatically(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        Column::factory()->create(['board_id' => $board->id, 'position' => 0]);
        Column::factory()->create(['board_id' => $board->id, 'position' => 1]);

        $data = ColumnData::from(['name' => 'New Column']);

        $service = app(ColumnService::class);
        $column = $service->create($board, $data);

        $this->assertEquals(2, $column->position);
    }

    public function test_create_calls_repository(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $data = ColumnData::from(['name' => 'New Column']);

        $service = app(ColumnService::class);
        $column = $service->create($board, $data);

        $this->assertEquals('New Column', $column->name);
        $this->assertEquals($board->id, $column->board_id);
        $this->assertEquals(0, $column->position);
    }

    /*
    |--------------------------------------------------------------------------
    | update
    |--------------------------------------------------------------------------
    */

    public function test_update_updates_only_provided_fields(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create([
            'board_id' => $board->id,
            'name' => 'Original',
            'position' => 0
        ]);

        $data = new ColumnData(
            name: 'Updated Name',
            position: null
        );

        $service = app(ColumnService::class);
        $result = $service->update($column, $data);

        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals(0, $result->position);
    }

    public function test_update_can_change_position(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create([
            'board_id' => $board->id,
            'name' => 'Column',
            'position' => 0
        ]);

        $data = new ColumnData(
            name: Optional::create(),
            position: 2
        );

        $service = app(ColumnService::class);
        $result = $service->update($column, $data);

        $this->assertEquals(2, $result->position);
    }

    /*
    |--------------------------------------------------------------------------
    | delete
    |--------------------------------------------------------------------------
    */

    public function test_delete_removes_column_and_cards(): void
    {
        Event::fake([ColumnDeleted::class]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $column->cards()->create(['title' => 'Card 1', 'position' => 0]);
        $column->cards()->create(['title' => 'Card 2', 'position' => 1]);

        $service = app(ColumnService::class);
        $service->delete($column);

        $this->assertDatabaseMissing('columns', ['id' => $column->id]);
        $this->assertDatabaseCount('cards', 0);
    }

    public function test_delete_broadcasts_event(): void
    {
        Event::fake([ColumnDeleted::class]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $service = app(ColumnService::class);
        $service->delete($column);

        Event::assertDispatched(ColumnDeleted::class, function ($event) use ($board, $column) {
            return $event->boardId === $board->id && $event->columnId === $column->id;
        });
    }
}
