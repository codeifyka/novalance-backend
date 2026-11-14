<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register', 'resetPassword']]);
    }   

    public function register(RegisterRequest $request)
    {
        $user = User::create(request(['username','first_name','last_name','account_type','email','password']));

        return response()->json([
            "user" => $user
        ]);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);
        error_log($credentials['email']);
        error_log($credentials['password']);
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        error_log("Succed");
        return $this->respondWithToken($token);
    }

    public function me()
    {
        if(auth('api')->check()){
            return response()->json(auth('api')->user());
        }

        return response()->json([
            "error" => "Bad token"
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    public function resetPassword(\Illuminate\Http\Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $confirmPassword = $request->input('confirm_password') ?? $request->input('password_confirmation');

        if (empty($email)) {
            return response()->json(["error" => "Email is required."], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(["error" => "No account found with this email."], 404);
        }

        if (empty($password) || strlen($password) < 6) {
            return response()->json(["error" => "Password must be at least 6 characters."], 422);
        }

        if ($password !== $confirmPassword) {
            return response()->json(["error" => "Passwords do not match."], 422);
        }

        $user->password = $password;
        $user->save();

        return response()->json([
            "message" => "Password reset successfully. You can now log in with your new password."
        ]);
    }

    protected function respondWithToken($token)
    {
        $user = auth('api')->user();
        error_log("$user");
        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'account_type' => $user->account_type
            ]
        ]);
    }
}
