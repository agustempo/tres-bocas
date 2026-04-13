<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $reviewer = $request->user();

        // Cannot review your own listing
        if ($listing->user_id === $reviewer->id) {
            return redirect()->route('listings.show', $listing)
                ->with('error', 'You cannot review your own listing.');
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
        ]);

        Review::create([
            ...$validated,
            'reviewer_id'      => $reviewer->id,
            'reviewed_user_id' => $listing->user_id,
            'listing_id'       => $listing->id,
            'approved'         => false,
        ]);

        return redirect()->route('listings.show', $listing)
            ->with('success', 'Review submitted and is awaiting approval.');
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('approve', $review);

        $review->update(['approved' => true]);

        return redirect()->back()->with('success', 'Review approved.');
    }
}
