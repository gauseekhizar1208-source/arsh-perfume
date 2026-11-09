<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index');
    }

    public function sales(Request $request, ReportService $reports): View|StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $rows = $reports->salesRows($from, $to);

        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['order_number', 'placed_at', 'status', 'payment_status', 'grand_total', 'refunded', 'net_revenue']);
                foreach ($rows as $o) {
                    fputcsv($out, [$o->order_number, $o->placed_at, $o->status, $o->payment_status, $o->grand_total, $o->refunded_amount, $o->net_revenue]);
                }
                fclose($out);
            }, 'arsh-sales-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('admin.reports.sales', [
            'rows' => $rows,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'revenue' => $reports->revenue($from, $to),
            'bestsellers' => $reports->bestSellers(10, $from, $to),
        ]);
    }

    public function inventory(Request $request, ReportService $reports): View|StreamedResponse
    {
        $rows = $reports->inventoryRows();

        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['product', 'product_status', 'variant', 'sku', 'stock', 'low_stock_threshold', 'price', 'sale_price']);
                foreach ($rows as $v) {
                    fputcsv($out, [$v->product?->name, $v->product?->status, $v->name, $v->sku, $v->stock, $v->low_stock_threshold, $v->price, $v->sale_price]);
                }
                fclose($out);
            }, 'arsh-inventory-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('admin.reports.inventory', ['rows' => $rows]);
    }

    public function customers(Request $request, ReportService $reports): View|StreamedResponse
    {
        $rows = $reports->customerRows();

        if ($request->query('format') === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['name', 'email', 'phone', 'orders', 'joined']);
                foreach ($rows as $c) {
                    fputcsv($out, [$c->name, $c->email, $c->phone, $c->orders_count, $c->created_at]);
                }
                fclose($out);
            }, 'arsh-customers-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('admin.reports.customers', ['rows' => $rows]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected function range(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [Carbon::parse($data['from'] ?? now()->startOfMonth()), Carbon::parse($data['to'] ?? now())];
    }
}
