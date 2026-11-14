<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JobPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function getInfo(Request $request)
    {
        return response()->json([
            "data" => [
                "user" => [
                    "id" => auth('api')->user()->id,
                    "username" => auth('api')->user()->username,
                    "first_name" => auth('api')->user()->first_name,
                    "last_name" => auth('api')->user()->last_name,
                    "email" => auth('api')->user()->email,
                    "account_type" => auth('api')->user()->account_type,
                    "country" => auth('api')->user()->country,
                    "gender" => auth('api')->user()->gender,
                    "telephone" => auth('api')->user()->telephone,
                    "profile_picture" => auth('api')->user()->profile_picture,
                ],
                "services" => auth('api')->user()->services->count(),
                "projects" => auth('api')->user()->projects->count(),
                "proposals" => auth('api')->user()->proposals->count(),
            ],
        ]);
    }

    public function updateInfo(Request $request)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json([
                "error" => "Unauthorized"
            ], 401);
        }

        if ($request->filled('username') && $request['username'] != $currentUser->username) {
            $usernameExists = User::query()
                ->where('username', '=', $request['username'])
                ->where('id', '!=', $currentUser->id)
                ->exists();
            if ($usernameExists) {
                return response()->json([
                    "error" => "Username already in use",
                    "field" => "username",
                ]);
            }
        }

        if ($request->filled('email') && $request['email'] != $currentUser->email) {
            $emailExists = User::query()
                ->where('email', '=', $request['email'])
                ->where('id', '!=', $currentUser->id)
                ->exists();
            if ($emailExists) {
                return response()->json([
                    "error" => "Email already in use",
                    "field" => "email",
                ]);
            }
        }

        User::query()->where('id', '=', $currentUser->id)->update([
            'username' => $request['username'] ?? $currentUser->username,
            'first_name' => $request['first_name'] ?? $currentUser->first_name,
            'last_name' => $request['last_name'] ?? $currentUser->last_name,
            'email' => $request['email'] ?? $currentUser->email,
            'gender' => $request['gender'] ?? $currentUser->gender,
            'telephone' => $request['telephone'] ?? $currentUser->telephone,
            'country' => $request['country'] ?? $currentUser->country,
            'profile_picture' => $request['profile_picture'] ?? $currentUser->profile_picture,
        ]);

        $updatedUser = User::find($currentUser->id);

        return response()->json([
            "data" => $updatedUser,
        ]);
    }

    public function uploadProfilePicture(Request $request)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json(["error" => "Unauthorized"], 401);
        }

        $file = $request->file('profile_picture') ?? $request->file('image') ?? $request->file('file');
        if (!$file) {
            return response()->json(["error" => "No image file provided."], 422);
        }

        if (!$file->isValid()) {
            return response()->json(["error" => "File upload failed: " . $file->getErrorMessage()], 422);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($extension, $allowedExtensions)) {
            return response()->json(["error" => "Invalid image format. Allowed formats: JPG, PNG, WEBP, GIF."], 422);
        }

        // Store file in public storage profile_pictures directory
        $storedFilename = $file->store('profile_pictures', 'public');
        $profilePictureUrl = '/storage/' . $storedFilename;

        // Clean up previous custom uploaded picture if exists
        if ($currentUser->profile_picture && str_starts_with($currentUser->profile_picture, '/storage/profile_pictures/')) {
            $oldPath = str_replace('/storage/', '', $currentUser->profile_picture);
            Storage::disk('public')->delete($oldPath);
        }

        $user = User::find($currentUser->id);
        $user->profile_picture = $profilePictureUrl;
        $user->save();

        return response()->json([
            "data" => [
                "profile_picture" => $profilePictureUrl,
                "user" => $user,
            ],
            "message" => "Profile picture updated successfully."
        ]);
    }

    public function changePassword(Request $request)
    {
        $currentUser = auth('api')->user();
        if (!$currentUser) {
            return response()->json(["error" => "Unauthorized"], 401);
        }

        $currentPassword = $request->input('current_password');
        $newPassword = $request->input('new_password');
        $confirmPassword = $request->input('confirm_password') ?? $request->input('new_password_confirmation');

        if (empty($currentPassword)) {
            return response()->json(["error" => "Current password is required."], 422);
        }

        if (!Hash::check($currentPassword, $currentUser->password)) {
            return response()->json(["error" => "Current password is incorrect."], 422);
        }

        if (empty($newPassword) || strlen($newPassword) < 6) {
            return response()->json(["error" => "New password must be at least 6 characters."], 422);
        }

        if ($newPassword !== $confirmPassword) {
            return response()->json(["error" => "New password and confirmation do not match."], 422);
        }

        $user = User::find($currentUser->id);
        $user->password = $newPassword;
        $user->save();

        return response()->json([
            "message" => "Password changed successfully."
        ]);
    }

    public function getAllJobs()
    {
        $jobs = JobPost::with('categories')->get();
    
        return response()->json(['data' => $jobs]);
    }

    public function getUserById($user_id)
    {
        $user = User::find($user_id);

        if ($user) {
            return response()->json([
                "data" => $user
            ]);
        } else {
            return response()->json([
                "message" => "User not found"
            ], 404);
        }
    }
}
