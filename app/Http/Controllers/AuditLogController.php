<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a paginated audit log trail (Super Admin only).
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('user')->latest('id');

        if ($request->filled('action')) {
            $query->where('action', 'LIKE', "%{$request->action}%");
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'LIKE', "%{$search}%")
                    ->orWhere('reason', 'LIKE', "%{$search}%")
                    ->orWhere('ip_address', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get();

        return view('audit.index', compact('logs', 'users'));
    }
}
