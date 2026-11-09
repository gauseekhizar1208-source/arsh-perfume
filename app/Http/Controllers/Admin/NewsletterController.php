<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(): View
    {
        return view('admin.newsletter.index', [
            'subscribers' => NewsletterSubscriber::query()->latest('id')->paginate(50),
            'activeCount' => NewsletterSubscriber::query()->active()->count(),
        ]);
    }

    /** Only subscribers who have not unsubscribed, with their consent timestamp. */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'consent_given_at']);
            NewsletterSubscriber::query()->active()->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [$r->email, $r->consent_given_at]);
                }
            });
            fclose($out);
        }, 'arsh-newsletter-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}
