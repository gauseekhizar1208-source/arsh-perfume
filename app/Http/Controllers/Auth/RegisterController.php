<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]{7,20}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // Role is set here in code. It is never read from the request.
        // forceFill: role_id / is_active are deliberately not mass assignable (see User model).
        $user = new User;
        $user->forceFill([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role_id' => Role::idFor(Role::CUSTOMER),
            'is_active' => true,
        ])->save();

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $cart->mergeGuestCartInto($user);

        return redirect()->route('home')->with('status', 'Welcome to Arsh Perfume!');
    }
}
