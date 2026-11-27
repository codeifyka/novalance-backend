<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    private function isAdmin($user): bool
    {
        return $user && $user->account_type === 'admin';
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$this->isAdmin($user)) {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        return response()->json([
            'data' => [
                'free_service_enabled' => PlatformSetting::getValue('free_service_enabled', '0') === '1',
                'service_fee_percentage' => (float) PlatformSetting::getValue('service_fee_percentage', '10'),
            ],
        ], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$this->isAdmin($user)) {
            return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
        }

        $validated = $request->validate([
            'free_service_enabled' => 'required|boolean',
            'service_fee_percentage' => 'required|numeric|min:0|max:100',
        ]);

        PlatformSetting::setValue('free_service_enabled', $validated['free_service_enabled'] ? '1' : '0');
        PlatformSetting::setValue('service_fee_percentage', (string) $validated['service_fee_percentage']);

        return response()->json([
            'message' => 'Service settings updated successfully.',
            'data' => [
                'free_service_enabled' => (bool) $validated['free_service_enabled'],
                'service_fee_percentage' => (float) $validated['service_fee_percentage'],
            ],
        ], 200);
    }
}
