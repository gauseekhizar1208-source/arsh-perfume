<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reporting. REVENUE DEFINITION (identical everywhere, see Order::scopeRevenue):
 *   an order counts if payment_status is paid/refunded AND status is not cancelled/returned;
 *   revenue of an order = grand_total - refunded_amount; it is attributed to the order's placed_at date.
 */
class ReportService
{
    /** Sum of net revenue (decimal string) for orders placed in [from, to]. */
    public function revenue(?Carbon $from = null, ?Carbon $to = null): string
    {
        $query = Order::query()->revenue();
        if ($from) {
            $query->where('placed_at', '>=', $from->copy()->startOfDay());
        }
        if ($to) {
            $query->where('placed_at', '<=', $to->copy()->endOfDay());
        }

        return number_format((float) $query->sum(DB::raw('grand_total - refunded_amount')), 2, '.', '');
    }

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        $low = ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->active())
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->count();

        $out = ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->active())
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        return [
            'total_revenue' => $this->revenue(),
            'today_revenue' => $this->revenue(today(), today()),
            'month_revenue' => $this->revenue(now()->startOfMonth(), now()),
            'total_orders' => Order::query()->count(),
            'pending_orders' => Order::query()->where('status', 'pending')->count(),
            'completed_orders' => Order::query()->where('status', 'delivered')->count(),
            'cancelled_orders' => Order::query()->where('status', 'cancelled')->count(),
            'total_customers' => User::query()->customers()->count(),
            'active_products' => Product::query()->active()->count(),
            'low_stock_products' => $low,
            'out_of_stock_products' => $out,
            'recent_orders' => Order::query()->latest('id')->limit(8)->get(),
            'bestsellers' => $this->bestSellers(5),
        ];
    }

    /**
     * Daily revenue + order count between two dates (inclusive), zero-filled.
     *
     * @return array{labels: array<int,string>, revenue: array<int,float>, orders: array<int,int>}
     */
    public function salesSeries(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $rows = Order::query()->revenue()
            ->whereBetween('placed_at', [$from, $to])
            ->selectRaw('DATE(placed_at) as day, COUNT(*) as orders, SUM(grand_total - refunded_amount) as revenue')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($r) => (string) $r->day);

        $labels = $revenue = $orders = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d M');
            $revenue[] = round((float) ($rows[$key]->revenue ?? 0), 2);
            $orders[] = (int) ($rows[$key]->orders ?? 0);
        }

        return compact('labels', 'revenue', 'orders');
    }

    /** @return Collection<int, object{product_id:int, product_name:string, quantity:int, total:string}> */
    public function bestSellers(int $limit = 5, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.payment_status', ['paid', 'refunded'])
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->when($from, fn ($q) => $q->where('orders.placed_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($q) => $q->where('orders.placed_at', '<=', $to->copy()->endOfDay()))
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit($limit)
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as total')
            ->get();
    }

    /** Orders between two dates as flat rows (for the sales report and CSV export). */
    public function salesRows(Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->whereBetween('placed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('placed_at')
            ->get();
    }

    /** One row per variant with stock and thresholds, for the inventory report. */
    public function inventoryRows(): Collection
    {
        return ProductVariant::query()
            ->with('product:id,name,status,sku')
            ->orderBy('product_id')->orderBy('sort_order')
            ->get();
    }

    public function customerRows(): Collection
    {
        return User::query()->customers()
            ->withCount('orders')
            ->orderBy('name')
            ->get();
    }
}
