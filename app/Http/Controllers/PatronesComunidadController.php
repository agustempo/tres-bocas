<?php

namespace App\Http\Controllers;

use App\Models\PatronComunidad;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PatronesComunidadController extends Controller
{
    /**
     * Store a new community-proposed schedule.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'muelle_id'            => ['nullable', 'exists:muelles,id'],
            'muelle_comunidad_id'  => ['nullable', 'exists:muelles_comunidad,id'],
            'empresa'              => ['nullable', 'string', 'in:Interisleña,Lineas Delta,Jilguero'],
            'hora_referencia'      => ['required', 'date_format:H:i'],
            'ventana_min'          => ['required', 'integer', 'in:15,20,30'],
            'recurrencia'          => ['required', 'in:diario,lv,sabado,domingo,fds,unico'],
            'fecha_unica'          => ['nullable', 'date', 'required_if:recurrencia,unico'],
            'sentido'              => ['required', 'in:ida,vuelta'],
        ]);

        // At least one departure dock must be set
        if (empty($data['muelle_id']) && empty($data['muelle_comunidad_id'])) {
            return back()->withErrors(['muelle_id' => __('schedule.error_dock_required')]);
        }

        // Auto-compute destino from sentido — no need to ask the user
        if ($data['sentido'] === 'vuelta') {
            $data['destino'] = 'Tigre';
        } else {
            $muelle = \App\Models\Muelle::find($data['muelle_id']);
            $data['destino'] = $muelle?->nombre ?? 'Delta';
        }

        PatronComunidad::create(array_merge($data, ['user_id' => auth()->id()]));

        return back()->with('community_added', true);
    }

    /**
     * Register a 👍/👎 reaction on a community schedule.
     * 👍 counts as a confirmation toward verification.
     */
    public function reaccionar(Request $request, PatronComunidad $patron): JsonResponse
    {
        $tipo = $request->input('tipo');
        if (!in_array($tipo, ['positivo', 'negativo'])) {
            return response()->json(['ok' => false], 422);
        }

        $sessionKey = "comunidad_reaction_{$patron->id}";
        if (session()->has($sessionKey)) {
            return response()->json(['ok' => true]);
        }

        session([$sessionKey => $tipo]);

        if ($tipo === 'positivo') {
            $patron->confirmar();
            $patron->refresh();
        }

        return response()->json([
            'ok'            => true,
            'verificado'    => $patron->verificado,
            'confirmaciones'=> $patron->confirmaciones,
        ]);
    }
}
