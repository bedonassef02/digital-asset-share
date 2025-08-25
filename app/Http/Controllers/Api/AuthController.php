<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function register(StoreUserRequest $request): \Illuminate\Http\JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ], 201);
    }

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $result = $this->authService->login(...$request->validated());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 401);
        }

        return response()->json([
            'message' => 'Logged in successfully',
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json($request->user());
    }
}
