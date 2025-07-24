<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SyncController extends Controller
{
    public function getGameState(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Update device activity
        $user->updateDeviceActivity();
        
        // Get synchronized game state
        $gameState = $user->syncGameStateAcrossDevices();
        
        return response()->json([
            'success' => true,
            'game_state' => $gameState,
            'multi_device_active' => $user->isActiveOnMultipleDevices(),
        ]);
    }

    public function syncDeviceState(Request $request): JsonResponse
    {
        $request->validate([
            'device_type' => 'string|in:mobile,tablet,desktop',
            'game_data' => 'array',
        ]);

        $user = Auth::user();
        
        // Update device activity and session data
        $user->updateDeviceActivity(
            $request->device_type,
            $request->ip(),
            $request->game_data ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Device state synchronized successfully',
            'last_sync' => $user->last_active_at->toISOString(),
        ]);
    }

    public function checkSyncStatus(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'sync_status' => [
                'last_active' => $user->last_active_at?->toISOString(),
                'device_type' => $user->last_device_type,
                'multi_device_active' => $user->isActiveOnMultipleDevices(),
                'requires_sync' => $user->last_active_at && 
                                  $user->last_active_at->diffInMinutes(now()) > 5,
            ],
        ]);
    }
}
