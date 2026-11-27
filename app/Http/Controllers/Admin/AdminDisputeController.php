<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Chat;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminDisputeController extends Controller
{
    /**
     * List all disputes for admin review.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user || $user->account_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $query = Dispute::with(['proposal.job_post', 'purchase.service', 'client', 'freelancer', 'resolver'])->latest();

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $disputes = $query->get();

        return response()->json(['data' => $disputes], 200);
    }

    /**
     * View dispute details along with chat messages context.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user || $user->account_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $dispute = Dispute::with(['proposal.job_post', 'purchase.service', 'client', 'freelancer', 'resolver'])->find($id);
        if (!$dispute) {
            return response()->json(['error' => 'Dispute not found.'], 404);
        }

        // Fetch associated chat and messages for dispute evidence context
        $chat = null;
        if ($dispute->proposal) {
            $chat = Chat::where(function ($q) use ($dispute) {
                $q->where('job_post_id', $dispute->proposal->job_post_id)
                  ->orWhere(function ($q2) use ($dispute) {
                      $q2->where('chattable_id', $dispute->proposal->job_post_id)
                         ->where('chattable_type', 'like', '%JobPost%');
                  });
            })->with(['messages' => function ($m) {
                $m->latest()->take(50);
            }])->first();
        } elseif ($dispute->purchase) {
            $chat = Chat::where(function ($q) use ($dispute) {
                $q->where('service_id', $dispute->purchase->service_id)
                  ->orWhere(function ($q2) use ($dispute) {
                      $q2->where('chattable_id', $dispute->purchase->service_id)
                         ->where('chattable_type', 'like', '%Service%');
                  });
            })->with(['messages' => function ($m) {
                $m->latest()->take(50);
            }])->first();
        }

        return response()->json([
            'data' => $dispute,
            'chat' => $chat,
        ], 200);
    }

    /**
     * Resolve dispute (full refund to client, partial refund, or release to freelancer).
     */
    public function resolve(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user || $user->account_type !== 'admin') {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $request->validate([
            'resolution_type' => 'required|in:full_refund,partial_refund,release_freelancer',
            'client_refund_amount' => 'nullable|numeric|min:0',
            'freelancer_amount' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string',
        ]);

        $dispute = Dispute::with(['proposal.job_post.user', 'proposal.freelancer', 'purchase.service', 'purchase.client', 'purchase.freelancer'])->find($id);
        if (!$dispute) {
            return response()->json(['error' => 'Dispute not found.'], 404);
        }

        if (str_starts_with($dispute->status, 'resolved_')) {
            return response()->json(['error' => 'This dispute has already been resolved.'], 400);
        }

        $walletService = app(WalletService::class);
        $type = $request->resolution_type;
        $notes = $request->admin_notes ?: 'Resolved by administrator.';

        try {
            if ($dispute->proposal) {
                $proposal = $dispute->proposal;
                $bidAmount = (float) $proposal->bid;

                if ($type === 'full_refund') {
                    $walletService->refundEscrow($proposal, $bidAmount, 0.0, $notes);
                    $dispute->status = 'resolved_refund_client';
                    $dispute->client_refund_amount = $bidAmount;
                    $dispute->freelancer_amount = 0.00;
                } elseif ($type === 'partial_refund') {
                    $clientAmount = (float) $request->client_refund_amount;
                    $freelancerAmount = (float) $request->freelancer_amount;

                    if (round($clientAmount + $freelancerAmount, 2) > $bidAmount) {
                        return response()->json(['error' => "Total split (\${$clientAmount} + \${$freelancerAmount}) exceeds bid (\${$bidAmount})."], 422);
                    }

                    $walletService->refundEscrow($proposal, $clientAmount, $freelancerAmount, $notes);
                    $dispute->status = 'resolved_partial';
                    $dispute->client_refund_amount = $clientAmount;
                    $dispute->freelancer_amount = $freelancerAmount;
                } elseif ($type === 'release_freelancer') {
                    $walletService->releaseEscrow($proposal);
                    $dispute->status = 'resolved_release_freelancer';
                    $dispute->client_refund_amount = 0.00;
                    $dispute->freelancer_amount = $bidAmount;
                }
            } elseif ($dispute->purchase) {
                $purchase = $dispute->purchase;
                $priceAmount = (float) $purchase->price;

                if ($type === 'full_refund') {
                    $walletService->refundEscrowForPurchase($purchase, $priceAmount, 0.0, $notes);
                    $dispute->status =  'resolved_refund_client';
                    $dispute->client_refund_amount = $priceAmount;
                    $dispute->freelancer_amount = 0.00;
                } elseif ($type === 'partial_refund') {
                    $clientAmount = (float) $request->client_refund_amount;
                    $freelancerAmount = (float) $request->freelancer_amount;

                    if (round($clientAmount + $freelancerAmount, 2) > $priceAmount) {
                        return response()->json(['error' => "Total split (\${$clientAmount} + \${$freelancerAmount}) exceeds service price (\${$priceAmount})."], 422);
                    }

                    $walletService->refundEscrowForPurchase($purchase, $clientAmount, $freelancerAmount, $notes);
                    $dispute->status = 'resolved_partial';
                    $dispute->client_refund_amount = $clientAmount;
                    $dispute->freelancer_amount = $freelancerAmount;
                } elseif ($type === 'release_freelancer') {
                    $walletService->releaseEscrowForPurchase($purchase);
                    $dispute->status = 'resolved_release_freelancer';
                    $dispute->client_refund_amount = 0.00;
                    $dispute->freelancer_amount = $priceAmount;
                }
            }

            $dispute->admin_notes = $notes;
            $dispute->resolved_by = $user->id;
            $dispute->resolved_at = now();
            $dispute->save();

            return response()->json([
                'message' => 'Dispute resolved successfully.',
                'data' => $dispute->fresh(['proposal.job_post', 'client', 'freelancer', 'resolver']),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
