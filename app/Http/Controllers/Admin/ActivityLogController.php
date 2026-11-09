<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.activity.index', [
            'logs' => AdminActivityLog::query()->with('user:id,name,email')
                ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', addcslashes((string) $a, '%_\\').'%'))
                ->latest('id')->paginate(50)->withQueryString(),
        ]);
    }
}
