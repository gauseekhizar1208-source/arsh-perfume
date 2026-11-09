<?php

namespace Tests\Feature;

use App\Exceptions\CheckoutException;
use App\Services\InventoryService;
use Tests\FeatureTestCase;

class InventoryTest extends FeatureTestCase
{
    public function test_adjust_changes_stock_and_writes_a_movement(): void
    {
        $variant = $this->sellableVariant(stock: 5);

        app(InventoryService::class)->adjust($variant, 3, 'restock');

        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id, 'type' => 'restock', 'quantity_change' => 3,
        ]);
    }

    public function test_stock_never_goes_negative(): void
    {
        $variant = $this->sellableVariant(stock: 2);

        $this->expectException(CheckoutException::class);
        try {
            app(InventoryService::class)->adjust($variant, -3, 'sale');
        } finally {
            $this->assertSame(2, $variant->fresh()->stock);
            $this->assertDatabaseCount('inventory_movements', 0);
        }
    }

    public function test_set_stock_records_only_the_difference(): void
    {
        $variant = $this->sellableVariant(stock: 5);

        app(InventoryService::class)->setStock($variant, 9, 'correction', 'stocktake');

        $this->assertSame(9, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id, 'quantity_change' => 4,
        ]);
    }
}
