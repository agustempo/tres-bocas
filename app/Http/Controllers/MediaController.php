<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorize('update', $listing);

        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $path = $request->file('image')->store('listings', 'public');

        $listing->media()->create([
            'path' => $path,
            'type' => 'image',
        ]);

        return redirect()->route('listings.show', $listing)
            ->with('success', 'Image uploaded successfully.');
    }
}
