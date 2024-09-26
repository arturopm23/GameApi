<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Game;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    protected $playerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the migrations
        Artisan::call('migrate');
    
        // Seed the database
        Artisan::call('db:seed');
    
        // Create a personal access client for Passport without interaction
        Artisan::call('passport:client', [
            '--name' => 'TestClient',
            '--no-interaction' => true,
            '--personal' => true
        ]);
    
        // Create a user with 'player' role
        $this->playerUser = User::create([
            'name' => 'PlayerUser',
            'email' => 'player@example.com',
            'password' => bcrypt('securePassword'),
        ]);
        $this->playerUser->assignRole('player');
    }

    public function test_player_can_roll_dice()
    {
        // Generate a token for the player user
        $token = $this->playerUser->createToken('TestToken')->accessToken;

        // Send a POST request to roll the dice
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/players/{$this->playerUser->id}/games");

        // Assert the response status and structure
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'game' => [
                         'id',
                         'user_id',
                         'dice1',
                         'dice2',
                         'win',
                         'created_at',
                         'updated_at',
                     ],
                 ]);

        // Assert that a game was created in the database
        $this->assertDatabaseHas('games', [
            'user_id' => $this->playerUser->id,
        ]);
    }

    public function test_player_can_delete_their_games()
    {
        // Generate a token for the player user
        $token = $this->playerUser->createToken('TestToken')->accessToken;

        // Create a game record for the player
        Game::create([
            'user_id' => $this->playerUser->id,
            'dice1' => 3,
            'dice2' => 4,
            'win' => true,
        ]);

        // Send a DELETE request to delete the player's games
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/players/{$this->playerUser->id}/games");

        // Assert the response status and message
        $response->assertStatus(200)
                 ->assertJson(['message' => 'All games deleted successfully.']);

        // Assert that no games exist in the database for the player
        $this->assertDatabaseMissing('games', [
            'user_id' => $this->playerUser->id,
        ]);
    }

    public function test_player_can_get_their_games()
    {
        // Generate a token for the player user
        $token = $this->playerUser->createToken('TestToken')->accessToken;

        // Create multiple game records for the player
        Game::create([
            'user_id' => $this->playerUser->id,
            'dice1' => 1,
            'dice2' => 2,
            'win' => false,
        ]);
        Game::create([
            'user_id' => $this->playerUser->id,
            'dice1' => 3,
            'dice2' => 4,
            'win' => true,
        ]);

        // Send a GET request to retrieve the player's games
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/players/{$this->playerUser->id}/games");

        // Assert the response status and structure
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'user_id',
                         'dice1',
                         'dice2',
                         'win',
                         'created_at',
                         'updated_at',
                     ],
                 ]);
    }

    public function test_player_cannot_roll_dice_for_other_player()
    {
        // Create another player user
        $anotherPlayer = User::create([
            'name' => 'AnotherPlayer',
            'email' => 'anotherplayer@example.com',
            'password' => bcrypt('securePassword'),
        ]);
        $anotherPlayer->assignRole('player');

        // Generate a token for the first player user
        $token = $this->playerUser->createToken('TestToken')->accessToken;

        // Send a POST request to roll the dice for another player
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/players/{$anotherPlayer->id}/games");

        // Assert the response status is 403
        $response->assertStatus(403)
                 ->assertJson(['message' => 'You do not have permission to roll for this player.']);
    }

    public function test_player_cannot_delete_games_of_other_player()
    {
        // Create another player user
        $anotherPlayer = User::create([
            'name' => 'AnotherPlayer',
            'email' => 'anotherplayer@example.com',
            'password' => bcrypt('securePassword'),
        ]);
        $anotherPlayer->assignRole('player');

        // Generate a token for the first player user
        $token = $this->playerUser->createToken('TestToken')->accessToken;

        // Send a DELETE request to delete games for another player
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/players/{$anotherPlayer->id}/games");

        // Assert the response status is 403
        $response->assertStatus(403)
                 ->assertJson(['message' => 'You do not have permission to delete games for this player.']);
    }

    public function test_user_cannot_get_other_user_games()
{
    // Create another player user
    $anotherPlayer = User::create([
        'name' => 'AnotherPlayer',
        'email' => 'anotherplayer@example.com',
        'password' => bcrypt('securePassword'),
    ]);
    $anotherPlayer->assignRole('player');

    // Generate a token for the first player user
    $token = $this->playerUser->createToken('TestToken')->accessToken;

    // Send a GET request to retrieve the games of another player
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson("/api/players/{$anotherPlayer->id}/games");

    // Assert the response status is 403
    $response->assertStatus(403)
             ->assertJson(['message' => 'You do not have permission to watch this user\'s play history.']);
}

public function test_user_cannot_access_games_without_authentication()
{
    // Send a GET request to retrieve games without authentication
    $response = $this->getJson("/api/players/{$this->playerUser->id}/games");

    // Assert the response status is 401
    $response->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
}

public function test_user_cannot_roll_dice_without_authentication()
{
    // Send a POST request to roll the dice without authentication
    $response = $this->postJson("/api/players/{$this->playerUser->id}/games");

    // Assert the response status is 401
    $response->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
}

public function test_user_cannot_delete_games_without_authentication()
{
    // Send a DELETE request to delete games without authentication
    $response = $this->deleteJson("/api/players/{$this->playerUser->id}/games");

    // Assert the response status is 401
    $response->assertStatus(401)
             ->assertJson(['message' => 'Unauthenticated.']);
}

public function test_player_gets_their_games_in_correct_format()
{
    // Generate a token for the player user
    $token = $this->playerUser->createToken('TestToken')->accessToken;

    // Create multiple game records for the player
    Game::create([
        'user_id' => $this->playerUser->id,
        'dice1' => 1,
        'dice2' => 2,
        'win' => false,
    ]);
    Game::create([
        'user_id' => $this->playerUser->id,
        'dice1' => 3,
        'dice2' => 4,
        'win' => true,
    ]);

    // Send a GET request to retrieve the player's games
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson("/api/players/{$this->playerUser->id}/games");

    // Assert the response status and check the format
    $response->assertStatus(200)
             ->assertJsonStructure([
                 '*' => [
                     'id',
                     'user_id',
                     'dice1',
                     'dice2',
                     'win',
                     'created_at',
                     'updated_at',
                 ],
             ])
             ->assertJsonCount(2); // Ensure there are two games returned
}

}
