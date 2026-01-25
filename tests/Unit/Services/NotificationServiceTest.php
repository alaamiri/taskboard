<?php

namespace Tests\Unit\Services;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Notifications\BoardSharedNotification;
use App\Notifications\CardAssignedNotification;
use App\Notifications\CardMovedNotification;
use App\Services\Notification\NotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->service = new NotificationService();
    }

    /*
    |--------------------------------------------------------------------------
    | notifyCardMoved
    |--------------------------------------------------------------------------
    */

    public function test_notify_card_moved_sends_notification_to_board_owner(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $mover = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->service->notifyCardMoved($card, $mover, 'To Do', 'Done');

        Notification::assertSentTo($owner, CardMovedNotification::class, function ($notification) use ($card) {
            return $notification->card->id === $card->id
                && $notification->fromColumn === 'To Do'
                && $notification->toColumn === 'Done';
        });
    }

    public function test_notify_card_moved_excludes_the_mover(): void
    {
        Notification::fake();

        $owner = User::factory()->create();

        $board = Board::factory()->create(['user_id' => $owner->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        // Owner moves their own card
        $this->service->notifyCardMoved($card, $owner, 'To Do', 'Done');

        Notification::assertNotSentTo($owner, CardMovedNotification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | notifyCardAssigned
    |--------------------------------------------------------------------------
    */

    public function test_notify_card_assigned_sends_notification(): void
    {
        Notification::fake();

        $assignedTo = User::factory()->create();
        $assignedBy = User::factory()->create();
        $assignedBy->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $assignedBy->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->service->notifyCardAssigned($card, $assignedTo, $assignedBy);

        Notification::assertSentTo($assignedTo, CardAssignedNotification::class, function ($notification) use ($card, $assignedBy) {
            return $notification->card->id === $card->id
                && $notification->assignedBy->id === $assignedBy->id;
        });
    }

    public function test_notify_card_assigned_excludes_self_assignment(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        // User assigns card to themselves
        $this->service->notifyCardAssigned($card, $user, $user);

        Notification::assertNotSentTo($user, CardAssignedNotification::class);
    }

    /*
    |--------------------------------------------------------------------------
    | notifyBoardShared
    |--------------------------------------------------------------------------
    */

    public function test_notify_board_shared_sends_notification(): void
    {
        Notification::fake();

        $sharedWith = User::factory()->create();
        $sharedBy = User::factory()->create();
        $sharedBy->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $sharedBy->id]);

        $this->service->notifyBoardShared($board, $sharedWith, $sharedBy);

        Notification::assertSentTo($sharedWith, BoardSharedNotification::class, function ($notification) use ($board, $sharedBy) {
            return $notification->board->id === $board->id
                && $notification->sharedBy->id === $sharedBy->id;
        });
    }

    public function test_notify_board_shared_excludes_self_sharing(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $user->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $user->id]);

        // User shares with themselves (edge case)
        $this->service->notifyBoardShared($board, $user, $user);

        Notification::assertNotSentTo($user, BoardSharedNotification::class);
    }
}
