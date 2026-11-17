<?php

namespace App\Http\Controllers;

use App\Models\JobPost;
use App\Models\SavedJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedJobController extends Controller
{
    /**
     * Get all saved jobs for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $savedJobs = SavedJob::where('user_id', $user->id)
            ->with([
                'jobPost' => function ($q) {
                    $q->with([
                        'categories',
                        'user' => function ($u) {
                            $u->select('id', 'username', 'first_name', 'last_name', 'country', 'profile_picture', 'created_at')
                              ->withCount('jobPosts');
                        }
                    ])
                    ->withCount([
                        'proposals',
                        'proposals as active_proposals_count' => function ($qp) {
                            $qp->where('status', 'active');
                        }
                    ]);
                }
            ])
            ->latest()
            ->get();

        $jobs = $savedJobs->map(function ($saved) {
            if ($saved->jobPost) {
                $saved->jobPost->is_saved = true;
                $saved->jobPost->saved_at = $saved->created_at;
                return $saved->jobPost;
            }
            return null;
        })->filter()->values();

        return response()->json(['data' => $jobs], 200);
    }

    /**
     * Toggle save/unsave a job post.
     */
    public function toggle(Request $request, $jobPostId): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $job = JobPost::find($jobPostId);
        if (!$job) {
            return response()->json(['error' => 'Job post not found'], 404);
        }

        $existing = SavedJob::where('user_id', $user->id)
            ->where('job_post_id', $jobPostId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'saved' => false,
                'message' => 'Job removed from saved jobs',
            ], 200);
        } else {
            SavedJob::create([
                'user_id' => $user->id,
                'job_post_id' => $jobPostId,
            ]);
            return response()->json([
                'saved' => true,
                'message' => 'Job saved successfully',
            ], 201);
        }
    }

    /**
     * Check if a job post is saved by the authenticated user.
     */
    public function check(Request $request, $jobPostId): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['saved' => false], 200);
        }

        $isSaved = SavedJob::where('user_id', $user->id)
            ->where('job_post_id', $jobPostId)
            ->exists();

        return response()->json(['saved' => $isSaved], 200);
    }
}
