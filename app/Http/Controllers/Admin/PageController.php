<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::query()->orderBy('title')->get()]);
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    /** Content is HTML from the admin only; it is stored as entered and rendered through the rich-text whitelist. */
    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ]);
        $data['content'] = strip_tags((string) ($data['content'] ?? ''), '<p><h2><h3><ul><ol><li><strong><em><a><br>');

        // Editing a policy always un-reviews it: the owner must approve the new text before it is published.
        $page->update([...$data, 'is_published' => false, 'reviewed_at' => null, 'reviewed_by' => null]);
        ActivityLogger::log('page.updated', $page, "Edited page {$page->title}");

        return back()->with('status', 'Page saved as a draft. Review it, then publish.');
    }

    public function publish(Request $request, Page $page): RedirectResponse
    {
        abort_if(blank($page->content), 422, 'A page needs content before it can be published.');
        $page->update(['is_published' => true, 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id]);
        ActivityLogger::log('page.published', $page, "Published page {$page->title}");

        return back()->with('status', 'Page published.');
    }

    public function unpublish(Page $page): RedirectResponse
    {
        $page->update(['is_published' => false]);
        ActivityLogger::log('page.unpublished', $page, "Unpublished page {$page->title}");

        return back()->with('status', 'Page unpublished.');
    }

    public function preview(Page $page): View
    {
        return view('pages.content', ['page' => $page, 'preview' => true]);
    }
}
