<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'] ?? now()->subDays(29));
        $to = Carbon::parse($data['to'] ?? now());

        return view('admin.dashboard', [
            'stats' => $reports->dashboard(),
            'chart' => $reports->salesSeries($from, $to),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
