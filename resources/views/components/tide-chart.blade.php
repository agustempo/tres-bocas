<div x-data="tideChart()" x-init="init()">

    {{-- Loading skeleton --}}
    <div x-show="loading" class="animate-pulse space-y-3 py-2">
        <div class="flex justify-between items-center">
            <div class="space-y-1.5">
                <div class="h-3.5 bg-gray-200 dark:bg-gray-700 rounded w-44"></div>
                <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded w-32"></div>
            </div>
            <div class="flex gap-3">
                <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded w-16"></div>
                <div class="h-3 bg-gray-100 dark:bg-gray-800 rounded w-16"></div>
            </div>
        </div>
        <div class="h-[200px] bg-gray-100 dark:bg-gray-800 rounded-xl"></div>
        <div class="flex gap-2">
            <div class="h-7 bg-gray-100 dark:bg-gray-800 rounded-lg w-16"></div>
            <div class="h-7 bg-gray-100 dark:bg-gray-800 rounded-lg w-16"></div>
            <div class="h-7 bg-gray-100 dark:bg-gray-800 rounded-lg w-16"></div>
        </div>
    </div>

    {{-- Fallback: PNG image from INA --}}
    <div x-show="!loading && fetchError" x-cloak class="space-y-3">
        <div class="flex items-center gap-2 text-xs text-amber-600 dark:text-amber-400
                    bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800
                    rounded-lg px-3 py-2">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span>{{ __('ui.tide_fallback_banner') }}</span>
        </div>
        <img src="{{ \App\Services\TideService::CHART_IMAGE }}"
             alt="{{ __('ui.tide_chart_title') }}"
             class="w-full h-auto rounded-xl"
             loading="lazy"
             onerror="this.style.display='none'">
        <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
            <a href="{{ \App\Services\TideService::CHART_SOURCE }}"
               target="_blank" rel="noopener"
               class="hover:underline">{{ __('ui.chart_source_label') }}</a>
        </p>
    </div>

    {{-- Live SVG chart --}}
    <div x-show="!loading && !fetchError" x-cloak class="space-y-3">

        {{-- Header: title + legend --}}
        <div class="flex flex-wrap items-start justify-between gap-y-2 gap-x-4">
            <div>
                <p class="font-bold text-sm text-gray-800 dark:text-gray-100">
                    {{ __('ui.tide_chart_title') }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                    {{ __('ui.tide_chart_subtitle') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full inline-block shrink-0"
                          style="background:var(--color-tide-observed);"></span>
                    {{ __('ui.tide_legend_observed') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-5 h-px shrink-0"
                          style="background:var(--color-tide-forecast);height:2px;"></span>
                    {{ __('ui.tide_legend_forecast') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-5 shrink-0 rounded-sm opacity-40"
                          style="background:var(--color-tide-band);height:8px;"></span>
                    {{ __('ui.tide_legend_error_band') }}
                </span>
            </div>
        </div>

        {{-- SVG chart container --}}
        <div x-html="svgContent" class="w-full overflow-hidden"></div>

        {{-- Range selector tabs --}}
        <div class="flex gap-2">
            @foreach ([2 => 'tide_range_2d', 4 => 'tide_range_4d', 7 => 'tide_range_7d'] as $days => $key)
            <button
                @click="setRange({{ $days }})"
                :class="range === {{ $days }}
                    ? 'bg-gray-800 text-white dark:bg-gray-100 dark:text-gray-900'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                class="px-4 py-1.5 rounded-lg text-xs font-medium transition-colors">
                {{ __('ui.' . $key) }}
            </button>
            @endforeach
        </div>

        {{-- Threshold legend --}}
        <div class="flex flex-wrap gap-4 text-xs">
            <span class="flex items-center gap-1.5 text-red-500 dark:text-red-400">
                <span class="inline-block w-5" style="border-top:2px dashed currentColor;"></span>
                {{ __('ui.tide_threshold_evacuation') }}
            </span>
            <span class="flex items-center gap-1.5 text-amber-500 dark:text-amber-400">
                <span class="inline-block w-5" style="border-top:2px dashed currentColor;"></span>
                {{ __('ui.tide_threshold_alert') }}
            </span>
            <span class="flex items-center gap-1.5" style="color:var(--color-tide-forecast);">
                <span class="inline-block w-5" style="border-top:1.5px solid currentColor;"></span>
                {{ __('ui.tide_normal_range') }}
            </span>
        </div>

        {{-- Source attribution --}}
        <p class="text-xs text-gray-400 dark:text-gray-500">
            ⓘ
            <a href="https://alerta.ina.gob.ar"
               target="_blank" rel="noopener"
               class="hover:underline hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                {{ __('ui.tide_source_attribution') }}
            </a>
        </p>

        {{-- Upcoming forecast table --}}
        <div x-show="tableRows.length > 0" class="pt-1">
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">
                {{ __('ui.tide_table_title') }}
            </h3>
            <div class="grid grid-cols-2 gap-x-6">
                <template x-for="row in tableRows" :key="row.time">
                    <div class="flex items-baseline justify-between py-1.5
                                border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500 dark:text-gray-400 text-xs tabular-nums"
                              x-text="formatLabel(row.time)"></span>
                        <span class="font-medium tabular-nums text-sm"
                              :class="row.value > 2.0 || row.value < 0.7
                                  ? 'text-amber-500 dark:text-amber-400'
                                  : 'text-gray-700 dark:text-gray-300'"
                              x-text="row.value.toFixed(2) + ' m'"></span>
                    </div>
                </template>
            </div>
        </div>

    </div>

</div>

<script>
function tideChart() {
    return {
        loading:    true,
        fetchError: false,
        tideData:   null,
        range:      4,
        svgContent: '',

        async init() {
            try {
                const ctrl  = new AbortController();
                const timer = setTimeout(() => ctrl.abort(), 10000);
                const resp  = await fetch('/api/tide-data', { signal: ctrl.signal });
                clearTimeout(timer);
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                this.tideData  = await resp.json();
                this.svgContent = this.buildSvg();
            } catch (e) {
                console.error('[tide-chart]', e.message ?? e);
                this.fetchError = true;
            } finally {
                this.loading = false;
            }
        },

        setRange(days) {
            this.range      = days;
            this.svgContent = this.buildSvg();
        },

        get tableRows() {
            if (!this.tideData?.data) return [];
            const nowMs = new Date(this.tideData.now).getTime();
            return this.tideData.data
                .filter(p => p.type === 'forecast' && new Date(p.time).getTime() > nowMs)
                .filter(p => parseInt(p.time.substring(11, 13)) % 6 === 0)
                .slice(0, 12);
        },

        // "2026-05-22T06:00:00-03:00" → "22 · 06hs"
        formatLabel(isoStr) {
            return parseInt(isoStr.substring(8, 10)) + ' · ' + isoStr.substring(11, 13) + 'hs';
        },

        buildSvg() {
            const d = this.tideData;
            if (!d?.data?.length) return '';

            const W = 560, H = 200;
            const P = { t: 14, r: 20, b: 36, l: 38 };
            const pw = W - P.l - P.r;  // 502
            const ph = H - P.t - P.b;  // 150
            const Y_MIN = 0, Y_MAX = 3.6;

            const nowMs   = new Date(d.now).getTime();
            const startMs = nowMs - 24 * 3600000;
            const endMs   = nowMs + this.range * 24 * 3600000;

            const visible = d.data.filter(p => {
                const t = new Date(p.time).getTime();
                return t >= startMs && t <= endMs;
            });
            if (!visible.length) return '';

            const xOf   = ms => P.l + (ms - startMs) / (endMs - startMs) * pw;
            const yOf   = v  => P.t + ph * (1 - (v - Y_MIN) / (Y_MAX - Y_MIN));
            const clamp = (n, lo, hi) => n < lo ? lo : n > hi ? hi : n;
            const fx    = p => xOf(new Date(p.time).getTime()).toFixed(1);
            const fy    = (v) => clamp(yOf(v), P.t, P.t + ph).toFixed(1);

            const obs = visible.filter(p => p.type === 'observed');
            const frc = visible.filter(p => p.type === 'forecast');

            const out = [];

            // ── defs ──────────────────────────────────────────────────────────
            out.push(`<defs><clipPath id="tc-clip"><rect x="${P.l}" y="${P.t}" width="${pw}" height="${ph}"/></clipPath></defs>`);

            // ── grid lines ────────────────────────────────────────────────────
            for (let v = 0; v <= 3.0; v += 0.5) {
                const y = yOf(v).toFixed(1);
                out.push(`<line x1="${P.l}" y1="${y}" x2="${W - P.r}" y2="${y}" stroke="#888" stroke-width="0.5" opacity="0.08"/>`);
            }

            // ── normal zone (0.7 – 2.2 m) ────────────────────────────────────
            const yNT = yOf(2.2).toFixed(1), yNB = yOf(0.7).toFixed(1);
            out.push(`<rect x="${P.l}" y="${yNT}" width="${pw}" height="${(yNB - yNT).toFixed(1)}" style="fill:var(--color-tide-band);" opacity="0.05"/>`);

            // ── threshold lines ───────────────────────────────────────────────
            const yEvac  = yOf(d.thresholds.evacuation).toFixed(1);
            const yAlert = yOf(d.thresholds.alert).toFixed(1);
            out.push(`<line x1="${P.l}" y1="${yEvac}" x2="${W - P.r}" y2="${yEvac}" stroke="#ef4444" stroke-width="1" stroke-dasharray="4 3" opacity="0.7"/>`);
            out.push(`<line x1="${P.l}" y1="${yAlert}" x2="${W - P.r}" y2="${yAlert}" stroke="#f59e0b" stroke-width="1" stroke-dasharray="4 3" opacity="0.65"/>`);

            // ── error band polygon ────────────────────────────────────────────
            if (frc.length > 1) {
                const hiPts = frc.filter(p => p.error_hi != null)
                    .map(p => `${fx(p)},${fy(p.error_hi)}`);
                const loPts = frc.filter(p => p.error_lo != null)
                    .slice().reverse()
                    .map(p => `${fx(p)},${fy(p.error_lo)}`);
                if (hiPts.length && loPts.length) {
                    out.push(`<polygon points="${[...hiPts, ...loPts].join(' ')}" style="fill:var(--color-tide-band);" opacity="0.12" clip-path="url(#tc-clip)"/>`);
                }
            }

            // ── observed polyline ─────────────────────────────────────────────
            if (obs.length > 1) {
                const pts = obs.map(p => `${fx(p)},${fy(p.value)}`).join(' ');
                out.push(`<polyline points="${pts}" style="stroke:var(--color-tide-observed);fill:none;" stroke-width="1.5" stroke-linejoin="round" clip-path="url(#tc-clip)"/>`);
            }

            // ── observed dots every 6 h ───────────────────────────────────────
            obs.forEach(p => {
                if (parseInt(p.time.substring(11, 13)) % 6 === 0) {
                    out.push(`<circle cx="${fx(p)}" cy="${fy(p.value)}" r="2.5" style="fill:var(--color-tide-observed);" clip-path="url(#tc-clip)"/>`);
                }
            });

            // ── forecast polyline ─────────────────────────────────────────────
            if (frc.length > 1) {
                const pts = frc.map(p => `${fx(p)},${fy(p.value)}`).join(' ');
                out.push(`<polyline points="${pts}" style="stroke:var(--color-tide-forecast);fill:none;" stroke-width="1.75" stroke-linejoin="round" clip-path="url(#tc-clip)"/>`);
            }

            // ── "ahora" vertical dashed line ──────────────────────────────────
            const xNow = xOf(nowMs);
            if (xNow >= P.l && xNow <= W - P.r) {
                const xN = xNow.toFixed(1);
                out.push(`<line x1="${xN}" y1="${P.t}" x2="${xN}" y2="${P.t + ph}" stroke="#9ca3af" stroke-width="1" stroke-dasharray="3 3" opacity="0.55"/>`);
                out.push(`<text x="${xN}" y="${P.t - 3}" text-anchor="middle" font-size="7.5" fill="#9ca3af">ahora</text>`);
            }

            // ── current level: latest observed dot + label ────────────────────
            if (obs.length) {
                const curr = obs[obs.length - 1];
                const cx   = xOf(new Date(curr.time).getTime());
                const cy   = parseFloat(fy(curr.value));
                const near = cx > W - P.r - 55;
                const lx   = (near ? cx - 7 : cx + 7).toFixed(1);
                const anch = near ? 'end' : 'start';
                out.push(`<circle cx="${cx.toFixed(1)}" cy="${cy.toFixed(1)}" r="4" style="fill:var(--color-tide-observed);"/>`);
                out.push(`<text x="${lx}" y="${(cy - 6).toFixed(1)}" text-anchor="${anch}" font-size="9" font-weight="700" style="fill:var(--color-tide-observed);">${curr.value.toFixed(2)} m</text>`);
            }

            // ── Y axis labels ─────────────────────────────────────────────────
            [0.0, 0.5, 1.0, 1.5, 2.0, 2.5].forEach(v => {
                const y = yOf(v);
                out.push(`<text x="${P.l - 4}" y="${(y + 3).toFixed(1)}" text-anchor="end" font-size="8" fill="#9ca3af">${v.toFixed(1)}</text>`);
            });

            // ── X axis: day boundary labels ───────────────────────────────────
            const MON = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            let prevDate = null;
            visible.forEach(p => {
                const date = p.time.substring(0, 10);
                if (date !== prevDate) {
                    prevDate = date;
                    const x = xOf(new Date(p.time).getTime());
                    if (x >= P.l + 8 && x <= W - P.r - 8) {
                        const xS = x.toFixed(1);
                        const day   = parseInt(date.substring(8, 10));
                        const month = parseInt(date.substring(5, 7)) - 1;
                        out.push(`<line x1="${xS}" y1="${P.t}" x2="${xS}" y2="${P.t + ph}" stroke="#9ca3af" stroke-width="0.5" opacity="0.25"/>`);
                        out.push(`<text x="${xS}" y="${H - 4}" text-anchor="middle" font-size="8" fill="#9ca3af">${day}-${MON[month]}</text>`);
                    }
                }
            });

            return `<svg viewBox="0 0 ${W} ${H}" style="width:100%;height:auto;display:block;" aria-hidden="true">${out.join('')}</svg>`;
        },
    };
}
</script>
