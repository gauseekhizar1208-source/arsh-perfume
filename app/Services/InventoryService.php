<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Every stock change goes through here: the variant row is locked (SELECT ... FOR UPDATE), the new level is
 * validated (never negative) and an inventory_movements row is written, all in one transaction.
 */
class InventoryService
{
    /**
     * @param  int  $delta  positive = stock in, negative = stock out
     *
     * @throws CheckoutException when the change would take stock below zero
     */
    public function adjust(
        ProductVariant $variant,
        int $delta,
        string $type,
        ?string $note = null,
        ?Model $reference = null,
        ?User $user = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($variant, $delta, $type, $note, $reference, $user) {
            $locked = ProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();

            $newStock = $locked->stock + $delta;
            if ($newStock < 0) {
                throw new CheckoutException("Not enough stock for {$locked->sku} (have {$locked->stock}, need ".abs($delta).').');
            }

            $locked->stock = $newStock;
            $locked->save();
            $variant->stock = $newStock; // keep the caller's instance fresh

            return InventoryMovement::query()->create([
                'product_variant_id' => $locked->id,
                'type' => $type,
                'quantity_change' => $delta,
                'stock_after' => $newStock,
                'reference_type' => $reference ? strtolower(class_basename($reference)) : null,
                'reference_id' => $reference?->getKey(),
                'user_id' => $user?->id,
                'note' => $note,
            ]);
        });
    }

    /** Set an absolute stock level (stock take / initial stock). */
    public function setStock(ProductVariant $variant, int $newStock, string $type = 'correction', ?string $note = null, ?User $user = null): ?InventoryMovement
    {
        return DB::transaction(function () use ($variant, $newStock, $type, $note, $user) {
            $locked = ProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();
            $delta = max(0, $newStock) - $locked->stock;

            if ($delta === 0) {
                return null;
            }

            return $this->adjust($locked, $delta, $type, $note, null, $user);
        });
    }

    /**
     * Put an order's stock back on the shelf. Idempotent: orders.stock_restored_at guarantees it happens once.
     * Must be called inside a transaction that already holds a lock on the order row.
     */
    public function restoreForOrder(Order $order, string $type = 'cancellation_restore', ?User $user = null): bool
    {
        if ($order->stock_restored_at) {
            return false;
        }

        foreach ($order->items()->get() as $item) {
            if ($item->product_variant_id && $variant = ProductVariant::query()->find($item->product_variant_id)) {
                $this->adjust($variant, $item->quantity, $type, "Order {$order->order_number}", $order, $user);
            }
            if ($item->product_id) {
                Product::withTrashed()->whereKey($item->product_id)
                    ->where('sales_count', '>=', $item->quantity)
                    ->decrement('sales_count', $item->quantity);
            }
        }

        $order->stock_restored_at = now();
        $order->save();

        return true;
    }
}
