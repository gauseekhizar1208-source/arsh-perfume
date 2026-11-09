<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Services\ActivityLogger;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->validate(['stock' => ['nullable', 'in:low,out,all']])['stock'] ?? 'all';

        $variants = ProductVariant::query()->with('product:id,name,sku,status')
            ->whereHas('product')
            ->when($filter === 'low', fn ($q) => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'low_stock_threshold'))
            ->when($filter === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->orderBy('stock')->paginate(25)->withQueryString();

        return view('admin.inventory.index', ['variants' => $variants, 'filter' => $filter]);
    }

    public function movements(Request $request): View
    {
        return view('admin.inventory.movements', [
            'movements' => InventoryMovement::query()->with(['variant.product:id,name', 'user:id,name'])
                ->when($request->query('variant'), fn ($q, $v) => $q->where('product_variant_id', (int) $v))
                ->latest('id')->paginate(30)->withQueryString(),
        ]);
    }

    /** Manual adjustment: a reason is mandatory and the change is logged in inventory_movements. */
    public function adjust(Request $request, ProductVariant $variant, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:add,remove,set'],
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['required', 'string', 'max:200'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        try {
            if ($data['mode'] === 'set') {
                $inventory->setStock($variant, (int) $data['quantity'], 'correction', $data['reason'], $request->user());
            } else {
                $delta = $data['mode'] === 'add' ? (int) $data['quantity'] : -(int) $data['quantity'];
                $inventory->adjust($variant, $delta, 'adjustment', $data['reason'], null, $request->user());
            }
        } catch (CheckoutException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (isset($data['low_stock_threshold'])) {
            $variant->update(['low_stock_threshold' => (int) $data['low_stock_threshold']]);
        }

        ActivityLogger::log('inventory.adjusted', $variant, "Stock adjusted for {$variant->sku}: {$data['mode']} {$data['quantity']}", ['reason' => $data['reason']]);

        return back()->with('status', 'Stock updated.');
    }
}
