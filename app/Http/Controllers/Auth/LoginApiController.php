<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginApiController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->headers->set('Accept', 'application/json');

        $cred = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (! Auth::attempt($cred)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = $request->user();

        // give the token an ability matching the role ('teacher' or 'admin' or 'student')
        $abilities = [$user->role];              // e.g. ['teacher']
        $token = $user->createToken('api', $abilities);

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities'  => $abilities,
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }
}
