<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): JsonResponse{
        $users = User::select('id', 'login')->get();

        return response()->json($users);
    }

    public function giveAccess(Request $request): JsonResponse{
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $ownerId = $request->user()->id;
        $guestId = $request->user_id;

        if($ownerId == $guestId){
            return response()->json([
                'message' => 'Нельзя дать доступ к своей библиотеке самому себе',
            ], 400);
        }

        UserAccess::firstOrCreate([
            'owner_id' => $ownerId,
            'guest_id' => $guestId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Доступ к вашей библиотеке предоставлен'
        ]);
    }
}
