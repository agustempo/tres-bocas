<?php

namespace App\Http\Controllers;

use App\Models\Muelle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserPreferencesController extends Controller
{
    public function updateMuelle(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'muelle_id'           => ['nullable', 'integer', 'exists:muelles,id'],
            'nuevo_muelle_nombre' => ['nullable', 'string', 'max:100'],
        ]);

        $muelleId = null;

        if ($request->filled('nuevo_muelle_nombre')) {
            $nombre   = trim($request->string('nuevo_muelle_nombre'));
            $existing = Muelle::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->first();

            if ($existing) {
                $muelleId = $existing->id;
            } else {
                $slug     = Str::slug($nombre);
                $baseSlug = $slug;
                $i        = 2;
                while ($slug && Muelle::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $i++;
                }

                if ($slug) {
                    $muelleId = Muelle::create([
                        'nombre' => $nombre,
                        'slug'   => $slug,
                        'activo' => false,
                    ])->id;
                }
            }
        } elseif ($request->filled('muelle_id')) {
            $muelleId = (int) $request->input('muelle_id');
        }

        auth()->user()->update(['preferred_muelle_id' => $muelleId]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Muelle actualizado.');
    }
}
