<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;
        $like = $q ? '%'.addcslashes($q, '%_\\').'%' : null;

        $customers = User::query()->customers()
            ->withCount('orders')
            ->when($like, fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.customers.index', ['customers' => $customers, 'q' => $q]);
    }

    /** Profile, addresses and order history. Passwords are never shown. */
    public function show(User $customer): View
    {
        abort_unless($customer->role?->name === 'customer', 404);

        return view('admin.customers.show', [
            'customer' => $customer->load('addresses'),
            'orders' => $customer->orders()->latest('placed_at')->paginate(10),
            'lifetime' => $customer->lifetimeTotal(),
        ]);
    }

    public function toggle(Request $request, User $customer): RedirectResponse
    {
        abort_unless($customer->role?->name === 'customer', 404);
        // This admin-only value is computed here; keep is_active guarded on public forms.
        $customer->forceFill(['is_active' => ! $customer->is_active])->save();
        ActivityLogger::log('customer.toggled', $customer, ($customer->is_active ? 'Reactivated' : 'Disabled')." account {$customer->email}");

        return back()->with('status', $customer->is_active ? 'Account reactivated.' : 'Account disabled. The customer is signed out on their next request.');
    }
}
