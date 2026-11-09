<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\GoogleSignInException;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\GoogleSignInService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleLoginController extends Controller
{
    public function redirect(Request $request, GoogleSignInService $google): RedirectResponse
    {
        if (! $google->isAvailable()) {
            return redirect()->route('login')->with('error', 'Google sign-in is currently unavailable. Please sign in with email.');
        }

        return $this->start($request, ['mode' => 'login']);
    }

    public function connect(Request $request, GoogleSignInService $google): RedirectResponse
    {
        if (! $google->isAvailable()) {
            return redirect()->route('account')->with('error', 'Google sign-in is currently unavailable.');
        }
        $request->validate(['google_password' => ['required', 'current_password']]);

        return $this->start($request, ['mode' => 'connect', 'user_id' => $request->user()->id]);
    }

    private function start(Request $request, array $context): RedirectResponse
    {
        $request->session()->put('google_oauth_context', $context + ['started_at' => time()]);

        // Socialite stores a random state in the session and verifies it on callback.
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, GoogleSignInService $google, CartService $cart): RedirectResponse
    {
        $context = $request->session()->pull('google_oauth_context');
        $destination = ($context['mode'] ?? null) === 'connect' && $request->user() ? 'account' : 'login';
        if (! $google->isAvailable() || ! is_array($context) || time() - ($context['started_at'] ?? 0) > 600) {
            $request->session()->forget('state');

            return redirect()->route($destination)->with('error', 'Google sign-in has expired or is unavailable. Please try again.');
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            $request->session()->forget('state');

            return redirect()->route($destination)->with('error', 'Google sign-in was not completed. You can try again.');
        }
        $connecting = ($context['mode'] ?? null) === 'connect';
        if (($connecting && (! $request->user() || $request->user()->id !== ($context['user_id'] ?? null))) || (! $connecting && $request->user())) {
            $request->session()->forget('state');

            return redirect()->route($request->user() ? 'account' : 'login')->with('error', 'Your session changed during Google sign-in. Please try again.');
        }
        try {
            $profile = Socialite::driver('google')->user();
            $user = $google->resolveUser($profile, $connecting ? $request->user() : null);
        } catch (InvalidStateException $e) {
            return redirect()->route($destination)->with('error', 'Google sign-in has expired. Please try again in the same browser.');
        } catch (GoogleSignInException $e) {
            return redirect()->route($destination)->with('error', $e->getMessage());
        } catch (Throwable $e) {
            // Never put OAuth codes, access tokens or the client secret in customer errors or logs.
            Log::warning('Google sign-in could not be completed.', ['exception_type' => get_class($e)]);

            return redirect()->route($destination)->with('error', 'Google sign-in could not be completed. Please try again or use your email and password.');
        }
        if ($connecting) {
            $request->session()->regenerate();

            return redirect()->route('account')->with('status', 'Google is connected. You can now use Continue with Google to sign in.');
        }
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $cart->mergeGuestCartInto($user);

        return $user->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->intended(route('home'));
    }
}
