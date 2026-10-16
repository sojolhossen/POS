<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Promotion;
use App\Services\AuditService;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index()
    {
        $promotions = Promotion::with(['category', 'creator'])->latest()->paginate(15);
        return view('promotions.index', compact('promotions'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('promotions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:promotions,code',
            'discount_percentage' => 'required|numeric|min:0.5|max:90',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'category_id' => 'nullable|exists:categories,id',
            'min_spend' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $promotion = Promotion::create($validated);

        AuditService::log(
            action: 'promotion.created',
            auditable: $promotion,
            oldValues: null,
            newValues: $validated,
            reason: "Created promotion {$promotion->name} ({$promotion->discount_percentage}%)"
        );

        return redirect()->route('promotions.index')->with('success', "Promotion {$promotion->name} created successfully.");
    }

    public function toggle(Promotion $promotion)
    {
        $oldState = $promotion->is_active;
        $promotion->update(['is_active' => !$oldState]);

        AuditService::log(
            action: 'promotion.toggled',
            auditable: $promotion,
            oldValues: ['is_active' => $oldState],
            newValues: ['is_active' => !$oldState],
            reason: "Toggled promotion {$promotion->name}"
        );

        return back()->with('success', "Promotion {$promotion->name} status updated.");
    }
}
