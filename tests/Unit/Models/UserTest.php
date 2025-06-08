<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_one_preference()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $preferences = UserPreference::create([
            'user_id' => $user->id,
            'sources' => ['BBC News', 'The Guardian'],
            'categories' => ['Technology', 'Science'],
            'authors' => ['John Doe', 'Jane Smith'],
        ]);

        $this->assertInstanceOf(UserPreference::class, $user->preferences);
        $this->assertEquals($preferences->id, $user->preferences->id);
        $this->assertEquals(['BBC News', 'The Guardian'], $user->preferences->sources);
        $this->assertEquals(['Technology', 'Science'], $user->preferences->categories);
        $this->assertEquals(['John Doe', 'Jane Smith'], $user->preferences->authors);
    }

    /** @test */
    public function it_can_create_token()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $token = $user->createToken('TestToken')->plainTextToken;

        $this->assertIsString($token);
        $this->assertStringContainsString('|', $token);
    }

    /** @test */
    public function it_hashes_passwords()
    {
        $plainPassword = 'secret123';
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt($plainPassword),
        ]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(password_verify($plainPassword, $user->password));
    }
}
