<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search   = $request->q;
        $catSlug  = $request->category;

        if ($search || $catSlug) {
            // Filtered view: flat grid
            $query = Listing::with(['category', 'user', 'media'])
                ->where('status', 'published')
                ->latest();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($catSlug) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $catSlug));
            }

            $listings   = $query->paginate(12)->withQueryString();
            $categories = Category::orderBy('name')->get();

            return view('home', compact('listings', 'categories', 'search', 'catSlug'));
        }

        // Default: sections grouped by category, each with up to 8 listings
        $categories = Category::with(['listings' => function ($q) {
            $q->where('status', 'published')
              ->with(['user', 'media'])
              ->latest()
              ->limit(8);
        }])->get()->filter(fn ($c) => $c->listings->isNotEmpty())->values();

        return view('home', [
            'categories' => $categories,
            'listings'   => null,
            'search'     => null,
            'catSlug'    => null,
        ]);
    }
}
