<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Wallet;
use App\Notifications\ServicePurchasedNotification;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * List purchases for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->account_type === 'freelancer') {
            $purchases = Purchase::where('freelancer_id', $user->id)
                ->with(['service.price', 'service.images', 'service.category', 'client', 'dispute'])
                ->latest()
                ->get();
        } else {
            $purchases = Purchase::where('client_id', $user->id)
                ->with(['service.price', 'service.images', 'service.category', 'freelancer', 'dispute'])
                ->latest()
                ->get();
        }

        return response()->json(['data' => $purchases], 200);
    }

    /**
     * Client purchases a service, locking the price into escrow.
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'ends_at' => 'nullable|date|after:today',
        ]);

        $service = Service::with(['price', 'user'])->findOrFail($validated['service_id']);

        if ($service->user_id === $user->id) {
            return response()->json(['error' => 'You cannot purchase your own service.'], 400);
        }

        $price = $service->price ? (float)$service->price->value : 0.0;
        if ($price <= 0) {
            return response()->json(['error' => 'This service does not have a valid price.'], 400);
        }

        // Verify client wallet balance
        $wallet = $user->getOrCreateWallet();
        if ((float)$wallet->balance < $price) {
            $deficit = round($price - (float)$wallet->balance, 2);
            return response()->json([
                'error' => "Insufficient wallet balance. Required: \${$price}, Available: \${$wallet->balance}. Deficit: \${$deficit}",
                'code' => 'INSUFFICIENT_BALANCE',
                'required' => $price,
                'available' => (float)$wallet->balance,
                'deficit' => $deficit,
            ], 400);
        }

        // Calculate delivery deadline
        $startedAt = now();
        if (!empty($validated['ends_at'])) {
            $endsAt = new \DateTime($validated['ends_at']);
        } else {
            $deliveryDays = (int)($service->delivery_days ?: 3);
            $endsAt = (clone $startedAt)->add(new \DateInterval("P{$deliveryDays}D"));
        }

        $walletService = app(WalletService::class);

        try {
            $purchase = DB::transaction(function () use ($user, $service, $price, $startedAt, $endsAt, $walletService) {
                // 1. Create Purchase record
                $purchase = Purchase::create([
                    'client_id' => $user->id,
                    'freelancer_id' => $service->user_id,
                    'service_id' => $service->id,
                    'price' => (string)$price,
                    'status' => 'active',
                    'started_at' => $startedAt,
                    'ends_at' => $endsAt,
                ]);

                // 2. Lock escrow in double-entry ledger
                $walletService->lockEscrowForPurchase($user, $purchase);

                return $purchase;
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Notify freelancer that their service was purchased
        if ($service->user) {
            try {
                $service->user->notify(new ServicePurchasedNotification($purchase, $service, $user));
            } catch (\Throwable $e) {
                \Log::error('Failed to send ServicePurchasedNotification: ' . $e->getMessage());
            }
        }

        // Find or create chat between client and freelancer for seamless communication
        $chat = Chat::where(function ($q) use ($service) {
            $q->where('chattable_type', \App\Models\Service::class)
              ->where('chattable_id', $service->id);
        })->orWhere(function ($q) use ($user, $service) {
            $q->where('client_id', $user->id)
              ->where('freelancer_id', $service->user_id)
              ->where('service_id', $service->id);
        })->first();

        if (!$chat) {
            $chat = Chat::create([
                'client_id' => $user->id,
                'freelancer_id' => $service->user_id,
                'service_id' => $service->id,
                'chattable_type' => \App\Models\Service::class,
                'chattable_id' => $service->id,
                'tag' => 'service',
                'start_date' => $startedAt,
                'end_date' => $endsAt,
            ]);
        } else {
            $chat->end_date = $endsAt;
            $chat->save();
        }

        return response()->json([
            'message' => 'Service purchased successfully! Funds are secured in escrow.',
            'data' => $purchase->load(['service.price', 'service.images', 'service.category', 'freelancer', 'dispute']),
            'chat' => $chat,
        ], 201);
    }

    /**
     * Get details of a single purchase.
     */
    public function show($id): JsonResponse
    {
        $user = auth('api')->user();
        $purchase = Purchase::with(['service.price', 'service.images', 'service.category', 'freelancer', 'client', 'dispute'])
            ->where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('client_id', $user->id)->orWhere('freelancer_id', $user->id);
            })
            ->first();

        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found'], 404);
        }

        return response()->json(['data' => $purchase], 200);
    }

    /**
     * Client approves deliverable and marks service order as completed (releases escrow to freelancer).
     */
    public function complete(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $purchase = Purchase::with(['service', 'freelancer'])->where('id', $id)->first();
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found.'], 404);
        }

        if ($purchase->client_id !== $user->id) {
            return response()->json(['error' => 'Only the client who purchased this service can mark it as completed.'], 403);
        }

        if ($purchase->status === 'completed') {
            return response()->json(['error' => 'This purchase has already been completed.'], 400);
        }

        if ($purchase->status === 'disputed') {
            return response()->json(['error' => 'Cannot complete a purchase currently under administrative dispute.'], 400);
        }

        $walletService = app(WalletService::class);

        try {
            $releaseTx = $walletService->releaseEscrowForPurchase($purchase);

            return response()->json([
                'message' => 'Service order completed! Escrow funds released to freelancer.',
                'data' => $purchase->fresh(['service.price', 'service.images', 'service.category', 'freelancer', 'dispute']),
                'transaction' => $releaseTx,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Freelancer marks work as delivered.
     */
    public function deliver(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $purchase = Purchase::with(['service', 'client'])->where('id', $id)->first();
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found.'], 404);
        }

        if ($purchase->freelancer_id !== $user->id) {
            return response()->json(['error' => 'Only the seller can mark deliverables as submitted.'], 403);
        }

        if ($purchase->status === 'completed') {
            return response()->json(['error' => 'This order is already completed.'], 400);
        }

        if ($purchase->status === 'disputed') {
            return response()->json(['error' => 'Cannot change delivery status while under dispute.'], 400);
        }

        $purchase->status = 'delivered';
        $purchase->delivered_at = now();
        $purchase->save();

        return response()->json([
            'message' => 'Deliverables marked as submitted. The client has been notified to review.',
            'data' => $purchase->fresh(['service.price', 'service.images', 'service.category', 'client', 'dispute']),
        ], 200);
    }

    /**
     * Client extends the delivery deadline.
     */
    public function extend(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'ends_at' => 'required|date|after:today',
        ]);

        $purchase = Purchase::where('id', $id)->first();
        if (!$purchase) {
            return response()->json(['error' => 'Purchase not found.'], 404);
        }

        if ($purchase->client_id !== $user->id) {
            return response()->json(['error' => 'Only the client can extend the contract deadline.'], 403);
        }

        if ($purchase->status === 'completed' || $purchase->status === 'refunded') {
            return response()->json(['error' => 'Cannot extend an already concluded contract.'], 400);
        }

        $purchase->ends_at = $request->ends_at;
        $purchase->save();

        // Sync associated chat
        $chat = Chat::where('service_id', $purchase->service_id)
            ->where('client_id', $purchase->client_id)
            ->first();

        if ($chat) {
            $chat->end_date = $request->ends_at;
            $chat->save();
        }

        return response()->json([
            'message' => 'Contract deadline extended successfully.',
            'data' => $purchase->fresh(['service.price', 'service.images', 'service.category', 'freelancer', 'dispute']),
        ], 200);
    }
}
