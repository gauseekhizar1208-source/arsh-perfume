<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(): View
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::query()->latest('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.coupons.form', $this->formData(new Coupon(['type' => 'percent', 'is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $coupon = Coupon::query()->create($this->validated($request));
        $this->syncRestrictions($coupon, $request);
        ActivityLogger::log('coupon.created', $coupon, "Created coupon {$coupon->code}");

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.form', $this->formData($coupon));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));
        $this->syncRestrictions($coupon, $request);
        ActivityLogger::log('coupon.updated', $coupon, "Updated coupon {$coupon->code}");

        return redirect()->route('admin.coupons.index')->with('status', 'Coupon saved.');
    }

    /** Coupons that were ever used are deactivated, not deleted (usage history must stay intact). */
    public function destroy(Coupon $coupon): RedirectResponse
    {
        if ($coupon->usages()->exists()) {
            $coupon->update(['is_active' => false]);

            return back()->with('status', 'Coupon has been used, so it was deactivated instead of deleted.');
        }

        ActivityLogger::log('coupon.deleted', null, "Deleted coupon {$coupon->code}");
        $coupon->delete();

        return back()->with('status', 'Coupon deleted.');
    }

    protected function validated(Request $request, ?Coupon $existing = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-]+$/', Rule::unique('coupons', 'code')->ignore($existing?->id)],
            'description' => ['nullable', 'string', 'max:200'],
            'type' => ['required', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'max_discount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['type'] === Coupon::TYPE_PERCENT && $data['value'] > 100) {
            abort(422, 'A percentage discount cannot exceed 100.');
        }
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['code'] = Coupon::normalise($data['code']);

        return $data;
    }

    protected function syncRestrictions(Coupon $coupon, Request $request): void
    {
        $productIds = array_filter((array) $request->input('product_ids', []));
        $categoryIds = array_filter((array) $request->input('category_ids', []));
        $coupon->products()->sync(Product::query()->whereIn('id', $productIds)->pluck('id'));
        $coupon->categories()->sync(Category::query()->whereIn('id', $categoryIds)->pluck('id'));
    }

    protected function formData(Coupon $coupon): array
    {
        return [
            'coupon' => $coupon,
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->ordered()->get(['id', 'name']),
            'selectedProducts' => $coupon->exists ? $coupon->products()->pluck('products.id')->all() : [],
            'selectedCategories' => $coupon->exists ? $coupon->categories()->pluck('categories.id')->all() : [],
        ];
    }
}
