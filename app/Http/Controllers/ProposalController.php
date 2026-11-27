<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\JobPost;
use App\Models\User;
use App\Notifications\NewProposalNotification;
use App\Services\WalletService;
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
            if ($jobPost && $jobPost->user && $jobPost->user->id !== $user->id && ($jobPost->user->notify_proposals ?? true)) {
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
        if ($jobPost && $jobPost->user && $jobPost->user->id !== $user->id && ($jobPost->user->notify_proposals ?? true)) {
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

    public function acceptProposal(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'ends_at' => 'nullable|date',
        ]);

        // Find the proposal
        $proposal = Proposal::where('id', $id)
            ->whereHas('job_post', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->first();

        if (!$proposal) {
            return response()->json(['message' => 'Proposal not found or you are not authorized to accept this proposal'], 404);
        }

        if ($proposal->status === 'active') {
            return response()->json(['error' => 'This proposal contract is already active.'], 400);
        }

        $walletService = app(WalletService::class);

        // Verify balance and lock escrow
        try {
            $lockTx = $walletService->lockEscrow($user, $proposal);
        } catch (\Throwable $e) {
            $wallet = $user->getOrCreateWallet();
            $bid = (float) $proposal->bid;
            $deficit = max(0, $bid - (float) $wallet->balance);

            return response()->json([
                'error' => $e->getMessage(),
                'code' => 'INSUFFICIENT_BALANCE',
                'required' => $bid,
                'available' => (float) $wallet->balance,
                'deficit' => $deficit,
            ], 422);
        }

        $endsAt = $request->ends_at ? \Carbon\Carbon::parse($request->ends_at) : now()->addMonth();

        $proposal->status = 'active';
        $proposal->started_at = now();
        $proposal->ends_at = $endsAt;
        $proposal->save();

        $chatData = [
            'freelancer_id' => $proposal->freelancer_id,
            'client_id' => $user->id,
            'job_post_id' => $proposal->job_post_id,
            'chattable_type' => JobPost::class,
            'chattable_id' => $proposal->job_post_id,
            'tag' => 'job_post',
            'start_date' => $proposal->started_at,
            'end_date' => $proposal->ends_at,
        ];
        $chatController = App::make(ChatController::class);
        $newChat = $chatController->store(new Request($chatData));

        return response()->json([
            "data" => $proposal,
            "chat" => $newChat,
            "transaction" => $lockTx
        ], 200);
    }

    public function completeProposal(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $proposal = Proposal::where('id', $id)
            ->whereHas('job_post', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->first();

        if (!$proposal) {
            return response()->json(['error' => 'Proposal not found or unauthorized.'], 404);
        }

        if ($proposal->status !== 'active' && $proposal->status !== 'disputed') {
            return response()->json(['error' => "Only active or disputed proposals can be completed."], 400);
        }

        $walletService = app(WalletService::class);

        try {
            $releaseTx = $walletService->releaseEscrow($proposal);
            return response()->json([
                'message' => 'Proposal completed and earnings released to freelancer.',
                'data' => $proposal->fresh(),
                'transaction' => $releaseTx,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function setFailed(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user || $user->account_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $proposal = Proposal::with(['job_post.user', 'freelancer'])->find($id);
        if (!$proposal) {
            return response()->json(['error' => 'Proposal not found.'], 404);
        }

        $reason = $request->reason ?: 'Terminated and marked failed by administrator.';
        $walletService = app(WalletService::class);

        try {
            $refundTxs = $walletService->refundEscrow($proposal, (float) $proposal->bid, 0, $reason);
            return response()->json([
                'message' => 'Proposal set to failed and full refund returned to client.',
                'data' => $proposal->fresh(),
                'transactions' => $refundTxs,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
