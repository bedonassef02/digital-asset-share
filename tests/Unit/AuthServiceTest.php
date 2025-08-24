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

        $user = $this->authService->register($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /** @test */
    public function it_logs_in_a_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $this->authService->login('test@example.com', 'password');

        $this->assertIsString($token);
        $this->assertCount(1, $user->tokens);
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
