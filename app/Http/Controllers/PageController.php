<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    /** Policy/about pages. Unpublished pages (not yet reviewed by the owner) are not shown. */
    public function show(string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return view('pages.content', ['page' => $page]);
    }

    public function faq(): View
    {
        return view('pages.faq', ['faqs' => Faq::query()->active()->get()]);
    }
}
