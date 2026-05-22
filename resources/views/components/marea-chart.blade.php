{{-- marea-chart: 3-series interactive SVG chart (SHN obs · SHN pron · INA modelo) --}}
{{-- Reads data from window.__tideData (set by the marea view). --}}

<div x-data="mareaChart()" x-init="init()">

    {{-- Header: title + toggles --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-3 px-5 pt-5">
        <div>
            <p class="font-bold text-sm text-gray-800 dark:text-gray-100">
                {{ __('ui.tide_chart_section') }}
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                {{ __('ui.tide_chart_subtitle') }}
            </p>
        </div>

        {{-- Series toggles --}}
        <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs items-center">
            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <input type="checkbox" x-model="showShnObs" @change="rebuild()" class="sr-only">
                <span class="w-3 h-3 rounded-full shrink-0 ring-1 ring-offset-1"
                      :class="showShnObs ? 'ring-transparent' : 'ring-gray-400 bg-transparent'"
                      :style="showShnObs ? 'background:var(--color-shn-obs);box-shadow:0 0 0 1px var(--color-shn-obs)' : ''"></span>
                <span :class="showShnObs ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'">
                    {{ __('ui.tide_legend_shn_obs') }}
                </span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <input type="checkbox" x-model="showShnPro" @change="rebuild()" class="sr-only">
                <span class="w-3 h-3 rounded-full shrink-0 ring-1 ring-offset-1"
                      :class="showShnPro ? 'ring-transparent' : 'ring-gray-400 bg-transparent'"
                      :style="showShnPro ? 'background:var(--color-shn-pro);box-shadow:0 0 0 1px var(--color-shn-pro)' : ''"></span>
                <span :class="showShnPro ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'">
                    {{ __('ui.tide_legend_shn_pro') }}
                </span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <input type="checkbox" x-model="showIna" @change="rebuild()" class="sr-only">
                <span class="inline-block w-5 h-px shrink-0" style="border-top:2px dashed var(--color-ina);" x-show="showIna"></span>
                <span class="inline-block w-5 h-px shrink-0 border-t-2 border-gray-300 dark:border-gray-600 border-dashed" x-show="!showIna" x-cloak></span>
                <span :class="showIna ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'">
                    {{ __('ui.tide_legend_ina') }}
                </span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer select-none">
                <input type="checkbox" x-model="showWind" @change="rebuild()" class="sr-only">
                <span class="inline-block w-5 h-px shrink-0" style="border-top:1.5px solid var(--color-wind-se);" x-show="showWind"></span>
                <span class="inline-block w-5 h-px shrink-0 border-t border-gray-300 dark:border-gray-600" x-show="!showWind" x-cloak></span>
                <span :class="showWind ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500'">
                    {{ __('ui.tide_legend_wind') }}
                </span>
            </label>
        </div>
    </div>

    {{-- Chart + tooltip overlay --}}
    <div class="relative"
         @mousemove="onMouseMove($event)"
         @mouseleave="tooltipVisible = false">

        <div x-html="svgContent" class="w-full overflow-hidden"></div>

        {{-- Floating tooltip --}}
        <div x-show="tooltipVisible" x-cloak
             class="absolute pointer-events-none z-20 rounded-xl shadow-2xl px-3 py-2.5
                    bg-gray-900/95 dark:bg-gray-800/95 text-white border border-white/10
                    text-xs min-w-[170px]"
             :style="tooltipStyle">
            <p class="text-gray-300 text-[10px] mb-1.5 font-medium" x-text="tooltipData?.timeLabel ?? ''"></p>
            <div class="space-y-1">
                <template x-if="showShnObs">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background:var(--color-shn-obs)"></span>
                        <span class="text-gray-400 flex-1">SHN obs</span>
                        <span class="font-semibold tabular-nums" x-text="tooltipData?.shnObsVal ?? '—'"></span>
                    </div>
                </template>
                <template x-if="showShnPro">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background:var(--color-shn-pro)"></span>
                        <span class="text-gray-400 flex-1">SHN pron</span>
                        <span class="font-semibold tabular-nums" x-text="tooltipData?.shnProVal ?? '—'"></span>
                    </div>
                </template>
                <template x-if="showIna">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background:var(--color-ina)"></span>
                        <span class="text-gray-400 flex-1">INA</span>
                        <span class="font-semibold tabular-nums" x-text="tooltipData?.inaVal ?? '—'"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Range tabs --}}
    <div class="flex gap-2 mt-3 px-5">
        @foreach ([1 => '1 día', 2 => '2 días', 3 => '3 días'] as $days => $label)
        <button @click="setRange({{ $days }})"
                :class="range === {{ $days }}
                    ? 'bg-gray-800 text-white dark:bg-gray-100 dark:text-gray-900'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="px-4 py-1.5 rounded-lg text-xs font-medium transition-colors">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- Threshold legend --}}
    <div class="flex flex-wrap gap-4 text-xs mt-3 px-5">
        <span class="flex items-center gap-1.5 text-red-400">
            <span class="inline-block w-5" style="border-top:2px dashed currentColor"></span>
            {{ __('ui.tide_threshold_evacuation') }}
        </span>
        <span class="flex items-center gap-1.5 text-amber-400">
            <span class="inline-block w-5" style="border-top:2px dashed currentColor"></span>
            {{ __('ui.tide_threshold_alert') }}
        </span>
    </div>

    {{-- Disclaimer --}}
    <p class="text-xs text-gray-400 dark:text-gray-500 mt-3 px-5 pb-5">
        ⓘ {{ __('ui.tide_chart_disclaimer') }}
        <a href="https://alerta.ina.gob.ar" target="_blank" rel="noopener"
           class="underline hover:text-gray-600 dark:hover:text-gray-300 ml-1">alerta.ina.gob.ar</a>
    </p>

</div>

<script>
function mareaChart() {
    return {
        range:      2,
        showShnObs: true,
        showShnPro: true,
        showIna:    true,
        showWind:   false,
        svgContent: '',
        tooltipVisible: false,
        tooltipData:    null,
        tooltipStyle:   '',

        // SVG dimensions (constant)
        W: 700, H: 210,
        P: { t: 14, r: 12, b: 36, l: 38 },

        init() { this.svgContent = this.buildSvg(); },
        rebuild() { this.svgContent = this.buildSvg(); },
        setRange(days) { this.range = days; this.svgContent = this.buildSvg(); },

        // ── Tooltip ──────────────────────────────────────────────────────────

        onMouseMove(e) {
            const d = window.__tideData;
            if (!d) return;

            const container = e.currentTarget;
            const rect = container.getBoundingClientRect();
            const relX = e.clientX - rect.left;
            const relY = e.clientY - rect.top;

            const pw = this.W - this.P.l - this.P.r;
            // Map screen X → SVG X
            const svgX = relX / rect.width * this.W;
            if (svgX < this.P.l || svgX > this.W - this.P.r) {
                this.tooltipVisible = false;
                return;
            }

            const nowMs   = new Date(d.now).getTime();
            const obsAll  = d.shn_observed || [];
            const startMs = obsAll.length
                ? new Date(obsAll[0].time).getTime() - 30 * 60000
                : nowMs - 3 * 3600000;
            const endMs   = nowMs + this.range * 24 * 3600000;
            const t = startMs + (svgX - this.P.l) / pw * (endMs - startMs);
            const isPast = t < nowMs;

            const nearest = (arr, maxGapMs) => {
                let best = null, bestD = Infinity;
                for (const p of (arr || [])) {
                    const diff = Math.abs(new Date(p.time).getTime() - t);
                    if (diff < bestD) { bestD = diff; best = p; }
                }
                return (maxGapMs == null || bestD <= maxGapMs) ? best : null;
            };

            const shnObs = nearest(d.shn_observed, 2 * 3600000);
            const shnPro = nearest(d.shn_forecast,  6 * 3600000);
            const ina    = nearest(d.ina_forecast,   2 * 3600000);

            // Time label (Argentina time via UTC-3)
            const argMs = t - 0; // ms stays the same, we format with locale
            const timeLabel = new Date(t).toLocaleString('es-AR', {
                timeZone: 'America/Argentina/Buenos_Aires',
                day: 'numeric', month: 'short',
                hour: '2-digit', minute: '2-digit',
            });

            this.tooltipData = {
                timeLabel,
                shnObsVal: shnObs ? shnObs.value.toFixed(2) + ' m' : '—',
                shnProVal: shnPro ? shnPro.value.toFixed(2) + ' m' : '—',
                inaVal:    (!isPast && ina) ? ina.value.toFixed(2) + ' m' : '—',
            };

            // Position: right side if cursor > 60% chart width
            const isRight = relX / rect.width > 0.6;
            const topPx = Math.max(4, relY - 45);
            if (isRight) {
                this.tooltipStyle = `top:${topPx}px; right:${Math.round(rect.width - relX + 10)}px; left:auto;`;
            } else {
                this.tooltipStyle = `top:${topPx}px; left:${Math.round(relX + 10)}px; right:auto;`;
            }
            this.tooltipVisible = true;
        },

        // ── SVG builder ──────────────────────────────────────────────────────

        buildSvg() {
            const d = window.__tideData;
            if (!d) return '';

            const W = this.W, H = this.H;
            const P = this.P;
            const pw = W - P.l - P.r;
            const ph = H - P.t - P.b;
            const Y_MIN = 0, Y_MAX = 3.6;

            const nowMs   = new Date(d.now).getTime();
            const obsAll  = d.shn_observed || [];
            const startMs = obsAll.length
                ? new Date(obsAll[0].time).getTime() - 30 * 60000
                : nowMs - 3 * 3600000;
            const endMs   = nowMs + this.range * 24 * 3600000;

            const xOf   = ms => P.l + (ms - startMs) / (endMs - startMs) * pw;
            const yOf   = v  => P.t + ph * (1 - (v - Y_MIN) / (Y_MAX - Y_MIN));
            const clamp = (n, lo, hi) => n < lo ? lo : n > hi ? hi : n;
            const fx    = p  => xOf(new Date(p.time).getTime()).toFixed(1);
            const fy    = v  => clamp(yOf(v), P.t, P.t + ph).toFixed(1);

            const vis = (arr) => (arr || []).filter(p => {
                const ms = new Date(p.time).getTime();
                return ms >= startMs && ms <= endMs;
            });

            const obsV  = vis(d.shn_observed);
            const proV  = vis(d.shn_forecast);
            const inaV  = vis(d.ina_forecast);
            const windV = vis(d.wind_hourly);

            const out = [];

            // defs / clip
            out.push(`<defs><clipPath id="mc-clip"><rect x="${P.l}" y="${P.t}" width="${pw}" height="${ph}"/></clipPath></defs>`);

            // faint grid lines
            for (let v = 0; v <= 3.0; v += 0.5) {
                const y = yOf(v).toFixed(1);
                out.push(`<line x1="${P.l}" y1="${y}" x2="${W-P.r}" y2="${y}" stroke="#888" stroke-width="0.4" opacity="0.08"/>`);
            }

            // Normal zone band (0.7–2.2 m)
            {
                const yNT = yOf(2.2).toFixed(1);
                const yNB = yOf(0.7).toFixed(1);
                out.push(`<rect x="${P.l}" y="${yNT}" width="${pw}" height="${(yNB - yNT).toFixed(1)}" style="fill:var(--color-shn-obs);" opacity="0.04"/>`);
            }

            // Threshold lines
            {
                const thr = d.thresholds || {};
                const yEvac  = yOf(thr.evacuation ?? 3.5).toFixed(1);
                const yAlert = yOf(thr.alert ?? 3.0).toFixed(1);
                out.push(`<line x1="${P.l}" y1="${yEvac}"  x2="${W-P.r}" y2="${yEvac}"  stroke="#ef4444" stroke-width="1" stroke-dasharray="4 3" opacity="0.7"/>`);
                out.push(`<line x1="${P.l}" y1="${yAlert}" x2="${W-P.r}" y2="${yAlert}" stroke="#f59e0b" stroke-width="1" stroke-dasharray="4 3" opacity="0.65"/>`);
            }

            // ── INA error band ────────────────────────────────────────────────
            if (this.showIna && inaV.length > 1) {
                const hiPts = inaV.filter(p => p.error_hi != null).map(p => `${fx(p)},${fy(p.error_hi)}`);
                const loPts = inaV.filter(p => p.error_lo != null).slice().reverse().map(p => `${fx(p)},${fy(p.error_lo)}`);
                if (hiPts.length && loPts.length) {
                    out.push(`<polygon points="${[...hiPts,...loPts].join(' ')}" style="fill:var(--color-ina);" opacity="0.12" clip-path="url(#mc-clip)"/>`);
                }
            }

            // ── INA line (violet dashed) ──────────────────────────────────────
            if (this.showIna && inaV.length > 1) {
                const pts = inaV.map(p => `${fx(p)},${fy(p.value)}`).join(' ');
                out.push(`<polyline points="${pts}" style="stroke:var(--color-ina);fill:none;" stroke-width="1.5" stroke-dasharray="5 3" stroke-linejoin="round" clip-path="url(#mc-clip)"/>`);
            }

            // ── INA alarm markers (value < 0.70 m) ───────────────────────────
            if (this.showIna) {
                inaV.forEach(p => {
                    if (p.value < 0.70) {
                        const cx = xOf(new Date(p.time).getTime()).toFixed(1);
                        const cy = fy(p.value);
                        out.push(`<line x1="${cx}" y1="${P.t}" x2="${cx}" y2="${cy}" stroke="#f59e0b" stroke-width="0.75" stroke-dasharray="2 2" opacity="0.4" clip-path="url(#mc-clip)"/>`);
                        out.push(`<circle cx="${cx}" cy="${cy}" r="3.5" style="stroke:var(--color-ina);fill:none;" stroke-width="1.5" clip-path="url(#mc-clip)"/>`);
                    }
                });
            }

            // ── SHN observed line (teal, solid) ───────────────────────────────
            if (this.showShnObs && obsV.length > 1) {
                const pts = obsV.map(p => `${fx(p)},${fy(p.value)}`).join(' ');
                out.push(`<polyline points="${pts}" style="stroke:var(--color-shn-obs);fill:none;" stroke-width="2" stroke-linejoin="round" clip-path="url(#mc-clip)"/>`);
            }

            // ── SHN observed dots ─────────────────────────────────────────────
            if (this.showShnObs) {
                obsV.forEach(p => {
                    out.push(`<circle cx="${fx(p)}" cy="${fy(p.value)}" r="2.5" style="fill:var(--color-shn-obs);" clip-path="url(#mc-clip)"/>`);
                });
                // Current level: larger dot + label
                if (obsV.length) {
                    const curr = obsV[obsV.length - 1];
                    const cx   = xOf(new Date(curr.time).getTime());
                    const cy   = parseFloat(fy(curr.value));
                    const near = cx > W - P.r - 55;
                    const lx   = (near ? cx - 7 : cx + 7).toFixed(1);
                    const anch = near ? 'end' : 'start';
                    out.push(`<circle cx="${cx.toFixed(1)}" cy="${cy.toFixed(1)}" r="4" style="fill:var(--color-shn-obs);"/>`);
                    out.push(`<text x="${lx}" y="${(cy - 6).toFixed(1)}" text-anchor="${anch}" font-size="9" font-weight="700" style="fill:var(--color-shn-obs);">${curr.value.toFixed(2)} m</text>`);
                }
            }

            // ── SHN forecast: discrete dots at extremes (hollow blue circles) ─
            if (this.showShnPro && proV.length) {
                proV.forEach(p => {
                    const cx = xOf(new Date(p.time).getTime()).toFixed(1);
                    const cy = fy(p.value);
                    if (parseFloat(cx) < P.l || parseFloat(cx) > W - P.r) return;
                    out.push(`<circle cx="${cx}" cy="${cy}" r="5" style="fill:none;stroke:var(--color-shn-pro);" stroke-width="2" clip-path="url(#mc-clip)"/>`);
                    const labelY = (parseFloat(cy) + (p.kind === 'min' ? 14 : -8)).toFixed(1);
                    out.push(`<text x="${cx}" y="${labelY}" text-anchor="middle" font-size="8" font-weight="600" style="fill:var(--color-shn-pro);">${p.value.toFixed(2)}</text>`);
                });
            }

            // ── Wind SE overlay (upper 28% of chart area) ────────────────────
            if (this.showWind && windV.length > 1) {
                const WIND_TOP = P.t;
                const WIND_H   = ph * 0.28;
                const windY    = spd => (WIND_TOP + WIND_H * (1 - Math.min(spd, 50) / 50)).toFixed(1);

                // Full line (low opacity)
                const allPts = windV.map(p => `${xOf(new Date(p.time).getTime()).toFixed(1)},${windY(p.speed)}`).join(' ');
                out.push(`<polyline points="${allPts}" style="stroke:var(--color-wind-se);fill:none;" stroke-width="1" opacity="0.35" clip-path="url(#mc-clip)"/>`);

                // SE segments (higher opacity + thicker)
                let segStart = null;
                for (let i = 0; i <= windV.length; i++) {
                    const p   = windV[i];
                    const isSE = p && p.is_se && p.speed >= 15;
                    if (isSE && segStart === null) {
                        segStart = i;
                    } else if (!isSE && segStart !== null) {
                        const seg = windV.slice(segStart, i);
                        if (seg.length > 1) {
                            const sPts = seg.map(q => `${xOf(new Date(q.time).getTime()).toFixed(1)},${windY(q.speed)}`).join(' ');
                            out.push(`<polyline points="${sPts}" style="stroke:var(--color-wind-se);fill:none;" stroke-width="2" clip-path="url(#mc-clip)"/>`);
                        }
                        segStart = null;
                    }
                }
            }

            // ── "ahora" dashed vertical line ─────────────────────────────────
            const xNow = xOf(nowMs);
            if (xNow >= P.l && xNow <= W - P.r) {
                const xN = xNow.toFixed(1);
                out.push(`<line x1="${xN}" y1="${P.t}" x2="${xN}" y2="${P.t+ph}" stroke="#9ca3af" stroke-width="1" stroke-dasharray="3 3" opacity="0.5"/>`);
                out.push(`<text x="${xN}" y="${P.t - 3}" text-anchor="middle" font-size="7.5" fill="#9ca3af">ahora</text>`);
            }

            // ── Y axis labels ─────────────────────────────────────────────────
            [0.0, 0.5, 1.0, 1.5, 2.0, 2.5].forEach(v => {
                const y = yOf(v);
                out.push(`<text x="${P.l - 4}" y="${(y + 3).toFixed(1)}" text-anchor="end" font-size="8" fill="#9ca3af">${v.toFixed(1)}</text>`);
            });

            // ── X axis: day boundary labels ───────────────────────────────────
            // Argentina = UTC-3 (no DST). Midnight AR = 03:00 UTC.
            const MON = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            let prevDay = '';
            for (let ms = Math.floor(startMs / 3600000) * 3600000; ms <= endMs; ms += 3600000) {
                const utcD = new Date(ms);
                // Shift to Argentina local: subtract 3h
                const argD = new Date(ms - 3 * 3600000);
                if (argD.getUTCHours() === 0) {
                    const ds = argD.toISOString().substring(0, 10);
                    if (ds !== prevDay) {
                        prevDay = ds;
                        const x = xOf(ms);
                        if (x >= P.l + 8 && x <= W - P.r - 8) {
                            const day   = parseInt(ds.substring(8, 10));
                            const month = parseInt(ds.substring(5, 7)) - 1;
                            out.push(`<line x1="${x.toFixed(1)}" y1="${P.t}" x2="${x.toFixed(1)}" y2="${P.t+ph}" stroke="#9ca3af" stroke-width="0.5" opacity="0.2"/>`);
                            out.push(`<text x="${x.toFixed(1)}" y="${H - 4}" text-anchor="middle" font-size="8" fill="#9ca3af">${day}-${MON[month]}</text>`);
                        }
                    }
                }
            }

            return `<svg viewBox="0 0 ${W} ${H}" style="width:100%;height:auto;display:block;" aria-hidden="true">${out.join('')}</svg>`;
        },
    };
}
</script>
