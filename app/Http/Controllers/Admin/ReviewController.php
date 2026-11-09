<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', 'in:all,pending,approved,rejected']])['status'] ?? 'all';

        $reviews = Review::query()
            ->with(['product:id,name', 'user:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', ['reviews' => $reviews, 'status' => $status]);
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        $this->moderate($request, $review, Review::APPROVED);

        return back()->with('status', 'Review approved and now visible publicly.');
    }

    public function reject(Request $request, Review $review): RedirectResponse
    {
        $this->moderate($request, $review, Review::REJECTED);

        return back()->with('status', 'Review rejected.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        ActivityLogger::log('review.deleted', $review, "Deleted review #{$review->id}");
        $review->delete();

        return back()->with('status', 'Review deleted.');
    }

    protected function moderate(Request $request, Review $review, string $status): void
    {
        // status / moderated_by / moderated_at are not in the model's $fillable list on purpose
        // (customers must never be able to set them), so an admin-only change uses forceFill().
        $review->forceFill([
            'status' => $status,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ])->save();

        ActivityLogger::log("review.$status", $review, "Review #{$review->id} $status");
    }
}