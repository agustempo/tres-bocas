<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Review;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $totalListings     = Listing::count();
            $publishedListings = Listing::where('status', 'published')->count();
            $pendingReviews    = Review::where('approved', false)->count();
            $myListings        = null;
        } else {
            $totalListings     = $user->listings()->count();
            $publishedListings = $user->listings()->where('status', 'published')->count();
            $pendingReviews    = Review::whereHas('listing', fn ($q) => $q->where('user_id', $user->id))
                                       ->where('approved', false)
                                       ->count();
            $myListings        = $user->listings()->with('category')->latest()->get();
        }

        return view('dashboard', compact(
            'totalListings',
            'publishedListings',
            'pendingReviews',
            'myListings'
        ));
    }
}
