<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /** @test */
    public function it_registers_a_new_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $result = $this->authService->register($userData);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertEquals('Bearer', $result['token_type']);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertCount(1, $user->tokens);
    }

    /** @test */
    public function it_logs_in_a_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->createToken('old_token_1');
        $user->createToken('old_token_2');

        $this->assertCount(2, $user->tokens); // Ensure old tokens exist

        $result = $this->authService->login('test@example.com', 'password');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertEquals('Bearer', $result['token_type']);

        $user->refresh(); // Refresh user to get updated token count
        $this->assertCount(1, $user->tokens); // Only the new token should exist
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login('test@example.com', 'wrong-password');
    }

    /** @test */
    public function it_fails_login_with_non_existent_email()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login('nonexistent@example.com', 'password');
    }

    /** @test */
    public function it_logs_out_a_user()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Simulate authentication for the logout method
        $this->actingAs($user);

        $this->authService->logout($user);

        // Re-fetch the user to get updated token count
        $user->refresh();

        $this->assertCount(0, $user->tokens);
    }
}
