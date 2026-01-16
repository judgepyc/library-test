<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegistrationRequest;
use App\Models\User; 
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function registration(RegistrationRequest $request) : JsonResponse{
        $data = $request->validated();

        $user = User::create([
            'login' => $data['login'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'login' => $user->login,
            ]
        ], 201);
    }

    public function login(Request $request): JsonResponse{
        $request -> validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('login', $request->login)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json([
                'message' => 'Неверный логин или пароль',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse{
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешный выход',
        ]);
    }
}
