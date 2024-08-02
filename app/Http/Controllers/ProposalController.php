<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\JobPost;
use Illuminate\Support\Facades\App;

class ProposalController extends Controller
{

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $validated = $request->validate([
            'job_post_id' => 'required|exists:job_posts,id',
            'cover_letter' => 'required',
        ]);
        $validated['freelancer_id'] = $user->id;

        $proposal = Proposal::create($validated);
        return response()->json($proposal, 201);
    }
    
    public function getByJobPostAndFreelancer($jobPostId): JsonResponse
    {
        $user = auth('api')->user();
        $proposal = Proposal::where('job_post_id', $jobPostId)
            ->where('freelancer_id', $user->id)
            ->first();

        if ($proposal) {
            return response()->json($proposal, 200);
        } else {
            return response()->json(['message' => 'Proposal not found'], 404);
        }
    }

    public function getMyProposals():JsonResponse
    {
        $user = auth('api')->user();

        // Get job posts created by the authenticated user
        $jobPosts = JobPost::where('user_id', $user->id)->pluck('id');

        // Get proposals for the job posts
        $proposals = Proposal::whereIn('job_post_id', $jobPosts)->get();

        return response()->json($proposals, 200);
    }

    public function acceptProposal($id): JsonResponse
    {
        $user = auth('api')->user();

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
                'started_at' => $proposal->started_at,
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
        $user = auth('api')->user();

        // Get proposals for the job posts
        $proposals = Proposal::where('freelancer_id', $user->id)->get();
        
        return response()->json($proposals, 200);
    }
}
