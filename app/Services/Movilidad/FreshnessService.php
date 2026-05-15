<?php

namespace App\Services\Movilidad;

use App\Models\Avistaje;
use Carbon\Carbon;

class FreshnessService
{
    // Umbral en minutos para considerar un avistaje "actual"
    const FRESCO_MINUTOS   = 30;
    const MEDIO_MINUTOS    = 90;
    const VIEJO_MINUTOS    = 240;

    public function compute(Avistaje $avistaje): array
    {
        $minutos = (int) $avistaje->hora_evento->diffInMinutes(now());

        if ($minutos < 2) {
            $label  = __('movilidad.freshness_ahora');
            $nivel  = 'fresco';
            $color  = 'green';
        } elseif ($minutos < self::FRESCO_MINUTOS) {
            $label  = trans_choice('movilidad.freshness_minutos', $minutos, ['count' => $minutos]);
            $nivel  = 'fresco';
            $color  = 'green';
        } elseif ($minutos < self::MEDIO_MINUTOS) {
            $label  = __('movilidad.freshness_hora');
            $nivel  = 'medio';
            $color  = 'yellow';
        } elseif ($minutos < self::VIEJO_MINUTOS) {
            $horas = (int) ceil($minutos / 60);
            $label  = __('movilidad.freshness_horas', ['count' => $horas]);
            $nivel  = 'viejo';
            $color  = 'orange';
        } else {
            $label  = __('movilidad.freshness_viejo');
            $nivel  = 'expirado';
            $color  = 'gray';
        }

        return [
            'minutos'  => $minutos,
            'label'    => $label,
            'nivel'    => $nivel,    // fresco | medio | viejo | expirado
            'color'    => $color,    // green | yellow | orange | gray
            'es_actual'=> $minutos < self::VIEJO_MINUTOS,
        ];
    }

    public function esActual(Avistaje $avistaje): bool
    {
        return $avistaje->hora_evento->diffInMinutes(now()) < self::VIEJO_MINUTOS;
    }
}
