<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\JobPost;
use App\Notifications\NewProposalNotification;
use Illuminate\Support\Facades\App;

class ProposalController extends Controller
{

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'job_post_id' => 'required|exists:job_posts,id',
            'cover_letter' => 'required|string',
            'bid' => 'required|numeric|min:1',
            'duration' => 'nullable|string',
        ]);
        $validated['freelancer_id'] = $user->id;
        $validated['status'] = 'pending';

        // If proposal already exists for this job, update it; otherwise create
        $existing = Proposal::where('job_post_id', $validated['job_post_id'])
            ->where('freelancer_id', $user->id)
            ->first();

        if ($existing) {
            $existing->update($validated);

            // Notify job owner of updated proposal
            $jobPost = JobPost::with('user')->find($validated['job_post_id']);
            if ($jobPost && $jobPost->user && $jobPost->user->id !== $user->id) {
                try {
                    $jobPost->user->notify(new NewProposalNotification($existing, $jobPost, $user));
                } catch (\Throwable $e) {
                    \Log::error('Failed to send NewProposalNotification: ' . $e->getMessage());
                }
            }

            return response()->json(['data' => $existing], 200);
        }

        $proposal = Proposal::create($validated);

        // Notify job owner of new proposal
        $jobPost = JobPost::with('user')->find($validated['job_post_id']);
        if ($jobPost && $jobPost->user && $jobPost->user->id !== $user->id) {
            try {
                $jobPost->user->notify(new NewProposalNotification($proposal, $jobPost, $user));
            } catch (\Throwable $e) {
                \Log::error('Failed to send NewProposalNotification: ' . $e->getMessage());
            }
        }

        return response()->json(['data' => $proposal], 201);
    }
    
    public function getByJobPostAndFreelancer($jobPostId): JsonResponse
    {
        $user = auth('api')->user() ?: request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $proposal = Proposal::where('job_post_id', $jobPostId)
            ->where('freelancer_id', $user->id)
            ->first();

        if ($proposal) {
            return response()->json(['data' => $proposal], 200);
        } else {
            return response()->json(['error' => 'Proposal not found'], 404);
        }
    }

    public function getMyProposals(): JsonResponse
    {
        $user = auth('api')->user() ?: request()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get job posts created by the authenticated user
        $jobPosts = JobPost::where('user_id', $user->id)->pluck('id');

        // Get proposals for the job posts
        $proposals = Proposal::whereIn('job_post_id', $jobPosts)->get();

        return response()->json(['data' => $proposals], 200);
    }

    public function acceptProposal($id): JsonResponse
    {
        $user = auth('api')->user() ?: request()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Find the proposal
        $proposal = Proposal::where('id', $id)
            ->whereHas('job_post', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->first();

        if ($proposal) {
            $proposal->status = 'active';
            $proposal->started_at = now();
            $proposal->save();
            $chatData = [
                'freelancer_id' => $proposal->freelancer_id,
                'client_id' => $user->id,
                'job_post_id' => $proposal->job_post_id,
                'chattable_type' => JobPost::class,
                'chattable_id' => $proposal->job_post_id,
                'tag' => 'job_post',
                'start_date' => $proposal->started_at,
            ];
            $chatController = App::make(ChatController::class);
            $newChat = $chatController->store(new Request($chatData));

            return response()->json(["data"=> $proposal, "chat" => $newChat], 200);
        } else {
            return response()->json(['message' => 'Proposal not found or you are not authorized to accept this proposal'], 404);
        }

    }

    public function getFreelancerProposals(): JsonResponse
    {
        $user = auth('api')->user() ?: request()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get proposals for the job posts
        $proposals = Proposal::where('freelancer_id', $user->id)->get();

        return response()->json(['data' => $proposals], 200);
    }
}
