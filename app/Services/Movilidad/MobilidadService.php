<?php

namespace App\Services\Movilidad;

use App\Models\Avistaje;
use App\Models\Muelle;
use App\Models\Patron;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MobilidadService
{
    public function __construct(private FreshnessService $freshness) {}

    /**
     * Último avistaje fresco para un muelle + servicio.
     * Retorna null si no hay ninguno en las últimas 4 horas.
     */
    public function getUltimoAvistaje(int $muelleId, int $servicioId, ?string $sentido = null): ?Avistaje
    {
        $corte = now()->subMinutes(FreshnessService::VIEJO_MINUTOS);

        return Avistaje::where('muelle_id', $muelleId)
            ->where('servicio_id', $servicioId)
            ->where('hora_evento', '>=', $corte)
            ->when($sentido, fn ($q) => $q->where('sentido', $sentido))
            ->orderBy('hora_evento', 'desc')
            ->first();
    }

    /**
     * Estimación del próximo paso basada en patrones del día de hoy.
     * Solo calcula para la próxima hora razonable, no para mañana.
     */
    public function estimarProximoPaso(int $muelleId, int $servicioId): ?array
    {
        $ahora = now();

        $patrones = Patron::where('muelle_id', $muelleId)
            ->where('servicio_id', $servicioId)
            ->activo()
            ->publico()
            ->paraHoy()
            ->orderBy('hora_referencia')
            ->get();

        if ($patrones->isEmpty()) {
            return null;
        }

        // Buscar el próximo patrón después de ahora
        foreach ($patrones as $patron) {
            $horaPatron = Carbon::today()->setTimeFromTimeString($patron->hora_referencia);
            $ventanaInicio = $horaPatron->copy()->subMinutes($patron->ventana_min);

            if ($ventanaInicio->greaterThan($ahora)) {
                return [
                    'hora'      => $horaPatron,
                    'ventana'   => $patron->ventana_min,
                    'confianza' => 'media',
                    'base'      => 'patron',
                    'patron'    => $patron,
                    'label'     => __('movilidad.patron_proximo_estimado') . ': ' . $patron->horaConVentana(),
                ];
            }
        }

        return null;
    }

    /**
     * Resumen completo de un muelle para la vista.
     * Devuelve servicios con su último avistaje y próxima estimación.
     */
    public function getResumenMuelle(Muelle $muelle): array
    {
        $tigreMuelle = Muelle::where('slug', 'tigre')->first();
        $esTigre     = $tigreMuelle?->id === $muelle->id;
        $diaHoy      = now()->dayOfWeek; // 0=Dom, 1=Lun ... 6=Sab
        $tipoDiaHoy  = in_array($diaHoy, [1, 2, 3, 4, 5]) ? 'lv' : ($diaHoy === 6 ? 'sabado' : 'domingo');

        $servicios = $muelle->servicios()
            ->where('servicios.activo', true)
            ->get();

        $resumen = [];

        foreach ($servicios as $servicio) {
            $ultimoAvistaje = $this->getUltimoAvistaje($muelle->id, $servicio->id);
            $proximo        = $this->estimarProximoPaso($muelle->id, $servicio->id);
            $freshness      = $ultimoAvistaje ? $this->freshness->compute($ultimoAvistaje) : null;

            // Todos los patrones del muelle (sin filtrar por día, solo publicos)
            $todos = Patron::where('muelle_id', $muelle->id)
                ->where('servicio_id', $servicio->id)
                ->activo()
                ->publico()
                ->withCount([
                    'avistajes as votos_ok'  => fn($q) => $q->whereIn('tipo', ['paso', 'embarco']),
                    'avistajes as votos_mal' => fn($q) => $q->whereIn('tipo', ['cancelado', 'no_paro']),
                ])
                ->orderBy('hora_referencia')
                ->get();

            $porTipo = [
                'lv'      => $todos->filter(fn($p) => $p->tipoDiaCalculado() === 'lv')->unique('hora_referencia')->sortBy('hora_referencia')->values(),
                'sabado'  => $todos->filter(fn($p) => $p->tipoDiaCalculado() === 'sabado')->sortBy('hora_referencia')->values(),
                'domingo' => $todos->filter(fn($p) => $p->tipoDiaCalculado() === 'domingo')->sortBy('hora_referencia')->values(),
            ];
            $patronesHoy = $todos->filter(fn($p) => $p->tipoDiaCalculado() === $tipoDiaHoy)->sortBy('hora_referencia')->values();

            // Horarios de salida desde Tigre agrupados por tipo de día
            $todosT = ($tigreMuelle && !$esTigre)
                ? Patron::where('muelle_id', $tigreMuelle->id)
                    ->where('servicio_id', $servicio->id)
                    ->activo()
                    ->publico()
                    ->where('sentido', 'ida')
                    ->orderBy('hora_referencia')
                    ->get()
                : collect();

            $tigrePorTipo = [
                'lv'      => $todosT->filter(fn($p) => $p->tipoDiaCalculado() === 'lv')->unique('hora_referencia')->sortBy('hora_referencia')->values(),
                'sabado'  => $todosT->filter(fn($p) => $p->tipoDiaCalculado() === 'sabado')->sortBy('hora_referencia')->values(),
                'domingo' => $todosT->filter(fn($p) => $p->tipoDiaCalculado() === 'domingo')->sortBy('hora_referencia')->values(),
            ];

            // Avistaje reciente en muelle adyacente de la misma ruta
            $ordenActual = DB::table('muelle_servicio')
                ->where('muelle_id', $muelle->id)
                ->where('servicio_id', $servicio->id)
                ->value('orden');

            $ordenesEnRuta = DB::table('muelle_servicio')
                ->where('servicio_id', $servicio->id)
                ->whereNotNull('orden')
                ->pluck('orden', 'muelle_id');

            $avistajeAdyacente = null;
            if ($ordenActual !== null) {
                $avistajeAdyacente = Avistaje::with('muelle')
                    ->where('servicio_id', $servicio->id)
                    ->where('muelle_id', '!=', $muelle->id)
                    ->where('hora_evento', '>=', now()->subMinutes(90))
                    ->whereIn('tipo', ['paso', 'embarco', 'no_paro'])
                    ->orderBy('hora_evento', 'desc')
                    ->get()
                    ->filter(fn($av) =>
                        $ordenesEnRuta->has($av->muelle_id) &&
                        abs($ordenesEnRuta->get($av->muelle_id) - $ordenActual) <= 3
                    )
                    ->first();
            }

            $resumen[] = [
                'servicio'            => $servicio,
                'ultimo_avistaje'     => $ultimoAvistaje,
                'freshness'           => $freshness,
                'proximo'             => $proximo,
                'patrones_hoy'        => $patronesHoy,
                'patrones_tigre_ida'  => $tigrePorTipo[$tipoDiaHoy] ?? collect(),
                'patrones_por_tipo'   => $porTipo,
                'tigre_por_tipo'      => $tigrePorTipo,
                'es_tigre'            => $esTigre,
                'tipo_dia_hoy'        => $tipoDiaHoy,
                'alertas_activas'     => $servicio->alertasActivas()->get(),
                'avistaje_adyacente'  => $avistajeAdyacente,
                'orden_actual'        => $ordenActual,
                'ordenes_en_ruta'     => $ordenesEnRuta,
            ];
        }

        return $resumen;
    }
}
