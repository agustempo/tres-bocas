<?php

namespace App\Http\Controllers;

use App\Models\MuelleComunidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MuellesComunidadController extends Controller
{
    /**
     * Store a new community-proposed dock.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'     => ['required', 'string', 'max:100'],
            'zona'       => ['nullable', 'string', 'max:100'],
            'referencia' => ['nullable', 'string', 'max:200'],
        ]);

        MuelleComunidad::create(array_merge($data, ['user_id' => auth()->id()]));

        return back()->with('dock_added', true);
    }
}
