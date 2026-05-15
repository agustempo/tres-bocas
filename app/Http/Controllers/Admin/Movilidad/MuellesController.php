<?php

namespace App\Http\Controllers\Admin\Movilidad;

use App\Http\Controllers\Controller;
use App\Models\Muelle;
use App\Models\Patron;
use App\Models\Servicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MuellesController extends Controller
{
    public function index()
    {
        $muelles = Muelle::orderBy('orden')
            ->withCount([
                'patrones as patrones_total'  => fn($q) => $q->where('activo', true),
                'patrones as patrones_ocultos'=> fn($q) => $q->where('activo', true)->where('visibilidad', 'oculto'),
                'patrones as patrones_stale'  => fn($q) => $q->where('activo', true)->where(function ($q) {
                    $q->whereNull('validado_at')
                      ->orWhere('validado_at', '<', now()->subDays(14));
                }),
            ])
            ->get();

        return view('admin.movilidad.index', compact('muelles'));
    }

    public function preview(Muelle $muelle): JsonResponse
    {
        $servicios = $muelle->servicios()->where('servicios.activo', true)->get();

        $resultado = [];
        foreach ($servicios as $servicio) {
            $raw = Patron::where('muelle_id', $muelle->id)
                ->where('servicio_id', $servicio->id)
                ->where('activo', true)
                ->orderBy('hora_referencia')
                ->get();

            $por_dia = ['lv' => [], 'sabado' => [], 'domingo' => []];
            $seen    = [];

            foreach ($raw as $p) {
                $td   = $p->tipoDiaCalculado();
                $hora = substr($p->hora_referencia, 0, 5);
                $key  = $td . '|' . $p->sentido . '|' . $hora;

                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $por_dia[$td][] = [
                    'hora'   => $hora,
                    'sentido'=> $p->sentido,
                    'fuente' => $p->fuente ?? 'estimado',
                    'stale'  => $p->necesitaRevision(),
                ];
            }

            $resultado[] = [
                'id'     => $servicio->id,
                'nombre' => $servicio->nombre,
                'dias'   => $por_dia,
            ];
        }

        return response()->json($resultado);
    }

    public function editor(Muelle $muelle)
    {
        $servicios = $muelle->servicios()->where('servicios.activo', true)->get();

        // Para cada servicio, agrupar patrones por (tipo_dia_calculado, sentido, hora)
        // Retornamos un mapa service_id → grupos para Alpine.js
        $patronesPorServicio = [];
        foreach ($servicios as $servicio) {
            $raw = Patron::where('muelle_id', $muelle->id)
                ->where('servicio_id', $servicio->id)
                ->where('activo', true)
                ->orderBy('hora_referencia')
                ->get();

            // Agrupar: un grupo = misma (tipoDia, sentido, hora)
            // En datos viejos, L-V tiene 5 filas idénticas → las colapsamos en 1
            $grupos = [];
            foreach ($raw as $p) {
                $td  = $p->tipoDiaCalculado();
                $key = $td . '|' . $p->sentido . '|' . substr($p->hora_referencia, 0, 5);
                if (! isset($grupos[$key])) {
                    $grupos[$key] = [
                        'id'              => $p->id,
                        'ids'             => [],
                        'hora'            => substr($p->hora_referencia, 0, 5),
                        'sentido'         => $p->sentido,
                        'tipo_dia'        => $td,
                        'fuente'          => $p->fuente ?? 'estimado',
                        'visibilidad'     => $p->visibilidad ?? 'publico',
                        'validado_at'     => $p->validado_at?->toDateTimeString(),
                        'necesita_revision' => $p->necesitaRevision(),
                        'notas_admin'     => $p->notas_admin ?? '',
                    ];
                }
                $grupos[$key]['ids'][] = $p->id;
            }

            $patronesPorServicio[$servicio->id] = array_values($grupos);
        }

        // Agregar resumen de avistajes por grupo (para emoji de sentimiento)
        $allIds = collect($patronesPorServicio)
            ->flatten(1)
            ->pluck('ids')
            ->flatten()
            ->unique()
            ->values()
            ->all();

        $avistajeSummary = DB::table('avistajes')
            ->whereIn('patron_id', $allIds)
            ->selectRaw("patron_id,
                SUM(CASE WHEN tipo IN ('paso','embarco') THEN 1 ELSE 0 END) as ok,
                SUM(CASE WHEN tipo IN ('no_paro','cancelado','demorado') THEN 1 ELSE 0 END) as mal")
            ->groupBy('patron_id')
            ->get()
            ->keyBy('patron_id');

        foreach ($patronesPorServicio as $svcId => &$grupos) {
            foreach ($grupos as &$grupo) {
                $ok = 0; $mal = 0;
                foreach ($grupo['ids'] as $pid) {
                    if (isset($avistajeSummary[$pid])) {
                        $ok  += (int) $avistajeSummary[$pid]->ok;
                        $mal += (int) $avistajeSummary[$pid]->mal;
                    }
                }
                $grupo['avistajes_ok']  = $ok;
                $grupo['avistajes_mal'] = $mal;
            }
            unset($grupo);
        }
        unset($grupos);

        return view('admin.movilidad.editor', [
            'muelle'               => $muelle,
            'servicios'            => $servicios,
            'patronesPorServicio'  => $patronesPorServicio,
        ]);
    }
}
