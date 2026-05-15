<?php

namespace App\Http\Controllers\Movilidad;

use App\Http\Controllers\Controller;
use App\Models\Avistaje;
use App\Models\ConfirmacionAvistaje;
use App\Models\Muelle;
use App\Models\Servicio;
use App\Services\Movilidad\CondicionesNavigacionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvistajesController extends Controller
{
    public function __construct(private CondicionesNavigacionService $condiciones) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'muelle_id'    => ['required', 'integer', 'exists:muelles,id'],
            'servicio_id'  => ['required', 'integer', 'exists:servicios,id'],
            'patron_id'    => ['nullable', 'integer', 'exists:patrones,id'],
            'hora_exacta'  => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'hace_minutos' => ['nullable', 'integer', 'min:0', 'max:120'],
            'tipo'         => ['required', 'in:paso,embarco,no_paro,cancelado,demorado'],
            'sentido'      => ['nullable', 'in:ida,vuelta'],
            'notas'        => ['nullable', 'string', 'max:500'],
        ]);

        // Cuando viene de un pill, usamos la hora exacta del patrón como hora_evento,
        // ajustada hacia atrás si el usuario indicó "hace X minutos".
        $horaEvento = !empty($validated['hora_exacta'])
            ? Carbon::today()->setTimeFromTimeString($validated['hora_exacta'])
                ->subMinutes((int) ($validated['hace_minutos'] ?? 0))
            : now();

        $snapshot = $this->condiciones->snapshotParaAvistaje();

        Avistaje::create([
            'muelle_id'      => $validated['muelle_id'],
            'servicio_id'    => $validated['servicio_id'],
            'patron_id'      => $validated['patron_id']  ?? null,
            'user_id'        => Auth::id(),
            'tipo'           => $validated['tipo'],
            'hora_evento'    => $horaEvento,
            'sentido'        => $validated['sentido'] ?? null,
            'notas'          => $validated['notas'] ?? null,
            'nivel_marea'    => $snapshot['nivel_marea'],
            'viento_kmh'     => $snapshot['viento_kmh'],
            'condicion_clima'=> $snapshot['condicion_clima'],
            'confirmaciones' => 0,
        ]);

        $muelle = Muelle::find($validated['muelle_id']);

        return redirect()
            ->route('movilidad.muelles.show', $muelle->slug)
            ->with('success', __('movilidad.avistaje_reportar_accion'));
    }

    public function confirmar(Request $request, int $id)
    {
        $avistaje = Avistaje::findOrFail($id);

        // No puede confirmar su propio avistaje
        if ($avistaje->user_id === Auth::id()) {
            return back()->with('error', 'No podés confirmar tu propio reporte.');
        }

        $yaConfirmado = ConfirmacionAvistaje::where('avistaje_id', $id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($yaConfirmado) {
            return back();
        }

        ConfirmacionAvistaje::create([
            'avistaje_id' => $id,
            'user_id'     => Auth::id(),
            'created_at'  => now(),
        ]);

        $avistaje->increment('confirmaciones');

        $muelle = $avistaje->muelle;

        return redirect()
            ->route('movilidad.muelles.show', $muelle->slug)
            ->with('success', __('movilidad.avistaje_confirmar_accion'));
    }
}
