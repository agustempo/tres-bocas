<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Muelle;
use App\Models\MuelleComunidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * GET /search?q=...
     * Returns up to 4 docks + 4 listings. Public endpoint, no auth required.
     */
    public function index(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['docks' => [], 'listings' => []]);
        }

        $like = "%{$q}%";

        // ── Official muelles ─────────────────────────────────────────────────
        $oficial = Muelle::where('activo', true)
            ->where(function ($query) use ($like) {
                $query->where('nombre', 'like', $like)
                      ->orWhere('zona', 'like', $like)
                      ->orWhere('descripcion', 'like', $like);
            })
            ->orderBy('orden')
            ->orderBy('nombre')
            ->limit(4)
            ->get(['id', 'nombre', 'zona', 'slug'])
            ->map(fn ($m) => [
                'id'        => $m->id,
                'name'      => $m->nombre,
                'detail'    => $m->zona,
                'url'       => route('movilidad.muelles.show', $m->slug),
                'type'      => 'dock',
                'verified'  => true,
                'community' => false,
            ]);

        // ── Community muelles (verified only) ────────────────────────────────
        $remaining = max(0, 4 - $oficial->count());
        $comunidad = collect();

        if ($remaining > 0) {
            $comunidad = MuelleComunidad::where('verificado', true)
                ->where(function ($query) use ($like) {
                    $query->where('nombre', 'like', $like)
                          ->orWhere('zona', 'like', $like)
                          ->orWhere('referencia', 'like', $like);
                })
                ->orderBy('nombre')
                ->limit($remaining)
                ->get(['id', 'nombre', 'zona', 'referencia'])
                ->map(fn ($m) => [
                    'id'        => $m->id,
                    'name'      => $m->nombre,
                    'detail'    => $m->zona ?: $m->referencia,
                    'url'       => null,
                    'type'      => 'dock',
                    'verified'  => false,
                    'community' => true,
                ]);
        }

        $docks = $oficial->concat($comunidad)->values();

        // ── Listings ─────────────────────────────────────────────────────────
        $listings = Listing::with(['category', 'user'])
            ->where('status', 'published')
            ->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                      ->orWhere('description', 'like', $like)
                      ->orWhereHas('category', fn ($q) => $q->where('name', 'like', $like));
            })
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn ($l) => [
                'id'            => $l->id,
                'title'         => $l->title,
                'category'      => $l->category?->name,
                'category_slug' => $l->category?->slug,
                'detail'        => $l->user?->name,
                'url'           => route('listings.show', $l->id),
                'type'          => 'listing',
            ]);

        return response()->json([
            'docks'    => $docks,
            'listings' => $listings,
        ]);
    }
}
