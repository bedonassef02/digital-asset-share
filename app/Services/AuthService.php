<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token');

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $user->tokens()->delete(); // Revoke existing tokens

        $token = $user->createToken('auth_token');

        return [
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
