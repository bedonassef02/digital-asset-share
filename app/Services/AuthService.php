<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
        ]);

        $user->createToken('auth_token')->plainTextToken;

        return $user;
    }

    public function login(string $email, string $password): string
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials'],
            ]);
        }

        $user->tokens()->delete(); // Revoke existing tokens

        $token = $user->createToken('auth_token')->plainTextToken;

        return $token;
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
