<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'listing_id'     => 'required|exists:listings,id',
            'message'        => 'required|string|max:2000',
            'requested_date' => 'nullable|date|after:today',
        ]);

        $listing = Listing::findOrFail($validated['listing_id']);

        // Cannot inquire about your own listing
        if ($listing->user_id === $request->user()->id) {
            $msg = 'You cannot send an inquiry about your own listing.';
            return $request->wantsJson()
                ? response()->json(['message' => $msg], 422)
                : redirect()->back()->with('error', $msg);
        }

        Inquiry::create([
            'listing_id'     => $listing->id,
            'provider_id'    => $listing->user_id,
            'customer_id'    => $request->user()->id,
            'message'        => $validated['message'],
            'requested_date' => $validated['requested_date'] ?? null,
            'status'         => 'pending',
        ]);

        $msg = 'Your message has been sent. The provider will get back to you.';

        return $request->wantsJson()
            ? response()->json(['message' => $msg])
            : redirect()->back()->with('success', $msg);
    }

    public function updateStatus(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $this->authorize('manage', $inquiry);

        $request->validate(['status' => 'required|in:pending,completed']);

        $inquiry->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Inquiry updated.');
    }
}
