<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $asCustomer = $user->reservationsAsCustomer()
            ->with(['listing.media', 'provider'])
            ->latest()
            ->get();

        $asProvider = $user->reservationsAsProvider()
            ->with(['listing.media', 'customer'])
            ->latest()
            ->get();

        return view('reservations.my-reservations', compact('asCustomer', 'asProvider'));
    }

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $user = $request->user();

        // Cannot reserve your own listing
        if ($listing->user_id === $user->id) {
            return redirect()->route('listings.show', $listing)
                ->with('error', 'You cannot reserve your own listing.');
        }

        // Only published listings can be reserved
        if ($listing->status !== 'published') {
            return redirect()->route('listings.show', $listing)
                ->with('error', 'This listing is not available for reservation.');
        }

        $request->validate([
            'scheduled_at' => 'nullable|date|after:now',
            'notes'        => 'nullable|string|max:1000',
        ]);

        Reservation::create([
            'listing_id'   => $listing->id,
            'provider_id'  => $listing->user_id,
            'customer_id'  => $user->id,
            'status'       => 'pending',
            'scheduled_at' => $request->scheduled_at,
            'notes'        => $request->notes,
        ]);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation request sent. Waiting for the provider to confirm.');
    }

    public function confirm(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('manage', $reservation);

        abort_if($reservation->status !== 'pending', 422, 'Only pending reservations can be confirmed.');

        $reservation->update(['status' => 'confirmed']);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation confirmed.');
    }

    public function complete(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('manage', $reservation);

        abort_if($reservation->status !== 'confirmed', 422, 'Only confirmed reservations can be marked as completed.');

        $reservation->update(['status' => 'completed']);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation marked as completed.');
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorize('manage', $reservation);

        abort_if(
            in_array($reservation->status, ['completed', 'cancelled']),
            422,
            'This reservation cannot be cancelled.'
        );

        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('reservations.index')
            ->with('success', 'Reservation cancelled.');
    }
}
