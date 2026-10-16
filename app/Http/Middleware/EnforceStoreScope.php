<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceStoreScope
{
    /**
     * Handle an incoming request.
     * Prevents Store Managers, Cashiers, and Staff from viewing/modifying unauthorized stores.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super Admin bypasses store scoping to manage all stores centrally
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Identify target store ID from route parameter, model binding, or query/body
        $storeId = null;
        $storeParam = $request->route('store');

        if ($storeParam instanceof Store) {
            $storeId = $storeParam->id;
        } elseif (is_numeric($storeParam)) {
            $storeId = (int) $storeParam;
        } elseif ($request->has('store_id')) {
            $storeId = (int) $request->input('store_id');
        } elseif ($request->route('store_id')) {
            $storeId = (int) $request->route('store_id');
        }

        if ($storeId !== null) {
            if (!$user->canAccessStore($storeId)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('messages.unauthorized_store_access')], 403);
                }
                abort(403, __('messages.unauthorized_store_access'));
            }
        }

        return $next($request);
    }
}
