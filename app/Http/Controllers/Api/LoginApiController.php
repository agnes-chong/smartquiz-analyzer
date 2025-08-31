<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginApiController extends Controller
{
    public function __invoke(Request $request)
    {
        // Force response to be JSON
        $request->headers->set('Accept', 'application/json');

        // Validate input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt login
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Issue token
        $user = $request->user();
        $token = $user->createToken('api-token', [$user->role ?? 'user'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'abilities' => [$user->role ?? 'user'],
            'user' => $user,
        ]);
    }
}
