<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /** Double opt-in is not sent yet: consent is recorded explicitly (checkbox required) with time and IP. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'consent' => ['accepted'],
        ]);

        NewsletterSubscriber::query()->updateOrCreate(
            ['email' => strtolower($data['email'])],
            [
                'consent_given_at' => now(),
                'consent_ip' => $request->ip(),
                'unsubscribe_token' => Str::random(48),
                'unsubscribed_at' => null,
            ]
        );

        return back()->with('status', 'Thank you for subscribing!');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        NewsletterSubscriber::query()->where('unsubscribe_token', $token)->update(['unsubscribed_at' => now()]);

        return redirect()->route('home')->with('status', 'You have been unsubscribed.');
    }
}
