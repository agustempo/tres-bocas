<?php

namespace App\Http\Controllers\Movilidad;

use App\Http\Controllers\Controller;
use App\Models\AlertaServicio;
use App\Models\Servicio;
use Illuminate\Http\Request;

class AlertasServicioController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'servicio_id'  => ['required', 'integer', 'exists:servicios,id'],
            'tipo'         => ['required', 'in:suspension,demora_general,ruta_alternativa'],
            'descripcion'  => ['required', 'string', 'max:500'],
            'valida_desde' => ['nullable', 'date'],
            'valida_hasta' => ['nullable', 'date', 'after:valida_desde'],
        ]);

        AlertaServicio::create([
            'servicio_id'  => $validated['servicio_id'],
            'tipo'         => $validated['tipo'],
            'descripcion'  => $validated['descripcion'],
            'valida_desde' => $validated['valida_desde'] ?? now(),
            'valida_hasta' => $validated['valida_hasta'] ?? null,
            'creada_por'   => auth()->id(),
        ]);

        return back()->with('success', __('movilidad.alerta_activa'));
    }

    public function destroy(AlertaServicio $alerta)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $alerta->update(['valida_hasta' => now()]);

        return back()->with('success', 'Alerta cerrada.');
    }
}
