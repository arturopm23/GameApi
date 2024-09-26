<?php
namespace Tests\Feature;

use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $playerUser;
    protected $adminUser;

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
    
        // Create a user with 'admin' role
        $this->adminUser = User::create([
            'name' => 'AdminUser2',
            'email' => 'admin2@example.com',
            'password' => bcrypt('securePassword'),
        ]);
        $this->adminUser->assignRole('admin');
    }

    public function test_user_can_be_stored()
    {
        $userData = [
            'name' => 'TestUser',
            'email' => 'testuser@example.com',
            'password' => 'securePassword',
        ];

        $response = $this->postJson('/api/players', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message', 'user' => ['id', 'name', 'email']
                 ]);

        $this->assertDatabaseHas('users', [
            'name' => 'TestUser',
            'email' => 'testuser@example.com',
        ]);
    }

    public function test_player_can_login() 
    {
        $response = $this->postJson('/api/players/login', [ 
            'email' => $this->playerUser->email,
            'password' => 'securePassword',
        ]);
    
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'token',
                 ]);
    }

    public function test_admin_can_login() 
    {
        // Admin login
        $response = $this->postJson('/api/players/login', [ 
            'email' => $this->adminUser->email,
            'password' => 'securePassword',
        ]);
    
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'token',
                 ]);
    }

    public function test_wrong_password_cannot_login() 
{
    $response = $this->postJson('/api/players/login', [ 
        'email' => $this->playerUser->email,
        'password' => 'wrongPassword',
    ]);

    $response->assertStatus(401)
             ->assertJson([
                 'error' => 'Unauthorized',
             ]);
}

    public function test_player_can_modify_name()
    {
        $token = $this->playerUser->createToken(env('APP_NAME'))->accessToken;
    
        $newName = 'Updated Player Name';
    
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/players/{$this->playerUser->id}", [
            'name' => $newName,
        ]);
    
        $response->assertStatus(200);
    
        $response->assertJson([
            'message' => 'Name updated successfully.',
            'name' => $newName,
        ]);
    
        $this->playerUser->refresh();
    
        $this->assertEquals($newName, $this->playerUser->name);
    }

    public function test_admin_can_modify_name()
{
    $token = $this->adminUser->createToken(env('APP_NAME'))->accessToken;

    $newName = 'Updated Admin Name';

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->putJson("/api/players/{$this->adminUser->id}", [
        'name' => $newName,
    ]);

    $response->assertStatus(200);

    $response->assertJson([
        'message' => 'Name updated successfully.',
        'name' => $newName,
    ]);

    $this->adminUser->refresh();

    $this->assertEquals($newName, $this->adminUser->name);
}

public function test_a_player_cannot_modify_other_player()
{
    // Create another player user
    $otherPlayer = User::factory()->create([
        'name' => 'OtherPlayer',
        'email' => 'otherplayer@example.com',
        'password' => bcrypt('securePassword'),
    ]);

    // Generate a token for the original player user
    $token = $this->playerUser->createToken(env('APP_NAME'))->accessToken;

    // New name for the other player
    $newName = 'Attempted Name Change';

    // Attempt to modify the other player's name
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->putJson("/api/players/{$otherPlayer->id}", [
        'name' => $newName,
    ]);

    // Assert that the response status is 403 Forbidden
    $response->assertStatus(403)
             ->assertJson([
                 'message' => 'You do not have permission to update this user.',
             ]);

    // Refresh the other player instance to check the name hasn't changed
    $otherPlayer->refresh();

    // Assert that the name remains unchanged
    $this->assertNotEquals($newName, $otherPlayer->name);
}

public function test_admin_cannot_modify_player_name()
{
    // Create a player user
    $playerUser = User::factory()->create([
        'name' => 'OriginalPlayer',
        'email' => 'originalplayer@example.com',
        'password' => bcrypt('securePassword'),
    ]);

    // Generate a token for the admin user
    $token = $this->adminUser->createToken(env('APP_NAME'))->accessToken;

    // New name that the admin will try to set
    $newName = 'Admin Attempted Change';

    // Attempt to modify the player's name
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->putJson("/api/players/{$playerUser->id}", [
        'name' => $newName,
    ]);

    // Assert that the response status is 403 Forbidden
    $response->assertStatus(403)
             ->assertJson([
                 'message' => 'You do not have permission to update this user.',
             ]);

    // Refresh the player instance to check the name hasn't changed
    $playerUser->refresh();

    // Assert that the name remains unchanged
    $this->assertNotEquals($newName, $playerUser->name);
}

public function test_admin_can_access_player_routes()
{
    // Generate a personal access token for the admin user
    $token = $this->adminUser->createToken(env('APP_NAME'))->accessToken;

    // Test accessing the players index route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players');
    $response->assertStatus(200);

    // Test accessing the ranking route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players/ranking');
    $response->assertStatus(200);

    // Test accessing the loser ranking route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players/ranking/loser');
    $response->assertStatus(200);

    // Test accessing the winner ranking route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players/ranking/winner');
    $response->assertStatus(200);
}

public function test_player_cannot_access_admin_routes()
{
    // Generate a personal access token for the player user
    $token = $this->playerUser->createToken(env('APP_NAME'))->accessToken;

    // Test accessing the players index route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players');
    $response->assertStatus(403);

    // Test accessing the ranking route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players/ranking');
    $response->assertStatus(403);

    // Test accessing the loser ranking route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players/ranking/loser');
    $response->assertStatus(403);

    // Test accessing the winner ranking route
    $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/players/ranking/winner');
    $response->assertStatus(403);
}

}
