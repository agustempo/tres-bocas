<?php

namespace App\Http\Controllers\Movilidad;

use App\Http\Controllers\Controller;
use App\Models\Muelle;
use App\Services\Movilidad\CondicionesNavigacionService;
use App\Services\Movilidad\MobilidadService;

class MuellesController extends Controller
{
    public function __construct(
        private MobilidadService $movilidad,
        private CondicionesNavigacionService $condiciones,
    ) {}

    public function index()
    {
        $muelles = Muelle::activo()
            ->with(['servicios' => fn ($q) => $q->where('servicios.activo', true)])
            ->orderBy('orden')
            ->get()
            ->groupBy('zona');

        return view('movilidad.index', compact('muelles'));
    }

    public function show(string $slug)
    {
        $muelle = Muelle::where('slug', $slug)->where('activo', true)->firstOrFail();

        $resumen    = $this->movilidad->getResumenMuelle($muelle);
        $condicionesActuales = $this->condiciones->evaluarActual($muelle->tipo_canal);

        // Historial reciente: los últimos 20 avistajes de las últimas 24hs
        $historial = $muelle->avistajes()
            ->with(['servicio', 'user'])
            ->where('hora_evento', '>=', now()->subHours(24))
            ->orderBy('hora_evento', 'desc')
            ->limit(20)
            ->get();

        return view('movilidad.muelles.show', compact('muelle', 'resumen', 'condicionesActuales', 'historial'));
    }
}
