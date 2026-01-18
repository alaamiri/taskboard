<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Column;
use App\Models\User;
use App\Policies\ColumnPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColumnPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ColumnPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ColumnPolicy();
    }

    public function test_user_can_create_column_in_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->create($user, $board));
    }

    public function test_user_cannot_create_column_in_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->create($user, $board));
    }

    public function test_user_can_update_column_in_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $this->assertTrue($this->policy->update($user, $column));
    }

    public function test_user_cannot_update_column_in_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $this->assertFalse($this->policy->update($user, $column));
    }

    public function test_user_can_delete_column_in_own_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $this->assertTrue($this->policy->delete($user, $column));
    }

    public function test_user_cannot_delete_column_in_others_board(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $otherUser->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);

        $this->assertFalse($this->policy->delete($user, $column));
    }
}
