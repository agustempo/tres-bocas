<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TideSummaryService
{
    const CACHE_KEY           = 'tide_llm_summary';
    const CACHE_KEY_DASHBOARD = 'tide_llm_dashboard';
    const CACHE_TTL = 65; // minutes — must exceed the hourly schedule interval
    const TZ        = 'America/Argentina/Buenos_Aires';

    // ─── Public API ──────────────────────────────────────────────────────────

    public function getCached(): ?string
    {
        return Cache::get(self::CACHE_KEY);
    }

    public function getCachedDashboard(): ?string
    {
        return Cache::get(self::CACHE_KEY_DASHBOARD);
    }

    /**
     * Short, friendly dashboard message — climate + tide vibe in 1-2 sentences.
     */
    public function generateDashboard(array $tideData, ?array $inaRaw): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            return null;
        }

        $nowIso      = $inaRaw['now'] ?? Carbon::now(self::TZ)->format('c');
        $inaForecast = $this->futureInaForecast($inaRaw, $nowIso);
        $inaExtremes = $this->detectExtremes($inaForecast);
        $shnForecast = app(TideService::class)->buildForecastSeries($tideData['forecast'] ?? []);

        $prompt = $this->buildDashboardPrompt($tideData, $shnForecast, $inaExtremes, $nowIso);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(12)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'messages'    => [
                        ['role' => 'system', 'content' => $this->dashboardSystemPrompt()],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'max_tokens'  => 80,
                    'temperature' => 0.4,
                ]);

            if (! $response->successful()) {
                Log::warning('TideSummaryService [dashboard]: OpenAI HTTP ' . $response->status());
                return null;
            }

            $text = trim($response->json('choices.0.message.content') ?? '');
            if (! $text) {
                return null;
            }

            Cache::put(self::CACHE_KEY_DASHBOARD, $text, now()->addMinutes(self::CACHE_TTL));
            return $text;

        } catch (\Throwable $e) {
            Log::warning('TideSummaryService [dashboard]: exception', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate a fresh summary from structured tide data, cache it, and return it.
     * Returns null on any failure — callers should fall back to template strings.
     *
     * @param array      $tideData    Output of TideService::getData()
     * @param array      $inaRaw      Output of InaService::getCachedTideData()
     */
    public function generate(array $tideData, ?array $inaRaw): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (! $apiKey) {
            Log::warning('TideSummaryService: OPENAI_API_KEY not configured — skipping LLM summary');
            return null;
        }

        // Derive the same intermediate data the controller uses
        $nowIso      = $inaRaw['now'] ?? Carbon::now(self::TZ)->format('c');
        $inaForecast = $this->futureInaForecast($inaRaw, $nowIso);
        $inaExtremes = $this->detectExtremes($inaForecast);
        $shnForecast = app(TideService::class)->buildForecastSeries($tideData['forecast'] ?? []);
        $comparison  = $this->buildComparison($shnForecast, $inaExtremes, $nowIso);

        $prompt = $this->buildPrompt($tideData, $inaExtremes, $shnForecast, $comparison, $nowIso);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(12)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'messages'    => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'max_tokens'  => 120,
                    'temperature' => 0.3,
                ]);

            if (! $response->successful()) {
                Log::warning('TideSummaryService: OpenAI HTTP ' . $response->status(), [
                    'body' => $response->body(),
                ]);
                return null;
            }

            $text = trim($response->json('choices.0.message.content') ?? '');
            if (! $text) {
                return null;
            }

            Cache::put(self::CACHE_KEY, $text, now()->addMinutes(self::CACHE_TTL));

            return $text;

        } catch (\Throwable $e) {
            Log::warning('TideSummaryService: exception calling OpenAI', ['msg' => $e->getMessage()]);
            return null;
        }
    }

    // ─── Dashboard prompt ─────────────────────────────────────────────────────

    private function dashboardSystemPrompt(): string
    {
        return <<<PROMPT
Sos el asistente del Delta del Paraná (San Fernando, Argentina).
Escribí exactamente 1 oración — o 2 muy cortas si hace falta — para la pantalla de inicio.

Enfoque: lo que importa AHORA y las próximas horas de hoy. Mañana solo merece una mención si hay algo realmente notable (nivel crítico o muy alto). No describas el día de mañana en detalle — para eso está la página de marea.

Tono: mensaje de texto a un vecino isleño. Relajado, con buena onda, directo. Si todo está bien, transmití eso con energía positiva. Si hay algo para avisar, avisás sin drama.

Cada evento viene con su franja horaria y estado. Usá esas etiquetas:
- Si todos los eventos de hoy son NORMAL → una frase positiva y corta.
- Si hay un evento no-NORMAL hoy → mencioná la franja y, si hay recuperación hoy mismo, nombrála. Si la recuperación es mañana, no hace falta explicarla en detalle.
- Si la "Tendencia observada" muestra niveles muy por debajo del pronóstico → mencionalo en una frase.
- No describas cada evento cronológicamente. Resumí la vibe del día.

Terminología: "pleamar"/"plea" para picos altos. "Bajamar"/"baja" para picos bajos.

Ejemplos correctos:
✓ "Tarde tranquila, agua normal. Mañana al mediodía baja un poco — nada urgente."
✓ "Agua normal toda la tarde y la noche."
✓ "Poca agua al mediodía (0.5 m), pero la plea de la tarde lo resuelve."
✓ "Todo tranquilo hoy. Mañana hay una baja al mediodía — vale la pena mirar la marea antes de salir."
✗ "Mañana a la madrugada y mañana también, pero a mediodía baja un poco — mejor salir a la mañana o esperar la plea de la noche." ← demasiado detalle sobre mañana
✗ "Se espera bajamar crítica. Asegurate de planificar con precaución." ← tono de gobierno

Estilo: español rioplatense. Sin exclamaciones. Arrancá directo.
PROMPT;
    }

    private function buildDashboardPrompt(
        array  $tideData,
        array  $shnForecast,
        array  $inaExtremes,
        string $nowIso
    ): string {
        $tz      = self::TZ;
        $now     = Carbon::parse($nowIso, $tz);
        $current = $tideData['current'] ?? null;
        $trend   = $tideData['trend']   ?? 'stable';
        $weather = $tideData['weather'] ?? [];
        $wind    = $tideData['wind']    ?? [];

        $trendMap = ['rising' => 'subiendo', 'falling' => 'bajando', 'stable' => 'estable'];
        $lines    = [];

        // ── Contexto temporal (el modelo necesita saber si es de día o de noche) ──
        $h      = (int) $now->format('G');
        $franja = match (true) {
            $h >= 0  && $h < 6  => 'madrugada',
            $h >= 6  && $h < 11 => 'mañana',
            $h >= 11 && $h < 14 => 'mediodía',
            $h >= 14 && $h < 19 => 'tarde',
            default             => 'noche',
        };
        $lines[] = 'Hora actual: ' . $now->format('H:i') . " ({$franja})";

        // Current state (compact)
        $stateParts = [];
        if ($current) {
            $nivel    = number_format((float) $current['level'], 2);
            $tendency = $trendMap[$trend] ?? $trend;
            $stateParts[] = "Marea: {$nivel} m ({$tendency})";
        }
        if ($weather['available'] ?? false) {
            $temp      = $weather['temperature'] ?? null;
            $feels     = $weather['feels_like'] ?? null;
            $condition = strtolower($weather['condition'] ?? '');
            if ($temp !== null) {
                $tempStr      = "{$temp}°C" . ($feels !== null && abs($feels - $temp) >= 3 ? " (sensación {$feels}°C)" : '');
                $stateParts[] = "Clima: {$tempStr}, {$condition}";
            }
        }
        if (($wind['available'] ?? false) && ($wind['speed'] ?? 0) > 0) {
            $stateParts[] = "Viento: {$wind['direction']}, {$wind['speed']} km/h";
        }
        if ($stateParts) {
            $lines[] = implode(' | ', $stateParts);
        }

        // Tendencia observada — últimas lecturas horarias (para ver si el agua bajó más de lo previsto)
        $hourly = $tideData['hourly'] ?? [];
        if (count($hourly) >= 2) {
            $recent  = array_slice($hourly, -3);
            $obsStrs = array_map(fn ($h) => "{$h['hour']}: " . number_format((float) $h['level'], 2) . " m", $recent);
            $lines[] = "Tendencia observada: " . implode(' → ', $obsStrs);
        }

        // ── Próximos eventos: cronológicos, SHN preferido, INA llena los gaps ──
        // SHN es la fuente oficial. INA se agrega solo cuando no hay evento SHN
        // del mismo tipo (max/min) dentro de una ventana de 90 minutos.
        // Esto evita duplicados y mantiene el gap de INA-only cubierto.
        $horizon    = $now->copy()->addHours(36);
        $matchWin   = 90; // minutos de tolerancia para considerar mismo evento

        $allEvents = [];

        // Primero SHN
        foreach ($shnForecast as $e) {
            $ts = Carbon::parse($e['time'], $tz);
            if ($ts->lt($now) || $ts->gt($horizon)) continue;
            $allEvents[] = ['time' => $e['time'], 'kind' => $e['kind'],
                            'value' => (float) $e['value'], 'source' => 'SHN'];
        }

        // INA solo si no hay SHN del mismo tipo cerca
        foreach ($inaExtremes as $e) {
            $ts = Carbon::parse($e['time'], $tz);
            if ($ts->lt($now) || $ts->gt($horizon)) continue;

            $hasShn = false;
            foreach ($allEvents as $s) {
                if ($s['source'] !== 'SHN' || $s['kind'] !== $e['kind']) continue;
                if (abs(Carbon::parse($s['time'], $tz)->diffInMinutes($ts)) <= $matchWin) {
                    $hasShn = true;
                    break;
                }
            }
            if (! $hasShn) {
                $allEvents[] = ['time' => $e['time'], 'kind' => $e['kind'],
                                'value' => (float) $e['value'], 'source' => 'INA'];
            }
        }

        usort($allEvents, fn ($a, $b) => strcmp($a['time'], $b['time']));

        if ($allEvents) {
            $lines[] = 'Próximos eventos:';
            foreach ($allEvents as $e) {
                $tipo  = $e['kind'] === 'max' ? 'pleamar' : 'bajamar';
                $nivel = number_format($e['value'], 2);
                $label = $this->eventLabel(Carbon::parse($e['time'], $tz), $now, $e['value']);
                $lines[] = "  [{$e['source']}] {$tipo} {$nivel} m — {$label}";
            }
        }

        // Rain today if significant
        if ($weather['available'] ?? false) {
            foreach ($weather['hourly'] ?? [] as $dg) {
                if ($dg['date'] !== $now->toDateString()) {
                    continue;
                }
                $rainyHours = array_filter($dg['hours'], fn ($h) => ($h['rain'] ?? 0) >= 50);
                if (! empty($rainyHours)) {
                    $rainyHours = array_values($rainyHours);
                    $maxProb    = max(array_column($rainyHours, 'rain'));
                    $lines[]    = "Lluvia hoy: hasta {$maxProb}% de probabilidad";
                }
                break;
            }
        }

        return implode("\n", $lines);
    }

    // ─── Prompt builders ─────────────────────────────────────────────────────

    private function systemPrompt(): string
    {
        return <<<PROMPT
Sos el asistente operacional del Delta del Paraná (San Fernando, Argentina).
Recibís un briefing de marea y clima. Cada evento ya viene con su franja horaria y su estado (NORMAL, NIVEL BAJO, POCA AGUA — CALADO CRÍTICO, ATENCIÓN, MAREA ALTA — MUELLES).

Tu tarea: escribir 2 oraciones útiles para alguien que va a navegar o trabajar en el delta.

Sobre las fuentes de datos:
- SHN (oficial): pronóstico astronómico que se recalcula a diario. Para eventos del día de hoy es la fuente más confiable tanto en HORARIO como en NIVEL. Solo puede errar cuando hay sudestada, viento sostenido o crecida fuerte del Paraná.
- INA (modelo hidrológico): se actualiza cada varios días — su valor es mayor para planificación de mediano plazo (próximos días/semana). Para hoy, usalo como referencia secundaria; para mañana en adelante, tiene más peso que SHN.
- Nivel observado: siempre el dato más confiable — es lo que está pasando ahora mismo. Si difiere del pronóstico, priorizalo.

Reglas de contenido:
- Empezá siempre desde el nivel observado actual. Si está muy por debajo del pronóstico, decílo: "el agua bajó más de lo previsto" o "llegó más bajo de lo que indicaba el pronóstico".
- Siempre describí el arco completo: la ventana mala Y cuándo mejora. La persona necesita saber cuándo puede salir tranquilo.
- Mencioná eventos NORMAL solo cuando sirven para marcar la recuperación.
- Los horarios son PICOS del ciclo — la condición dura horas alrededor. Hablá de franjas.
- Para eventos importantes, mencioná hora y nivel: "bajamar al mediodía (0.5 m)", "plea a las 18:30 (1.2 m)".
- Si el evento es solo de INA, aclaralo con "según el INA". Si es SHN, podés decirlo directamente.
- Lluvia y viento SE solo si coinciden con un momento crítico.
- Si TODOS los eventos son NORMAL, transmitilo con optimismo. Si alguno es NIVEL BAJO o peor, no uses "pinta bien".
- CRÍTICO: Cada evento tiene su propia franja horaria y estado ya calculados. NO los mezcles entre sí. Si SHN tiene una baja a las 01:00 (madrugada, NORMAL) e INA tiene otra baja a las 13:00 (mediodía, NIVEL BAJO), son dos eventos distintos — describí cada uno con su franja exacta. Nunca uses la franja de un evento para describir el nivel de otro.

Terminología: "pleamar" / "plea" para picos altos. "Bajamar" / "baja" para picos bajos.

Tono: relajado, isleño, directo. No convertir un aviso en alerta de gobierno.
Ejemplos correctos:
✓ "El agua bajó más de lo previsto — ahora en 0.39 m y aún bajando. La plea de la tarde (18:30, 1.2 m) es la ventana para salir."
✓ "Mañana al mediodía bajamar a 0.6 m según el INA — nivel bajo en esa franja. La plea de la noche mejora a 1.4 m."
✗ "Se espera un nivel crítico. Asegurate de planificar con precaución." ← muy formal

Estilo:
- Español rioplatense. Sin exclamaciones. Arrancá directo.
- Frases cortas. Punto seguido en vez de comas encadenadas.
- Si SHN e INA difieren bastante, podés usar "podría". No menciones bandas de incertidumbre.
PROMPT;
    }

    private function buildPrompt(
        array   $tideData,
        array   $inaExtremes,
        array   $shnForecast,
        ?array  $comparison,
        string  $nowIso
    ): string {
        $tz      = self::TZ;
        $now     = Carbon::parse($nowIso, $tz);
        $current = $tideData['current'] ?? null;
        $trend   = $tideData['trend']   ?? 'stable';
        $wind    = $tideData['wind']    ?? [];
        $weather = $tideData['weather'] ?? [];

        $trendMap = ['rising' => 'subiendo', 'falling' => 'bajando', 'stable' => 'estable'];

        $sections = [];

        // ── Estado actual + tendencia horaria observada ──────────────────────
        $currentLines = [];
        if ($current) {
            $nivel     = number_format((float) $current['level'], 2);
            $tendency  = $trendMap[$trend] ?? $trend;
            $statusNow = $this->eventLabel($now, $now, (float) $current['level']);
            // Extract just the STATUS part for current level
            $statusPart = explode(' — ', $statusNow)[1] ?? '';
            $currentLines[] = "Nivel observado: {$nivel} m ({$tendency}) — {$statusPart}";
        }

        // Últimas 4 lecturas horarias observadas — muestran la trayectoria real
        $hourly = $tideData['hourly'] ?? [];
        if (count($hourly) >= 2) {
            $recent = array_slice($hourly, -4); // últimas 4
            $obsStrs = array_map(fn ($h) => "{$h['hour']}: " . number_format((float) $h['level'], 2) . " m", $recent);
            $currentLines[] = "Lecturas recientes (SHN observado): " . implode(' → ', $obsStrs);
        }
        if ($weather['available'] ?? false) {
            $temp      = $weather['temperature'] ?? null;
            $feels     = $weather['feels_like'] ?? null;
            $condition = $weather['condition'] ?? '';
            $parts     = [];
            if ($temp !== null) {
                $parts[] = "{$temp}°C" . ($feels !== null && abs($feels - $temp) >= 3 ? " (sensación {$feels}°C)" : '');
            }
            if ($condition) $parts[] = strtolower($condition);
            if ($parts) $currentLines[] = "Clima: " . implode(', ', $parts);
        }
        if ($wind['available'] ?? false) {
            $currentLines[] = "Viento: {$wind['direction']}, {$wind['speed']} km/h";
        }
        if ($currentLines) {
            $sections[] = "── ESTADO ACTUAL ──\n" . implode("\n", $currentLines);
        }

        // ── Pronóstico de marea ──────────────────────────────────────────────
        $tideParts = [];

        $futureShn = array_values(array_filter($shnForecast, fn ($e) => strcmp($e['time'], $nowIso) > 0));
        if ($futureShn) {
            $shnLines = ['SHN (oficial):'];
            foreach ($futureShn as $e) {
                $tipo    = $e['kind'] === 'max' ? 'alta' : 'baja';
                $eCarbon = Carbon::parse($e['time'], $tz);
                $nivel   = number_format((float) $e['value'], 1);
                $label   = $this->eventLabel($eCarbon, $now, (float) $e['value']);
                $shnLines[] = "  Marea {$tipo}: {$nivel} m — {$label}";
            }
            $tideParts[] = implode("\n", $shnLines);
        }

        $horizon   = $now->copy()->addHours(36);
        $futureIna = array_values(array_filter(
            $inaExtremes,
            fn ($e) => strcmp($e['time'], $nowIso) > 0 && Carbon::parse($e['time'], $tz)->lte($horizon)
        ));
        if ($futureIna) {
            $inaLines = ['INA (modelo hidrológico — mencioná "según el INA" al citar estos valores):'];
            foreach ($futureIna as $e) {
                $tipo    = $e['kind'] === 'max' ? 'alta' : 'baja';
                $eCarbon = Carbon::parse($e['time'], $tz);
                $nivel   = number_format((float) $e['value'], 1);
                $label   = $this->eventLabel($eCarbon, $now, (float) $e['value']);
                $inaLines[] = "  Marea {$tipo}: {$nivel} m — {$label}";
            }
            $tideParts[] = implode("\n", $inaLines);
        }

        if ($comparison) {
            $note = match ($comparison['interp']) {
                'agree'      => 'Concordancia: SHN e INA coinciden en horario del próximo evento.',
                'minor_diff' => "Diferencia leve SHN/INA: {$comparison['diff']} m en el nivel.",
                default      => "Diferencia notable SHN/INA: {$comparison['diff']} m — posible factor meteorológico.",
            };
            $tideParts[] = $note;
        }

        if ($tideParts) {
            $sections[] = "── PRONÓSTICO DE MAREA (36 h) ──\n" . implode("\n", $tideParts);
        }

        // ── Clima próximo ────────────────────────────────────────────────────
        $weatherSection = $this->buildWeatherSection($weather, $now, $inaExtremes, $nowIso);
        if ($weatherSection) {
            $sections[] = "── CLIMA PRÓXIMO ──\n" . $weatherSection;
        }

        return implode("\n\n", $sections);
    }

    private function buildWeatherSection(array $weather, Carbon $now, array $inaExtremes, string $nowIso): string
    {
        if (! ($weather['available'] ?? false)) {
            return '';
        }

        $tz    = self::TZ;
        $lines = [];

        foreach ($weather['hourly'] ?? [] as $dayGroup) {
            $date  = $dayGroup['date'] ?? null;
            $hours = $dayGroup['hours'] ?? [];
            if (! $date || empty($hours)) {
                continue;
            }

            $dayCarbon  = Carbon::parse($date, $tz)->startOfDay();
            $isToday    = $dayCarbon->isSameDay($now);
            $isTomorrow = $dayCarbon->isSameDay($now->copy()->addDay());
            if (! $isToday && ! $isTomorrow) {
                continue;
            }

            $relevant = $isToday
                ? array_values(array_filter($hours, fn ($h) => Carbon::parse("{$date}T{$h['hour']}", $tz)->gt($now)))
                : $hours;
            if (empty($relevant)) {
                continue;
            }

            $label = $isToday ? 'Resto de hoy' : 'Mañana (' . Carbon::parse($date, $tz)->format('d/m') . ')';
            $parts = [];

            // Rain: only if at least one hour >= 50% probability
            $rainy = array_values(array_filter($relevant, fn ($h) => ($h['rain'] ?? 0) >= 50));
            if (! empty($rainy)) {
                $firstH  = $rainy[0]['hour'];
                $lastH   = end($rainy)['hour'];
                $maxProb = max(array_column($rainy, 'rain'));
                $window  = $firstH === $lastH ? "a las {$firstH}" : "de {$firstH} a {$lastH}";
                $parts[] = "lluvia {$window} (hasta {$maxProb}%)";
            } else {
                $parts[] = 'sin lluvia probable';
            }

            // Temp range
            $temps   = array_column($relevant, 'temp');
            $parts[] = 'temp ' . min($temps) . '–' . max($temps) . '°C';

            // Fog: 2+ hours of Niebla = sustained; 1 = posible
            $fogHours = array_filter($relevant, fn ($h) => ($h['condition'] ?? '') === 'Niebla');
            if (count($fogHours) >= 2) {
                $parts[] = 'niebla intensa';
            } elseif (! empty($fogHours)) {
                $parts[] = 'niebla posible';
            }

            // SE wind
            $seHours = array_values(array_filter($relevant, fn ($h) => ($h['wind_dir'] ?? 0) >= 100
                && ($h['wind_dir'] ?? 0) <= 170
                && ($h['wind_speed'] ?? 0) >= 15));
            if (! empty($seHours)) {
                $avgSpd  = round(array_sum(array_column($seHours, 'wind_speed')) / count($seHours));
                $parts[] = "viento SE ~{$avgSpd} km/h (efecto represa posible)";
            }

            $lines[] = "{$label}: " . implode(', ', $parts) . '.';

            // Tomorrow: INA tide range + critical level alerts
            if ($isTomorrow) {
                $tomorrowDate = $dayCarbon->toDateString();
                $inaToday     = array_filter(
                    $inaExtremes,
                    fn ($e) => Carbon::parse($e['time'], $tz)->toDateString() === $tomorrowDate
                );
                if (! empty($inaToday)) {
                    $vals   = array_column(array_values($inaToday), 'value');
                    $minVal = min($vals);
                    $maxVal = max($vals);

                    $criticals = [];
                    if ($minVal < 0.60) {
                        $criticals[] = 'mínima ' . number_format($minVal, 2) . ' m → calado crítico';
                    } elseif ($minVal < 0.70) {
                        $criticals[] = 'mínima ' . number_format($minVal, 2) . ' m → nivel bajo';
                    }
                    if ($maxVal > 2.20) {
                        $criticals[] = 'máxima ' . number_format($maxVal, 2) . ' m → posible agua en muelles';
                    }

                    $tidesLine = '  INA mañana: mín ' . number_format($minVal, 2) . ' m / máx ' . number_format($maxVal, 2) . ' m';
                    if (! empty($criticals)) {
                        $tidesLine .= ' — ALERTA: ' . implode('; ', $criticals);
                    }
                    $lines[] = $tidesLine;
                }
            }
        }

        return implode("\n", $lines);
    }

    // ─── Shared logic (mirrors MareaController private methods) ──────────────

    private function futureInaForecast(?array $inaRaw, string $nowIso): array
    {
        if (! $inaRaw || empty($inaRaw['data'])) {
            return [];
        }
        return array_values(array_filter(
            $inaRaw['data'],
            fn ($p) => $p['type'] === 'forecast' && strcmp($p['time'], $nowIso) >= 0
        ));
    }

    private function detectExtremes(array $series): array
    {
        $extremes = [];
        $n = count($series);
        for ($i = 1; $i < $n - 1; $i++) {
            $prev = $series[$i - 1]['value'];
            $curr = $series[$i]['value'];
            $next = $series[$i + 1]['value'];
            if ($curr > $prev && $curr > $next) {
                $extremes[] = array_merge($series[$i], ['kind' => 'max']);
            } elseif ($curr < $prev && $curr < $next) {
                $extremes[] = array_merge($series[$i], ['kind' => 'min']);
            }
        }
        return $extremes;
    }

    private function buildComparison(array $shnForecast, array $inaExtremes, string $nowIso): ?array
    {
        $tz          = self::TZ;
        $matchWindow = config('tide.event_match_window_minutes', 30);

        foreach ($shnForecast as $shn) {
            if (strcmp($shn['time'], $nowIso) <= 0) {
                continue;
            }
            $shnTs = Carbon::parse($shn['time'], $tz);

            foreach ($inaExtremes as $ina) {
                if ($ina['kind'] !== $shn['kind']) {
                    continue;
                }
                if (abs(Carbon::parse($ina['time'], $tz)->diffInMinutes($shnTs)) > $matchWindow) {
                    continue;
                }
                $diff   = abs($shn['value'] - $ina['value']);
                $interp = match (true) {
                    $diff < 0.10 => 'agree',
                    $diff < 0.20 => 'minor_diff',
                    default      => 'notable_diff',
                };
                return [
                    'kind'      => $shn['kind'],
                    'shn_value' => $shn['value'],
                    'ina_value' => $ina['value'],
                    'diff'      => round($diff, 3),
                    'interp'    => $interp,
                ];
            }
        }
        return null;
    }

    // ─── Event label helper ───────────────────────────────────────────────────

    /**
     * Build a rich, unambiguous label for a single tide event.
     * Example output: "Miércoles, mediodía — POCA AGUA"
     *
     * Embedding franja + status directly in the data means GPT doesn't have to
     * infer either one from raw numbers — it just reads what's there.
     */
    private function eventLabel(Carbon $ts, Carbon $now, float $level): string
    {
        // ── Day name ──────────────────────────────────────────────────────────
        // Always include the actual weekday so the label stays unambiguous even
        // when the LLM cache is served hours after generation (hoy→ayer, etc.).
        $days   = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $diff   = (int) $now->copy()->startOfDay()->diffInDays($ts->copy()->startOfDay(), false);
        $dayName = $days[$ts->dayOfWeek];
        $dayStr = match ($diff) {
            0       => "hoy ({$dayName})",
            1       => "mañana ({$dayName})",
            default => $dayName,
        };

        // ── Franja horaria ────────────────────────────────────────────────────
        $h = (int) $ts->format('G'); // 0–23
        $franja = match (true) {
            $h >= 0  && $h < 6  => 'madrugada',
            $h >= 6  && $h < 11 => 'mañana',
            $h >= 11 && $h < 14 => 'mediodía',
            $h >= 14 && $h < 19 => 'tarde',
            default             => 'noche',
        };

        // ── Status label ──────────────────────────────────────────────────────
        $status = match (true) {
            $level >= 2.20 => 'MAREA ALTA — MUELLES',
            $level >= 1.70 => 'ATENCIÓN',
            $level >= 0.70 => 'NORMAL',
            $level >= 0.60 => 'NIVEL BAJO',
            $level >= 0.40 => 'POCA AGUA',
            default        => 'MUY POCA AGUA',
        };

        $hora = $ts->format('H:i');
        return "{$dayStr}, {$franja} ({$hora}) — {$status}";
    }
}
