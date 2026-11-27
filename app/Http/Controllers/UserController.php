<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JobPost;
use App\Models\Proposal;
use App\Models\Purchase;
use App\Models\PayoutRequest;
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
                    "notify_proposals" => (bool) (auth('api')->user()->notify_proposals ?? true),
                    "notify_purchases" => (bool) (auth('api')->user()->notify_purchases ?? true),
                    "availability_status" => auth('api')->user()->availability_status ?? 'available',
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

        $availability = $currentUser->availability_status ?? 'available';
        if ($request->has('availability_status') && in_array($request['availability_status'], ['available', 'busy', 'unavailable'], true)) {
            $availability = $request['availability_status'];
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
            'notify_proposals' => $request->has('notify_proposals') ? (bool) $request->boolean('notify_proposals') : ($currentUser->notify_proposals ?? true),
            'notify_purchases' => $request->has('notify_purchases') ? (bool) $request->boolean('notify_purchases') : ($currentUser->notify_purchases ?? true),
            'availability_status' => $availability,
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

    public function getAllJobs(Request $request)
    {
        $query = JobPost::with('categories')->withCount('proposals');

        if ($request->filled('search')) {
            $search = '%' . strtolower($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(job_posts.title) LIKE ?', [$search])
                  ->orWhereRaw('LOWER(job_posts.description) LIKE ?', [$search])
                  ->orWhereHas('categories', function ($categoryQuery) use ($search) {
                      $categoryQuery->whereRaw('LOWER(categories.name) LIKE ?', [$search]);
                  });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $query->latest();

        if ($request->has('page') || $request->has('per_page') || $request->boolean('paginate')) {
            $perPage = (int) $request->input('per_page', 6);
            $paginated = $query->paginate($perPage);

            return response()->json([
                'data' => [
                    'items' => $paginated->items(),
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ]
            ]);
        }

        return response()->json(['data' => $query->get()]);
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

    /**
     * Permanently delete the authenticated user's account after safety checks.
     */
    public function deleteAccount(Request $request)
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['error' => 'Incorrect password.'], 422);
        }

        // Safety Check 1: Active or disputed proposals
        $hasActiveProposals = Proposal::whereIn('status', ['active', 'disputed'])
            ->where(function ($q) use ($user) {
                $q->where('freelancer_id', $user->id)
                  ->orWhereHas('job_post', function ($jq) use ($user) {
                      $jq->where('user_id', $user->id);
                  });
            })
            ->exists();

        if ($hasActiveProposals) {
            return response()->json([
                'error' => 'Cannot delete account while you have active or disputed job proposals.'
            ], 400);
        }

        // Safety Check 2: Active, delivered, or disputed service purchases
        $hasActivePurchases = Purchase::whereIn('status', ['active', 'delivered', 'disputed'])
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)
                  ->orWhere('freelancer_id', $user->id);
            })
            ->exists();

        if ($hasActivePurchases) {
            return response()->json([
                'error' => 'Cannot delete account while you have active, delivered, or disputed service orders.'
            ], 400);
        }

        // Safety Check 3: Pending payout requests
        $hasPendingPayouts = PayoutRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingPayouts) {
            return response()->json([
                'error' => 'Cannot delete account while you have pending payout requests.'
            ], 400);
        }

        // Safety Check 4: Remaining wallet balance
        $wallet = $user->wallet;
        if ($wallet && (float) $wallet->balance > 0) {
            return response()->json([
                'error' => 'Please withdraw or spend your remaining wallet balance ($' . number_format((float) $wallet->balance, 2) . ') before deleting your account.'
            ], 400);
        }

        // Revoke Sanctum tokens and delete user
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Your account has been permanently deleted.'
        ], 200);
    }
}
