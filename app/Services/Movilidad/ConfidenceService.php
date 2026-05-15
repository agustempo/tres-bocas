<?php

namespace App\Services\Movilidad;

use App\Models\Avistaje;
use App\Models\Patron;

/**
 * Calcula la confianza de un patrón horario sin modificar datos estructurales.
 *
 * La confianza combina:
 *  - Fuente del horario (oficial > comunidad > estimado)
 *  - Días desde la última validación admin
 *  - Avistajes de hoy: confirmaciones vs. contradicciones
 *
 * Nunca persiste datos — solo lee y retorna.
 */
class ConfidenceService
{
    /**
     * @return array{score:int, nivel:string, color:string, label:string, ok:int, mal:int, total:int}
     */
    public function compute(Patron $patron, ?array $avistajesHoy = null): array
    {
        // Si ya tenemos conteos pre-calculados (vía withCount), úsalos
        $ok  = $avistajesHoy['ok']  ?? $patron->votos_ok  ?? 0;
        $mal = $avistajesHoy['mal'] ?? $patron->votos_mal ?? 0;

        // Fallback: query directa si no hay datos
        if ($avistajesHoy === null && ! isset($patron->votos_ok)) {
            $avistajes = Avistaje::where('patron_id', $patron->id)
                ->whereDate('hora_evento', today())
                ->get(['tipo']);
            $ok  = $avistajes->whereIn('tipo', ['paso', 'embarco'])->count();
            $mal = $avistajes->whereIn('tipo', ['cancelado', 'no_paro'])->count();
        }

        $total = $ok + $mal;

        // Score base según fuente
        $base = match($patron->fuente ?? 'estimado') {
            'oficial'   => 75,
            'comunidad' => 58,
            default     => 35,
        };

        // Penalidad por antigüedad de validación
        $diasSinValidar = $patron->validado_at
            ? (int) $patron->validado_at->diffInDays(now())
            : 999;

        $limite = match($patron->fuente ?? 'estimado') {
            'oficial' => 60, 'comunidad' => 14, default => 30,
        };

        $stalenessRatio = min($diasSinValidar / max($limite, 1), 1.0);
        $base           = (int) ($base * (1 - $stalenessRatio * 0.35));

        // Señal comunitaria de hoy
        if ($total > 0) {
            $ratioOk = $ok / $total;
            $score   = (int) ($base * 0.35 + $ratioOk * 100 * 0.65);
        } else {
            $score = $base;
        }

        $score = max(0, min(100, $score));

        // Estado semántico
        if ($total > 0 && $mal > $ok) {
            $nivel = 'contradictorio';
            $color = 'red';
            $label = 'Reportes contradictorios';
        } elseif ($ok > 0) {
            $nivel = 'confirmado';
            $color = 'green';
            $label = "Confirmado hoy ({$ok}×)";
        } elseif ($score >= 65) {
            $nivel = 'confiable';
            $color = 'teal';
            $label = 'Horario confiable';
        } elseif ($score >= 35) {
            $nivel = 'moderado';
            $color = 'yellow';
            $label = 'Sin confirmaciones recientes';
        } else {
            $nivel = 'incierto';
            $color = 'gray';
            $label = 'Estimado — sin datos';
        }

        return compact('score', 'nivel', 'color', 'label', 'ok', 'mal', 'total');
    }

    /**
     * Computa en batch para una colección de patrones.
     * Evita N+1 cargando todos los avistajes de hoy de una sola vez.
     *
     * @param \Illuminate\Support\Collection<Patron> $patrones
     * @return array<int, array>  keyed by patron id
     */
    public function computeBatch(\Illuminate\Support\Collection $patrones): array
    {
        $patronIds = $patrones->pluck('id')->toArray();

        $avistajesCounts = Avistaje::whereIn('patron_id', $patronIds)
            ->whereDate('hora_evento', today())
            ->selectRaw('patron_id,
                sum(case when tipo in (\'paso\',\'embarco\') then 1 else 0 end) as ok,
                sum(case when tipo in (\'cancelado\',\'no_paro\') then 1 else 0 end) as mal')
            ->groupBy('patron_id')
            ->get()
            ->keyBy('patron_id');

        $results = [];
        foreach ($patrones as $patron) {
            $counts = $avistajesCounts->get($patron->id);
            $results[$patron->id] = $this->compute($patron, $counts
                ? ['ok' => (int)$counts->ok, 'mal' => (int)$counts->mal]
                : ['ok' => 0, 'mal' => 0]
            );
        }

        return $results;
    }
}
