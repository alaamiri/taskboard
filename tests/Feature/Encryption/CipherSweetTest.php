<?php

namespace Tests\Feature\Encryption;

use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CipherSweetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | User Encryption Tests
    |--------------------------------------------------------------------------
    */

    public function test_user_name_is_encrypted_in_database(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        // Get raw value from database
        $rawData = DB::table('users')->where('id', $user->id)->first();

        // The raw value should not be the plaintext
        $this->assertNotEquals('John Doe', $rawData->name);
        $this->assertStringStartsWith('nacl:', $rawData->name);
    }

    public function test_user_email_is_encrypted_in_database(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Get raw value from database
        $rawData = DB::table('users')->where('id', $user->id)->first();

        // The raw value should not be the plaintext
        $this->assertNotEquals('test@example.com', $rawData->email);
        $this->assertStringStartsWith('nacl:', $rawData->email);
    }

    public function test_user_decryption_on_retrieval(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        // Fresh retrieval should decrypt
        $retrieved = User::find($user->id);

        $this->assertEquals('Jane Smith', $retrieved->name);
        $this->assertEquals('jane@example.com', $retrieved->email);
    }

    /*
    |--------------------------------------------------------------------------
    | Board Encryption Tests
    |--------------------------------------------------------------------------
    */

    public function test_board_name_is_encrypted_in_database(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create([
            'user_id' => $user->id,
            'name' => 'Project Alpha',
        ]);

        // Get raw value from database
        $rawData = DB::table('boards')->where('id', $board->id)->first();

        // The raw value should not be the plaintext
        $this->assertNotEquals('Project Alpha', $rawData->name);
        $this->assertStringStartsWith('nacl:', $rawData->name);
    }

    /*
    |--------------------------------------------------------------------------
    | Card Encryption Tests
    |--------------------------------------------------------------------------
    */

    public function test_card_title_is_encrypted_in_database(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create([
            'column_id' => $column->id,
            'title' => 'Implement Feature X',
        ]);

        // Get raw value from database
        $rawData = DB::table('cards')->where('id', $card->id)->first();

        // The raw value should not be the plaintext
        $this->assertNotEquals('Implement Feature X', $rawData->title);
        $this->assertStringStartsWith('nacl:', $rawData->title);
    }

    public function test_card_decryption_on_retrieval(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);
        $column = Column::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create([
            'column_id' => $column->id,
            'title' => 'Test Card Title',
            'description' => 'Test Description',
        ]);

        // Fresh retrieval should decrypt
        $retrieved = Card::find($card->id);

        $this->assertEquals('Test Card Title', $retrieved->title);
        $this->assertEquals('Test Description', $retrieved->description);
    }
}
