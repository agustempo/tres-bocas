<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Listing::with(['category', 'user'])->latest();

        // Admins see all statuses; everyone else sees only published
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            $query->where('status', 'published');
        }

        // Full-text search across title, description, and category name
        if ($search = $request->q) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by category slug
        if ($categorySlug = $request->category) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        $listings   = $query->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('listings.index', compact('listings', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('listings.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'required|string',
            'contact'              => 'required|string|max:255',
            'category_id'          => 'required|exists:categories,id',
            'location.latitude'    => 'nullable|numeric|between:-90,90',
            'location.longitude'   => 'nullable|numeric|between:-180,180',
            'location.description' => 'nullable|string|max:255',
        ]);

        $listing = $request->user()->listings()->create([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'contact'     => $validated['contact'],
            'category_id' => $validated['category_id'],
            'status'      => 'draft',
        ]);

        $this->syncLocation($listing, $validated['location'] ?? []);

        return redirect()->route('listings.show', $listing)
            ->with('success', 'Listing created successfully. It is currently a draft — publish it when ready.');
    }

    public function show(Listing $listing): View
    {
        // Guests and non-owners can only view published listings
        if ($listing->status !== 'published') {
            $user = auth()->user();
            abort_unless(
                $user && ($user->isAdmin() || $user->id === $listing->user_id),
                404
            );
        }

        $listing->load(['category', 'user', 'media', 'location']);

        return view('listings.show', compact('listing'));
    }

    public function edit(Listing $listing): View
    {
        $this->authorize('update', $listing);

        $listing->load('location');
        $categories = Category::orderBy('name')->get();

        return view('listings.edit', compact('listing', 'categories'));
    }

    public function update(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'required|string',
            'contact'              => 'required|string|max:255',
            'category_id'          => 'required|exists:categories,id',
            'status'               => 'required|in:draft,published,archived',
            'location.latitude'    => 'nullable|numeric|between:-90,90',
            'location.longitude'   => 'nullable|numeric|between:-180,180',
            'location.description' => 'nullable|string|max:255',
        ]);

        $listing->update([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'contact'     => $validated['contact'],
            'category_id' => $validated['category_id'],
            'status'      => $validated['status'],
        ]);

        $this->syncLocation($listing, $validated['location'] ?? []);

        return redirect()->route('listings.show', $listing)
            ->with('success', 'Listing updated successfully.');
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        $this->authorize('delete', $listing);

        $listing->delete();

        return redirect()->route('listings.index')
            ->with('success', 'Listing deleted successfully.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function syncLocation(Listing $listing, array $locationData): void
    {
        $lat = $locationData['latitude']  ?? null;
        $lng = $locationData['longitude'] ?? null;

        if (filled($lat) && filled($lng)) {
            $listing->location()->updateOrCreate([], [
                'latitude'    => (float) $lat,
                'longitude'   => (float) $lng,
                'description' => filled($locationData['description'] ?? null)
                                    ? $locationData['description']
                                    : null,
            ]);
        } else {
            // Remove location if coordinates were cleared
            $listing->location()->delete();
        }
    }
}
