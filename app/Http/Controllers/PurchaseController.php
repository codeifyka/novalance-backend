<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Purchase;
use App\Models\Service;
use App\Notifications\ServicePurchasedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($user->account_type === 'freelancer') {
            $purchases = Purchase::where('freelancer_id', $user->id)
                ->with(['service.price', 'service.images', 'service.category', 'client'])
                ->latest()
                ->get();
        } else {
            $purchases = Purchase::where('client_id', $user->id)
                ->with(['service.price', 'service.images', 'service.category', 'freelancer'])
                ->latest()
                ->get();
        }

        return response()->json(['data' => $purchases], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        $service = Service::with(['price', 'user'])->findOrFail($validated['service_id']);

        if ($service->user_id === $user->id) {
            return response()->json(['error' => 'You cannot purchase your own service.'], 400);
        }

        $price = $service->price ? (string)$service->price->value : '0';

        $purchase = Purchase::create([
            'client_id' => $user->id,
            'freelancer_id' => $service->user_id,
            'service_id' => $service->id,
            'price' => $price,
            'status' => 'active',
        ]);

        // Notify freelancer that their service was purchased
        if ($service->user) {
            try {
                $service->user->notify(new ServicePurchasedNotification($purchase, $service, $user));
            } catch (\Throwable $e) {
                \Log::error('Failed to send ServicePurchasedNotification: ' . $e->getMessage());
            }
        }

        // Find or create chat between client and freelancer for seamless communication
        $chat = Chat::where('client_id', $user->id)
            ->where('freelancer_id', $service->user_id)
            ->first();

        if (!$chat) {
            $chat = Chat::create([
                'client_id' => $user->id,
                'freelancer_id' => $service->user_id,
                'service_id' => $service->id,
                'start_date' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Service purchased successfully!',
            'data' => $purchase->load(['service.price', 'service.images', 'service.category', 'freelancer']),
            'chat' => $chat,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $user = auth('api')->user();
        $purchase = Purchase::with(['service.price', 'service.images', 'service.category', 'freelancer', 'client'])
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
}
