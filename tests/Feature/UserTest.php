<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'player']);
        Role::create(['name' => 'admin']); 
    }

    public function test_user_can_be_stored()
    {
        // Create user data
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login_ok() {
        // Create a user with a known password and a name
        // Create user data
        $userData = [
            'name' => 'TestUser',
            'email' => 'testuser@example.com',
            'password' => 'securePassword',
        ];

        $response = $this->postJson('/api/players', $userData);
    
        // Attempt to login with the created user's credentials
        $response = $this->postJson('/api/players/login', [ // Adjust the endpoint if necessary
            'email' => 'testuser@example.com',
            'password' => 'securePassword',
        ]);
    
        // Assert that the login response is successful
        $response->assertStatus(200); // Check for a 200 OK response
    
        // Assert that the response structure contains 'message' and 'token'
        $response->assertJsonStructure([
            'message', // Assuming you return a message in your response
            'token',   // Check that a token is included in the response
        ]);
    }
    
    
    
}
