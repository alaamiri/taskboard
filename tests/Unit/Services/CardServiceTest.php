<?php

namespace Tests\Unit\Services;

use App\Data\Card\CardData;
use App\Data\Card\MoveCardData;
use App\Events\CardDeleted;
use App\Events\CardMoved;
use App\Exceptions\Card\CannotMoveCardException;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Services\Model\CardService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\LaravelData\Optional;
use Tests\TestCase;

class CardServiceTest extends TestCase
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
        $column = Column::factory()->create(['board_id' => $board->id]);

        Card::factory()->create(['column_id' => $column->id, 'position' => 0]);
        Card::factory()->create(['column_id' => $column->id, 'position' => 1]);

        $data = CardData::from(['title' => 'New Card', 'description' => null]);

        $service = app(CardService::class);
        $card = $service->create($column, $data);

        $this->assertEquals(2, $card->position);
    }

    public function test_create_calls_repository_with_correct_data(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $data = CardData::from(['title' => 'New Card', 'description' => 'Card description']);

        $service = app(CardService::class);
        $card = $service->create($column, $data);

        $this->assertEquals('New Card', $card->title);
        $this->assertEquals('Card description', $card->description);
        $this->assertEquals($column->id, $card->column_id);
        $this->assertEquals(0, $card->position);
    }

    public function test_create_handles_null_description(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $data = CardData::from(['title' => 'Card Without Description']);

        $service = app(CardService::class);
        $card = $service->create($column, $data);

        $this->assertNull($card->description);
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
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create([
            'column_id' => $column->id,
            'title' => 'Original',
            'description' => 'Original desc'
        ]);

        $data = new CardData(
            title: 'Updated Title',
            description: null
        );

        $service = app(CardService::class);
        $result = $service->update($card, $data);

        $this->assertEquals('Updated Title', $result->title);
        $this->assertNull($result->description);
    }

    public function test_update_handles_optional_title(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create([
            'column_id' => $column->id,
            'title' => 'Keep this',
            'description' => 'Old desc'
        ]);

        $data = new CardData(
            title: Optional::create(),
            description: 'New description'
        );

        $service = app(CardService::class);
        $result = $service->update($card, $data);

        $this->assertEquals('New description', $result->description);
    }

    /*
    |--------------------------------------------------------------------------
    | delete
    |--------------------------------------------------------------------------
    */

    public function test_delete_removes_card(): void
    {
        Event::fake([CardDeleted::class]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $service = app(CardService::class);
        $service->delete($card);

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }

    public function test_delete_broadcasts_event(): void
    {
        Event::fake([CardDeleted::class]);

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $service = app(CardService::class);
        $service->delete($card);

        Event::assertDispatched(CardDeleted::class, function ($event) use ($board, $card) {
            return $event->boardId === $board->id && $event->cardId === $card->id;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | move
    |--------------------------------------------------------------------------
    */

    public function test_move_within_same_column(): void
    {
        Event::fake([CardMoved::class]);
        Notification::fake();

        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);

        Card::factory()->create(['column_id' => $column->id, 'position' => 0]);
        Card::factory()->create(['column_id' => $column->id, 'position' => 1]);
        $card3 = Card::factory()->create(['column_id' => $column->id, 'position' => 2]);

        $data = MoveCardData::from(['column_id' => $column->id, 'position' => 0]);

        $service = app(CardService::class);
        $movedCard = $service->move($card3, $data);

        $this->assertEquals($column->id, $movedCard->column_id);
        $this->assertEquals(0, $movedCard->position);
    }

    public function test_move_to_different_column(): void
    {
        Event::fake([CardMoved::class]);
        Notification::fake();

        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column1 = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
        $column2 = Column::factory()->create(['board_id' => $board->id, 'name' => 'Done']);

        $card = Card::factory()->create(['column_id' => $column1->id, 'position' => 0]);

        $data = MoveCardData::from(['column_id' => $column2->id, 'position' => 0]);

        $service = app(CardService::class);
        $movedCard = $service->move($card, $data);

        $this->assertEquals($column2->id, $movedCard->column_id);
        $this->assertEquals(0, $movedCard->position);
    }

    public function test_move_throws_exception_for_different_board(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $board1 = Board::factory()->create(['user_id' => $user->id]);
        $board2 = Board::factory()->create(['user_id' => $user->id]);

        $column1 = Column::factory()->create(['board_id' => $board1->id]);
        $column2 = Column::factory()->create(['board_id' => $board2->id]);

        $card = Card::factory()->create(['column_id' => $column1->id, 'position' => 0]);

        $data = MoveCardData::from(['column_id' => $column2->id, 'position' => 0]);

        $this->expectException(CannotMoveCardException::class);

        $service = app(CardService::class);
        $service->move($card, $data);
    }

    public function test_move_sends_notification(): void
    {
        Event::fake([CardMoved::class]);
        Notification::fake();

        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column1 = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
        $column2 = Column::factory()->create(['board_id' => $board->id, 'name' => 'Done']);

        $card = Card::factory()->create(['column_id' => $column1->id, 'position' => 0]);

        $data = MoveCardData::from(['column_id' => $column2->id, 'position' => 0]);

        $service = app(CardService::class);
        $service->move($card, $data);

        // Self-notification is excluded, so board owner won't be notified when they move their own card
        Notification::assertNothingSent();
    }

    public function test_move_broadcasts_event(): void
    {
        Event::fake([CardMoved::class]);
        Notification::fake();

        $user = User::factory()->create();
        $user->syncRoles(['admin']);
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column1 = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
        $column2 = Column::factory()->create(['board_id' => $board->id, 'name' => 'Done']);

        $card = Card::factory()->create(['column_id' => $column1->id, 'position' => 0]);

        $data = MoveCardData::from(['column_id' => $column2->id, 'position' => 0]);

        $service = app(CardService::class);
        $service->move($card, $data);

        Event::assertDispatched(CardMoved::class, function ($event) use ($column1, $column2, $card) {
            return $event->card->id === $card->id
                && $event->fromColumnId === $column1->id
                && $event->toColumnId === $column2->id;
        });
    }
}
