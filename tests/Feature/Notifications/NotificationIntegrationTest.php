<?php

namespace Tests\Feature\Notifications;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Notifications\CardMovedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Card Move Notification
    |--------------------------------------------------------------------------
    */

    public function test_card_move_creates_notification_for_board_owner(): void
    {
        $boardOwner = User::factory()->create();
        $mover = User::factory()->create();
        $mover->syncRoles(['admin']);

        $board = Board::factory()->create(['user_id' => $boardOwner->id]);
        $column1 = Column::factory()->create(['board_id' => $board->id, 'name' => 'To Do']);
        $column2 = Column::factory()->create(['board_id' => $board->id, 'name' => 'Done']);
        $card = Card::factory()->create(['column_id' => $column1->id, 'position' => 0]);

        Sanctum::actingAs($mover);

        $response = $this->patchJson("/api/cards/{$card->id}/move", [
            'column_id' => $column2->id,
            'position' => 0,
        ]);

        $response->assertStatus(200);

        // Board owner should have a notification
        $this->assertTrue(
            $boardOwner->notifications()->where('type', CardMovedNotification::class)->exists()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Notification API Endpoints
    |--------------------------------------------------------------------------
    */

    public function test_notifications_index_returns_users_notifications(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a notification manually
        $user->notify(new CardMovedNotification(
            Card::factory()->create([
                'column_id' => Column::factory()->create([
                    'board_id' => Board::factory()->create(['user_id' => $user->id])->id
                ])->id
            ]),
            User::factory()->create(),
            'To Do',
            'Done'
        ));

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'data', 'read_at', 'created_at'],
                ],
            ]);
    }

    public function test_notifications_unread_returns_only_unread(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $mover = User::factory()->create();

        // Create 2 notifications
        $user->notify(new CardMovedNotification($card, $mover, 'To Do', 'In Progress'));
        $user->notify(new CardMovedNotification($card, $mover, 'In Progress', 'Done'));

        // Mark one as read
        $user->notifications()->first()->markAsRead();

        $response = $this->getJson('/api/notifications/unread');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $user->notify(new CardMovedNotification($card, User::factory()->create(), 'To Do', 'Done'));

        $notification = $user->notifications()->first();

        $response = $this->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(204);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);
        $mover = User::factory()->create();

        // Create multiple notifications
        $user->notify(new CardMovedNotification($card, $mover, 'To Do', 'In Progress'));
        $user->notify(new CardMovedNotification($card, $mover, 'In Progress', 'Done'));

        $response = $this->postJson('/api/notifications/read-all');

        $response->assertStatus(204);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_delete_notification(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $user->notify(new CardMovedNotification($card, User::factory()->create(), 'To Do', 'Done'));

        $notification = $user->notifications()->first();

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }
}
