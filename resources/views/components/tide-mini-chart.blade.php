{{-- tide-mini-chart: live mini chart + next extremes, fetched from /api/tide-data --}}
@props(['tide' => null])

@php
    $fallback = collect($tide['forecast'] ?? [])->take(3)->values()->toArray();
@endphp

<div x-data="tideMiniChart()" x-init="init()">

    {{-- Section heading --}}
    <p class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 px-1 mb-2">
        {{ __('ui.upcoming_tides_heading') }}
    </p>

    {{-- Loading skeleton --}}
    <div x-show="loading" class="animate-pulse space-y-2">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem">
            <div class="h-20 bg-gray-100 dark:bg-gray-800 rounded-2xl"></div>
            <div class="h-20 bg-gray-100 dark:bg-gray-800 rounded-2xl"></div>
            <div class="h-20 bg-gray-100 dark:bg-gray-800 rounded-2xl"></div>
        </div>
        <div class="h-20 bg-gray-100 dark:bg-gray-800 rounded-xl mt-1"></div>
    </div>

    {{-- Live content: slots from API + mini chart --}}
    <div x-show="!loading && slots.length > 0" x-cloak class="space-y-2">

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem">
            <template x-for="slot in slots" :key="slot.time">
                <div class="flex flex-col items-center gap-1 rounded-2xl border py-4 px-2"
                     :class="slot.type === 'max'
                         ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-900/50'
                         : 'bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700'">
                    <span class="text-xl leading-none" x-text="slot.type === 'max' ? '⬆️' : '⬇️'"></span>
                    <span class="text-lg font-black tabular-nums text-gray-800 dark:text-gray-100 leading-tight"
                          x-text="slot.time.substring(11, 16)"></span>
                    <span class="text-sm font-bold tabular-nums"
                          :class="slot.type === 'max'
                              ? 'text-blue-600 dark:text-blue-400'
                              : 'text-gray-500 dark:text-gray-400'"
                          x-text="slot.value.toFixed(2) + ' m'"></span>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 text-center leading-tight"
                          x-text="slotDayLabel(slot.time)"></span>
                </div>
            </template>
        </div>

        <div x-html="svgContent" class="w-full overflow-hidden rounded-xl"></div>

        <div class="flex items-center justify-between px-0.5 mt-0.5">
            <a href="{{ route('marea.index') }}"
               class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                {{ __('ui.tide_mini_see_all') }} →
            </a>
            <span class="text-[10px] text-gray-400 dark:text-gray-500">Fuente: INA</span>
        </div>

    </div>

    {{-- Static fallback: shown when API unavailable after load --}}
    <div x-show="!loading && slots.length === 0" x-cloak class="space-y-2">

        @if(!empty($fallback))
        @php
            $fbColorMap = [
                'red'    => ['num' => 'text-red-600 dark:text-red-400',       'bg' => 'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-900/50'],
                'orange' => ['num' => 'text-orange-500 dark:text-orange-400', 'bg' => 'bg-orange-50 dark:bg-orange-900/20 border-orange-100 dark:border-orange-900/50'],
                'yellow' => ['num' => 'text-yellow-600 dark:text-yellow-400', 'bg' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-100 dark:border-yellow-900/50'],
                'green'  => ['num' => 'text-green-700 dark:text-green-400',   'bg' => 'bg-green-50 dark:bg-green-900/20 border-green-100 dark:border-green-900/50'],
                'gray'   => ['num' => 'text-gray-500 dark:text-gray-400',     'bg' => 'bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700'],
            ];
        @endphp
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem">
            @foreach($fallback as $evt)
            @php
                $isPleamar = str_contains(mb_strtolower($evt['type']), 'plea');
                $fbc = $fbColorMap[$evt['status']['color'] ?? 'gray'] ?? $fbColorMap['gray'];
            @endphp
            <div class="flex flex-col items-center gap-1 rounded-2xl border {{ $fbc['bg'] }} py-4 px-2">
                <span class="text-xl leading-none">{{ $isPleamar ? '⬆️' : '⬇️' }}</span>
                <span class="text-lg font-black tabular-nums text-gray-800 dark:text-gray-100 leading-tight">
                    {{ $evt['time'] }}
                </span>
                @if($evt['level'])
                <span class="text-sm font-bold tabular-nums {{ $fbc['num'] }}">{{ $evt['level'] }} m</span>
                @endif
                <span class="text-[10px] text-gray-400 dark:text-gray-500 text-center leading-tight">
                    {{ $evt['day_label'] ?? '' }}
                </span>
            </div>
            @endforeach
        </div>
        @endif

        <div class="flex items-center justify-between px-0.5 mt-0.5">
            <a href="{{ route('marea.index') }}"
               class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                {{ __('ui.tide_mini_see_all') }} →
            </a>
        </div>

    </div>

</div>

<script>
function tideMiniChart() {
    return {
        loading:    true,
        tideData:   null,
        slots:      [],
        svgContent: '',

        async init() {
            try {
                const ctrl  = new AbortController();
                const timer = setTimeout(() => ctrl.abort(), 10000);
                const resp  = await fetch('/api/tide-data', { signal: ctrl.signal });
                clearTimeout(timer);
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                this.tideData  = await resp.json();
                this.slots     = this.findExtremes();
                this.svgContent = this.buildSvg();
            } catch (e) {
                console.error('[tide-mini]', e.message ?? e);
            } finally {
                this.loading = false;
            }
        },

        findExtremes() {
            if (!this.tideData?.data) return [];
            const nowMs = new Date(this.tideData.now).getTime();
            const frc = this.tideData.data.filter(
                p => p.type === 'forecast' && new Date(p.time).getTime() > nowMs
            );

            const extremes = [];
            for (let i = 1; i < frc.length - 1; i++) {
                const prev = frc[i - 1].value;
                const curr = frc[i].value;
                const next = frc[i + 1].value;
                if (curr > prev && curr > next) {
                    extremes.push({ type: 'max', time: frc[i].time, value: frc[i].value });
                } else if (curr < prev && curr < next) {
                    extremes.push({ type: 'min', time: frc[i].time, value: frc[i].value });
                }
            }
            return extremes.slice(0, 3);
        },

        slotDayLabel(isoStr) {
            const nowDate  = this.tideData.now.substring(0, 10);
            const slotDate = isoStr.substring(0, 10);
            if (slotDate === nowDate) return 'Hoy';

            const [y, m, d] = nowDate.split('-').map(Number);
            const tom = new Date(y, m - 1, d + 1);
            const tomorrowDate = tom.getFullYear() + '-'
                + String(tom.getMonth() + 1).padStart(2, '0') + '-'
                + String(tom.getDate()).padStart(2, '0');
            if (slotDate === tomorrowDate) return 'Mañana';

            const MON = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            return parseInt(isoStr.substring(8, 10)) + ' ' + MON[parseInt(isoStr.substring(5, 7)) - 1];
        },

        buildSvg() {
            const d = this.tideData;
            if (!d?.data?.length) return '';

            const W = 360, H = 80;
            const P = { t: 4, r: 6, b: 14, l: 6 };
            const pw = W - P.l - P.r;
            const ph = H - P.t - P.b;
            const Y_MIN = 0, Y_MAX = 3.6;

            const nowMs   = new Date(d.now).getTime();
            const startMs = nowMs - 6 * 3600000;
            const endMs   = nowMs + 48 * 3600000;

            const visible = d.data.filter(p => {
                const t = new Date(p.time).getTime();
                return t >= startMs && t <= endMs;
            });
            if (!visible.length) return '';

            const xOf   = ms  => P.l + (ms - startMs) / (endMs - startMs) * pw;
            const yOf   = v   => P.t + ph * (1 - (v - Y_MIN) / (Y_MAX - Y_MIN));
            const clamp = (n, lo, hi) => n < lo ? lo : n > hi ? hi : n;
            const fx    = p   => xOf(new Date(p.time).getTime()).toFixed(1);
            const fy    = v   => clamp(yOf(v), P.t, P.t + ph).toFixed(1);

            const obs = visible.filter(p => p.type === 'observed');
            const frc = visible.filter(p => p.type === 'forecast');

            const out = [];

            out.push(`<defs><clipPath id="tmc-clip"><rect x="${P.l}" y="${P.t}" width="${pw}" height="${ph}"/></clipPath></defs>`);

            // error band
            if (frc.length > 1) {
                const hiPts = frc.filter(p => p.error_hi != null).map(p => `${fx(p)},${fy(p.error_hi)}`);
                const loPts = frc.filter(p => p.error_lo != null).slice().reverse().map(p => `${fx(p)},${fy(p.error_lo)}`);
                if (hiPts.length && loPts.length) {
                    out.push(`<polygon points="${[...hiPts, ...loPts].join(' ')}" style="fill:var(--color-tide-band);" opacity="0.12" clip-path="url(#tmc-clip)"/>`);
                }
            }

            // observed line
            if (obs.length > 1) {
                const pts = obs.map(p => `${fx(p)},${fy(p.value)}`).join(' ');
                out.push(`<polyline points="${pts}" style="stroke:var(--color-tide-observed);fill:none;" stroke-width="1.5" stroke-linejoin="round" clip-path="url(#tmc-clip)"/>`);
            }

            // forecast line (dashed)
            if (frc.length > 1) {
                const pts = frc.map(p => `${fx(p)},${fy(p.value)}`).join(' ');
                out.push(`<polyline points="${pts}" style="stroke:var(--color-tide-forecast);fill:none;" stroke-width="1.5" stroke-linejoin="round" stroke-dasharray="4 2" clip-path="url(#tmc-clip)"/>`);
            }

            // "ahora" vertical line
            const xNow = xOf(nowMs);
            if (xNow >= P.l && xNow <= W - P.r) {
                out.push(`<line x1="${xNow.toFixed(1)}" y1="${P.t}" x2="${xNow.toFixed(1)}" y2="${P.t + ph}" stroke="#9ca3af" stroke-width="0.75" stroke-dasharray="2 2" opacity="0.5"/>`);
            }

            // X axis: day boundary labels
            const MON = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
            let prevDate = null;
            visible.forEach(p => {
                const date = p.time.substring(0, 10);
                if (date !== prevDate) {
                    prevDate = date;
                    const x = xOf(new Date(p.time).getTime());
                    if (x >= P.l + 12 && x <= W - P.r - 12) {
                        const day   = parseInt(date.substring(8, 10));
                        const month = parseInt(date.substring(5, 7)) - 1;
                        out.push(`<line x1="${x.toFixed(1)}" y1="${P.t}" x2="${x.toFixed(1)}" y2="${P.t + ph}" stroke="#9ca3af" stroke-width="0.5" opacity="0.2"/>`);
                        out.push(`<text x="${x.toFixed(1)}" y="${H - 2}" text-anchor="middle" font-size="7" fill="#9ca3af">${day} ${MON[month]}</text>`);
                    }
                }
            });

            return `<svg viewBox="0 0 ${W} ${H}" style="width:100%;height:auto;display:block;" aria-hidden="true">${out.join('')}</svg>`;
        },
    };
}
</script>
