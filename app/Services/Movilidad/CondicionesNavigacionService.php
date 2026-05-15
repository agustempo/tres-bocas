<?php

namespace App\Services\Movilidad;

use App\Services\TideService;
use App\Services\WeatherService;

class CondicionesNavigacionService
{
    const UMBRALES = [
        'canal_secundario' => ['marea_minima' => 0.70, 'viento_maximo' => 35],
        'rio_principal'    => ['marea_minima' => 0.40, 'viento_maximo' => 50],
        'default'          => ['marea_minima' => 0.60, 'viento_maximo' => 40],
    ];

    public function __construct(
        private TideService   $tideService,
        private WeatherService $weatherService,
    ) {}

    public function evaluarActual(string $tipoCanal = 'default'): array
    {
        $umbral = self::UMBRALES[$tipoCanal] ?? self::UMBRALES['default'];

        $tideData    = $this->tideService->getData();
        $weatherData = $this->weatherService->getData();

        $nivel  = $tideData['current']['level']   ?? null;
        $viento = $weatherData['wind']['speed']   ?? ($tideData['wind']['speed'] ?? null);

        $advertencias = [];
        $estado       = 'ok'; // ok | precaucion | riesgo

        if ($nivel !== null) {
            $nivelFloat = (float) str_replace(',', '.', $nivel);

            if ($nivelFloat < $umbral['marea_minima']) {
                $estado       = 'precaucion';
                $advertencias[] = __('movilidad.condicion_marea_baja', ['level' => number_format($nivelFloat, 2)]);
            }
        }

        if ($viento !== null) {
            $vientoInt = (int) $viento;

            if ($vientoInt > $umbral['viento_maximo']) {
                $estado       = 'precaucion';
                $advertencias[] = __('movilidad.condicion_viento_fuerte', ['speed' => $vientoInt]);
            }

            if ($vientoInt > $umbral['viento_maximo'] * 1.4) {
                $estado = 'riesgo';
            }
        }

        return [
            'estado'       => $estado,        // ok | precaucion | riesgo
            'advertencias' => $advertencias,
            'nivel_marea'  => $nivel,
            'viento_kmh'   => $viento,
            'tide_raw'     => $tideData,
            'weather_raw'  => $weatherData,
        ];
    }

    public function snapshotParaAvistaje(): array
    {
        $data = $this->evaluarActual();

        $weatherRaw = $data['weather_raw'] ?? [];

        return [
            'nivel_marea'    => $data['nivel_marea'],
            'viento_kmh'     => $data['viento_kmh'] ? (int) $data['viento_kmh'] : null,
            'condicion_clima' => $weatherRaw['condition'] ?? $weatherRaw['emoji'] ?? null,
        ];
    }
}
