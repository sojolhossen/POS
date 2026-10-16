<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectivityController extends Controller
{
    /**
     * Heartbeat ping endpoint to verify active server connectivity.
     */
    public function ping(Request $request): JsonResponse
    {
        // If client sends offline header or test simulation flag
        if ($request->header('X-Simulate-Offline') === 'true') {
            return response()->json([
                'status' => 'offline',
                'message' => __('messages.offline_banner_desc'),
            ], 503);
        }

        return response()->json([
            'status' => 'online',
            'timestamp' => now()->toISOString(),
            'server' => 'ClothingPOS-Central',
        ]);
    }
}
