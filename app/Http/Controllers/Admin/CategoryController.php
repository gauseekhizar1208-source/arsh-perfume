<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->ordered()->withCount('products')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::query()->create($this->validated($request));
        ActivityLogger::log('category.created', $category, "Created category {$category->name}");

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));
        ActivityLogger::log('category.updated', $category, "Updated category {$category->name}");

        return redirect()->route('admin.categories.index')->with('status', 'Category saved.');
    }

    /** A category that still has products cannot be deleted: deactivate it instead. */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'This category still has products. Move them or deactivate the category instead.');
        }

        if ($category->image_path) {
            Storage::disk(config('arsh.media_disk'))->delete($category->image_path);
        }
        ActivityLogger::log('category.deleted', null, "Deleted category {$category->name}");
        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'Category deleted.');
    }

    public function toggle(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);
        ActivityLogger::log('category.toggled', $category, ($category->is_active ? 'Activated' : 'Deactivated')." category {$category->name}");

        return back()->with('status', $category->is_active ? 'Category activated.' : 'Category deactivated.');
    }

    /** Receives the full ordered list of category ids from the drag-and-drop UI. */
    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->validate(['order' => ['required', 'array', 'max:200'], 'order.*' => ['integer']])['order'];

        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                Category::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return back()->with('status', 'Order saved.');
    }

    protected function validated(Request $request, ?Category $existing = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9\-]+$/', Rule::unique('categories', 'slug')->ignore($existing?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->storeAs('categories', Str::random(32).'.'.$request->file('image')->guessExtension(), config('arsh.media_disk'));
        }
        unset($data['image']);

        return $data;
    }
}
