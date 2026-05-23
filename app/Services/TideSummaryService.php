<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TideSummaryService
{
    const CACHE_KEY = 'tide_llm_summary';
    const CACHE_TTL = 35; // minutes — slightly longer than the tide data TTL
    const TZ        = 'America/Argentina/Buenos_Aires';

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Return the cached LLM-generated summary, or null if not yet generated.
     */
    public function getCached(): ?string
    {
        return Cache::get(self::CACHE_KEY);
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
                    'max_tokens'  => 160,
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

    // ─── Prompt builders ─────────────────────────────────────────────────────

    private function systemPrompt(): string
    {
        return <<<PROMPT
Sos el asistente operacional del Delta del Paraná (San Fernando, Argentina).
Recibís un briefing con datos de marea y clima para las próximas 36 horas.

Tu tarea: escribir 2 oraciones que le sirvan de verdad a alguien que va a navegar o trabajar en el delta. No repitas los números — interpretá el día y contá lo que importa.

Para razonar bien:
- Mirá el ciclo completo de hoy: si hay un momento bueno entre dos momentos críticos, eso define la ventana operativa.
- Si el nivel mejora en la tarde pero vuelve a caer a niveles críticos a la noche, eso hay que decirlo.
- Nivel < 0.70 m = calado crítico para lanchas. Nivel > 2.20 m = agua en muelles. Solo usá estas alertas cuando aplican.
- Si mañana tiene un patrón similar o peor, mencionálo brevemente.
- Lluvia (si la hay con alta prob.) y niebla son relevantes solo si coinciden con un momento crítico de marea o dificultan la navegación.
- Viento SE con efecto de represa: solo si aparece en los datos.

Estilo:
- Español rioplatense. Directo y neutro. Sin tecnicismos hidrológicos.
- "marea alta" y "marea baja". Nunca "pleamar" ni "bajante".
- No saludes. No te presentes. Arrancá directo con la información.
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

        // ── Estado actual ────────────────────────────────────────────────────
        $currentLines = [];
        if ($current) {
            $nivel     = number_format((float) $current['level'], 2);
            $tendency  = $trendMap[$trend] ?? $trend;
            $currentLines[] = "Nivel (observado): {$nivel} m | Tendencia: {$tendency}";
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
                $tipo      = $e['kind'] === 'max' ? 'alta' : 'baja';
                $eCarbon   = Carbon::parse($e['time'], $tz);
                $hora      = $eCarbon->format('H:i');
                $dia       = $eCarbon->isSameDay($now) ? 'hoy' : 'mañana';
                $shnLines[] = "  Marea {$tipo}: {$e['value']} m — {$hora} ({$dia})";
            }
            $tideParts[] = implode("\n", $shnLines);
        }

        $horizon   = $now->copy()->addHours(36);
        $futureIna = array_values(array_filter(
            $inaExtremes,
            fn ($e) => strcmp($e['time'], $nowIso) > 0 && Carbon::parse($e['time'], $tz)->lte($horizon)
        ));
        if ($futureIna) {
            $inaLines = ['INA (modelo hidrológico):'];
            foreach ($futureIna as $e) {
                $tipo      = $e['kind'] === 'max' ? 'alta' : 'baja';
                $eCarbon   = Carbon::parse($e['time'], $tz);
                $hora      = $eCarbon->format('H:i');
                $dia       = $eCarbon->isSameDay($now) ? 'hoy' : 'mañana';
                $banda     = (isset($e['error_lo'], $e['error_hi']))
                    ? " (banda: {$e['error_lo']}–{$e['error_hi']} m)"
                    : '';
                $inaLines[] = "  Marea {$tipo}: {$e['value']} m — {$hora} ({$dia}){$banda}";
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
                    if ($minVal < 0.70) {
                        $criticals[] = 'mínima ' . number_format($minVal, 2) . ' m → calado crítico';
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
}
