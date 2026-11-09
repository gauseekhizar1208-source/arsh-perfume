<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit('login', 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        // Session fixation protection: new session id after login. Keep the guest cart and checkout intent.
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $cart->mergeGuestCartInto($request->user());

        // Admins go straight to the admin dashboard.
        if ((int) $request->user()->role_id === 1) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}