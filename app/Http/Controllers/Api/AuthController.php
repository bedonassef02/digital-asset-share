<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Http\Requests\Auth\LoginRequest;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(StoreUserRequest $request) // Use StoreUserRequest
    {
        $result = $this->authService->register($request->validated()); // Use validated()

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $result['access_token'],
            'token_type' => $result['token_type'],
        ], 201);
    }

    public function login(LoginRequest $request) // Use LoginRequest
    {
        try {
            $result = $this->authService->login($request->validated()); // Use validated()
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
