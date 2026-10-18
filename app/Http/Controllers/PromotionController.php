<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function index()
    {
        $promotions = Promotion::with(['category', 'product', 'creator'])->latest()->paginate(15);
        return view('promotions.index', compact('promotions'));
    }

    public function create()
    {
        $categories = Category::all();
        $products = Product::active()->orderBy('name')->get();
        return view('promotions.create', compact('categories', 'products'));
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
            'product_id' => 'nullable|exists:products,id',
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

    public function edit(Promotion $promotion)
    {
        $categories = Category::all();
        $products = Product::active()->orderBy('name')->get();
        return view('promotions.edit', compact('promotion', 'categories', 'products'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($promotion->id)],
            'discount_percentage' => 'required|numeric|min:0.5|max:90',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'category_id' => 'nullable|exists:categories,id',
            'product_id' => 'nullable|exists:products,id',
            'min_spend' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $oldValues = $promotion->toArray();
        $promotion->update($validated);

        AuditService::log(
            action: 'promotion.updated',
            auditable: $promotion,
            oldValues: $oldValues,
            newValues: $validated,
            reason: "Updated promotion {$promotion->name}"
        );

        return redirect()->route('promotions.index')->with('success', "Promotion {$promotion->name} updated successfully.");
    }

    public function destroy(Promotion $promotion)
    {
        $name = $promotion->name;
        $oldValues = $promotion->toArray();
        $promotion->delete();

        AuditService::log(
            action: 'promotion.deleted',
            auditable: $promotion,
            oldValues: $oldValues,
            newValues: null,
            reason: "Deleted promotion {$name}"
        );

        return redirect()->route('promotions.index')->with('success', "Promotion {$name} deleted successfully.");
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
