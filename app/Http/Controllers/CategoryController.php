<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories and their subcategories.
     */
    public function index(): View
    {
        $categories = Category::with(['parent', 'subcategories'])
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);

        $rootCategories = Category::rootOnly()->active()->orderBy('name')->get();

        return view('categories.index', compact('categories', 'rootCategories'));
    }

    /**
     * Store a newly created category or subcategory.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:categories,code'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'code' => strtoupper(trim($validated['code'])),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::log(
            action: 'category.created',
            auditable: $category,
            newValues: $category->toArray(),
            reason: 'User created category/subcategory'
        );

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', Rule::unique('categories')->ignore($category->id)],
            'parent_id' => ['nullable', 'exists:categories,id', 'not_in:' . $category->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $old = $category->toArray();

        $category->update([
            'name' => $validated['name'],
            'code' => strtoupper(trim($validated['code'])),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditService::log(
            action: 'category.updated',
            auditable: $category,
            oldValues: $old,
            newValues: $category->toArray(),
            reason: 'User updated category'
        );

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Soft delete/deactivate category when doing so does not compromise historical records.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()->route('categories.index')->with('error', 'Cannot delete category with associated products. Deactivate it instead.');
        }

        $category->delete();

        AuditService::log(
            action: 'category.deleted',
            auditable: $category,
            reason: 'Category removed'
        );

        return redirect()->route('categories.index')->with('success', 'Category removed successfully.');
    }
}
