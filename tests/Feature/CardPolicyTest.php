<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use App\Policies\CardPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CardPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CardPolicy();
    }

    public function test_user_can_create_card_in_own_board_column(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $this->assertTrue($this->policy->create($user, $column));
    }

    public function test_user_cannot_create_card_in_others_board_column(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $this->assertFalse($this->policy->create($user, $column));
    }

    public function test_user_can_update_card_in_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->assertTrue($this->policy->update($user, $card));
    }

    public function test_user_cannot_update_card_in_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->assertFalse($this->policy->update($user, $card));
    }

    public function test_user_can_delete_card_in_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->assertTrue($this->policy->delete($user, $card));
    }

    public function test_user_cannot_delete_card_in_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->assertFalse($this->policy->delete($user, $card));
    }

    public function test_user_can_move_card_in_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->assertTrue($this->policy->move($user, $card));
    }

    public function test_user_cannot_move_card_in_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['column_id' => $column->id]);

        $this->assertFalse($this->policy->move($user, $card));
    }
}
