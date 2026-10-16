@extends('layouts.app')

@section('title', __('messages.categories'))
@section('header', __('messages.categories'))

@section('content')
<div class="space-y-6" x-data="{ showModal: false, isEdit: false, formAction: '{{ route('categories.store') }}', catName: '', catCode: '', catParent: '', catDesc: '', catActive: true }">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ __('messages.categories') }}</h2>
            <p class="text-xs text-slate-500 mt-0.5">Category codes directly determine automatic product SKU generation (e.g. SH-000123)</p>
        </div>

        <button type="button" @click="isEdit = false; formAction = '{{ route('categories.store') }}'; catName = ''; catCode = ''; catParent = ''; catDesc = ''; catActive = true; showModal = true"
                class="inline-flex items-center gap-2 py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Create Category</span>
        </button>
    </div>

    <!-- Categories Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider font-bold">
                        <th class="py-3.5 px-4">Code</th>
                        <th class="py-3.5 px-4">Category Name</th>
                        <th class="py-3.5 px-4">Parent Category</th>
                        <th class="py-3.5 px-4">Subcategories</th>
                        <th class="py-3.5 px-4">Product Count</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="py-3.5 px-4 font-mono font-black text-indigo-600 dark:text-indigo-400 text-sm">
                                {{ $category->code }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $category->name }}
                                @if($category->description)
                                    <div class="text-[11px] font-normal text-slate-400">{{ $category->description }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                {{ $category->parent ? $category->parent->name : '— (Root Level)' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-500">
                                @if($category->subcategories->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($category->subcategories as $sub)
                                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold">{{ $sub->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 text-[11px]">None</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-800 dark:text-slate-200">
                                {{ $category->products_count }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($category->is_active)
                                    <span class="text-emerald-600 font-bold text-[11px] flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                    </span>
                                @else
                                    <span class="text-slate-400 font-bold text-[11px] flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="
                                        isEdit = true;
                                        formAction = '/categories/{{ $category->id }}';
                                        catName = '{{ addslashes($category->name) }}';
                                        catCode = '{{ $category->code }}';
                                        catParent = '{{ $category->parent_id }}';
                                        catDesc = '{{ addslashes($category->description ?? '') }}';
                                        catActive = {{ $category->is_active ? 'true' : 'false' }};
                                        showModal = true;
                                    " class="p-1 text-slate-500 hover:text-indigo-600" title="Edit Category">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>

                                    @if(auth()->user()->isSuperAdmin() && $category->products_count === 0)
                                        <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this category?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-500 hover:text-rose-600" title="Delete Category">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No categories created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $categories->links() }}
        </div>
    </div>

    <!-- Category Modal -->
    <div x-cloak x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl" @click.outside="showModal = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-base text-slate-900 dark:text-white" x-text="isEdit ? 'Edit Category' : 'Create New Category'"></h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form :action="formAction" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category Name *</label>
                    <input type="text" name="name" x-model="catName" required placeholder="e.g. Panjabi Collection"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Category Code * <span class="text-slate-400 font-normal">(2-4 letters, e.g. PJ, SH, PT)</span>
                    </label>
                    <input type="text" name="code" x-model="catCode" required maxlength="10" placeholder="e.g. PJ"
                           class="w-full text-xs font-mono font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Parent Category (Optional for subcategory)</label>
                    <select name="parent_id" x-model="catParent" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3">
                        <option value="">-- None (Root Category) --</option>
                        @foreach($rootCategories as $rc)
                            <option value="{{ $rc->id }}">{{ $rc->name }} ({{ $rc->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" x-model="catDesc" rows="2" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 py-2.5 px-3"></textarea>
                </div>

                <label class="flex items-center gap-2 cursor-pointer pt-1">
                    <input type="checkbox" name="is_active" value="1" x-model="catActive" class="rounded text-indigo-600 w-4 h-4">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Active</span>
                </label>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showModal = false" class="py-2 px-4 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="py-2 px-5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/30">Save Category</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
