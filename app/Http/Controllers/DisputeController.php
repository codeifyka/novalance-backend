<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DisputeController extends Controller
{
    /**
     * Client raises a dispute for a proposal after the ends_at date has arrived.
     */
    public function store(Request $request, $proposalId): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $proposal = Proposal::with(['job_post'])->find($proposalId);
        if (!$proposal) {
            return response()->json(['error' => 'Proposal not found.'], 404);
        }

        // Only the client (job owner) can raise a dispute
        if (!$proposal->job_post || $proposal->job_post->user_id !== $user->id) {
            return response()->json(['error' => 'Only the client who hired this proposal can raise a dispute.'], 403);
        }

        if ($proposal->status !== 'active') {
            return response()->json(['error' => "Disputes can only be opened for active proposals. Current status: {$proposal->status}."], 400);
        }

        // Check dispute timing rule: only allowed once ends_at date has arrived
        if (!$proposal->ends_at || now()->lessThan($proposal->ends_at)) {
            $formattedEndsAt = $proposal->ends_at ? $proposal->ends_at->format('Y-m-d H:i') : 'not set';
            return response()->json([
                'error' => "Disputes can only be raised once the contract end date has arrived ({$formattedEndsAt}).",
                'code' => 'END_DATE_NOT_REACHED',
                'ends_at' => $proposal->ends_at,
            ], 422);
        }

        // Check if dispute already exists
        $existingDispute = Dispute::where('proposal_id', $proposal->id)->first();
        if ($existingDispute) {
            return response()->json(['error' => 'A dispute has already been submitted for this proposal.', 'data' => $existingDispute], 400);
        }

        $dispute = Dispute::create([
            'proposal_id' => $proposal->id,
            'client_id' => $user->id,
            'freelancer_id' => $proposal->freelancer_id,
            'reason' => $request->reason,
            'status' => 'open',
        ]);

        $proposal->status = 'disputed';
        $proposal->save();

        return response()->json([
            'message' => 'Dispute raised successfully. An administrator will review your case.',
            'data' => $dispute->load(['proposal.job_post', 'client', 'freelancer']),
        ], 201);
    }

    /**
     * Get disputes for the authenticated user (client or freelancer).
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $disputes = Dispute::where('client_id', $user->id)
            ->orWhere('freelancer_id', $user->id)
            ->with(['proposal.job_post', 'client', 'freelancer', 'resolver'])
            ->latest()
            ->get();

        return response()->json(['data' => $disputes], 200);
    }
}
