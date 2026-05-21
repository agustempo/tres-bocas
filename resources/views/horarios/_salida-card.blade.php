{{--
  Partial: _salida-card
  Expected variables (from $salidas map or inline @include):
    $patron, $hora (Carbon), $esPasado, $esReciente, $esProximo, $min, $minAtras,
    $avistajeActivo, $nConfirmaciones, $yaConfirmo, $horaTigre (Carbon|null),
    $miReaccion (string: 'positivo'|'negativo'|'')
  $muelle available from parent scope.
--}}
@php
    $esIda       = $patron->sentido === 'ida';
    $horaMostrar = ($esIda && $horaTigre) ? $horaTigre : $hora;

    if ($esReciente) {
        $countdownText  = __('movilidad.schedule_recent_ago', ['time' => $minAtras . ' min']);
        $countdownColor = '#6b7280';
    } elseif ($min <= 0) {
        $countdownText  = __('ui.countdown_passed');
        $countdownColor = '#6b7280';
    } elseif ($min < 60) {
        $countdownText  = __('movilidad.salida_en_min', ['min' => $min]);
        $countdownColor = $min <= 15 ? '#ef4444' : ($min <= 30 ? '#f59e0b' : '#10b981');
    } else {
        $h = (int) floor($min / 60);
        $m = $min % 60;
        $countdownText  = __('movilidad.salida_en_horas', ['h' => $h, 'm' => $m]);
        $countdownColor = '#10b981';
    }

    if ($avistajeActivo) {
        $countdownText  = $avistajeActivo->tipoLabel();
        $countdownColor = $avistajeActivo->tipo === 'cancelado' ? '#ef4444' : '#f59e0b';
    }
@endphp

<div x-data="{
        voted:       @js($miReaccion),
        reportando:  false,
        posCount:    {{ $nConfirmaciones }},
        negCount:    {{ $avistajeActivo ? $avistajeActivo->confirmaciones : 0 }},
        tipo:        '',
        sentLabel:   '',
        notaVisible: false,
        sent:        false,
        async vote(voto) {
            if (this.voted) return;
            this.voted = voto;
            if (voto === 'positivo') { this.posCount++; }
            else { this.negCount++; this.reportando = true; }
            fetch('{{ route('horarios.salidas.reaccionar', $patron) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({ tipo: voto })
            });
        }
     }"
     class="rounded-2xl border overflow-hidden
            {{ $esReciente
                ? 'border-teal-200 dark:border-teal-800 bg-white dark:bg-gray-800/60 opacity-85'
                : ($esProximo
                    ? 'border-amber-300 dark:border-amber-700 bg-white dark:bg-gray-800/60'
                    : 'border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800/50') }}">

    {{-- Sighting strip --}}
    @if($avistajeActivo)
    <div class="px-4 py-2 border-b border-amber-100 dark:border-amber-800/30
                bg-amber-50/50 dark:bg-amber-900/10">
        <p class="text-[11px] text-amber-700 dark:text-amber-400 leading-tight">
            {{ __('movilidad.departure_sighting_strip', [
                'type' => $avistajeActivo->tipoLabel(),
                'name' => $avistajeActivo->user?->name ?? '',
                'time' => $avistajeActivo->hora_evento->diffForHumans(),
            ]) }}
        </p>
    </div>
    @endif

    {{-- Main info row --}}
    <div class="px-4 py-3.5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-baseline gap-2 shrink-0">
                <span class="font-black font-mono tabular-nums leading-none
                             {{ $esProximo ? 'text-gray-900 dark:text-gray-50' : 'text-gray-800 dark:text-gray-100' }}"
                      style="font-size:1.75rem;line-height:1">
                    {{ $horaMostrar->format('H:i') }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ __('movilidad.tolerancia_label', ['min' => $patron->ventana_min]) }}
                </span>
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                    {{ $patron->servicio?->nombre }}
                </p>
                @if($esIda && $horaTigre)
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ __('movilidad.llega_muelle_aprox', ['hora' => $hora->format('H:i')]) }}
                </p>
                @endif
            </div>

            <div class="shrink-0 flex flex-col items-end gap-1">
                @if($esProximo && !$avistajeActivo)
                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full"
                      style="background:#14b8a6;color:#fff;">
                    {{ __('movilidad.proxima_label') }}
                </span>
                @endif
                @if($esReciente && !$avistajeActivo)
                <span class="text-[10px] text-gray-400 dark:text-gray-500">
                    {{ __('movilidad.schedule_recent_hint') }}
                </span>
                @endif
                @if($esReciente && !$avistajeActivo)
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $countdownText }}
                </span>
                @else
                <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                      style="background:{{ $countdownColor }}1a;color:{{ $countdownColor }}">
                    {{ $countdownText }}
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Reaction row --}}
    <div class="px-4 pb-3.5 flex items-center gap-2">
        <button @click="vote('positivo')"
                :disabled="!!voted"
                :class="{
                    'border-teal-300 dark:border-teal-700 bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300': voted === 'positivo',
                    'opacity-40 cursor-default border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-600': voted && voted !== 'positivo',
                    'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-teal-300 dark:hover:border-teal-700': !voted
                }"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium border transition-colors">
            <span>👍</span>
            <span x-show="posCount > 0" x-text="posCount"></span>
        </button>

        <button @click="vote('negativo')"
                :disabled="!!voted"
                :class="{
                    'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400': voted === 'negativo',
                    'opacity-40 cursor-default border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-600': voted && voted !== 'negativo',
                    'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-red-300 dark:hover:border-red-700': !voted
                }"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium border transition-colors">
            <span>👎</span>
            <span x-show="negCount > 0" x-text="negCount"></span>
        </button>
    </div>

    {{-- ── AUTH: full report panel ──────────────────────────────────────── --}}
    @auth
    <div x-show="reportando" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="border-t border-gray-100 dark:border-gray-700/50">

        {{-- Success state --}}
        <div x-show="sent"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="px-4 py-6 text-center bg-teal-50/30 dark:bg-teal-900/10">
            <p class="text-2xl mb-2">🙌</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                {{ __('movilidad.sighting_submit_success_title') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"
               x-text="'{{ __('movilidad.sighting_submit_success_subtitle') }}'.replace(':type', sentLabel)">
            </p>
        </div>

        {{-- Form state --}}
        <div x-show="!sent" class="px-4 py-4 space-y-3 bg-gray-50/50 dark:bg-gray-800/30">

            {{-- Context header --}}
            <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                <span class="font-mono font-bold text-gray-600 dark:text-gray-300">
                    {{ $horaMostrar->format('H:i') }}
                </span>
                <span>·</span>
                <span>{{ $patron->servicio?->nombre }}</span>
                <span>·</span>
                <span style="color:{{ $countdownColor }}">{{ $countdownText }}</span>
            </div>

            {{-- Title + subtitle --}}
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('movilidad.sighting_panel_title') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ __('movilidad.sighting_panel_subtitle') }}
                </p>
            </div>

            <form method="POST" action="{{ route('movilidad.avistajes.store') }}"
                  @submit.prevent="if (!tipo) return; sent = true; setTimeout(() => $el.submit(), 2200)">
                @csrf
                <input type="hidden" name="muelle_id"   value="{{ $muelle->id }}">
                <input type="hidden" name="servicio_id" value="{{ $patron->servicio_id }}">
                <input type="hidden" name="patron_id"   value="{{ $patron->id }}">
                <input type="hidden" name="hora_exacta" value="{{ substr($patron->hora_referencia, 0, 5) }}">
                <input type="hidden" name="sentido"
                       value="{{ in_array($patron->sentido, ['ida','vuelta']) ? $patron->sentido : 'vuelta' }}">
                <input type="hidden" name="tipo" x-model="tipo">

                {{-- Option buttons --}}
                <div class="grid grid-cols-2 gap-2">
                    @foreach([
                        ['demorado',        '⏱️', 'avistaje_demorado',        'amber'],
                        ['cancelado',       '❌', 'avistaje_cancelado',       'red'],
                        ['problema_muelle', '💧', 'avistaje_problema_muelle', 'amber'],
                        ['otro',            '💬', 'avistaje_otro',            'amber'],
                    ] as [$val, $icon, $key, $color])
                    <button type="button"
                            @click="tipo = '{{ $val }}'; sentLabel = $el.querySelector('.lbl').textContent.trim()"
                            :class="tipo === '{{ $val }}'
                                ? '{{ $color === 'red'
                                    ? 'border-red-400 dark:border-red-600 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300'
                                    : 'border-amber-400 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' }}'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="flex items-center gap-2 px-3 py-2.5 rounded-xl border text-sm font-medium text-left transition-all">
                        <span class="shrink-0 text-base leading-none">{{ $icon }}</span>
                        <span class="lbl leading-tight">{{ __('movilidad.' . $key) }}</span>
                    </button>
                    @endforeach
                </div>

                {{-- Note toggle --}}
                <div class="pt-1">
                    <button type="button"
                            @click="notaVisible = !notaVisible"
                            class="text-xs text-gray-400 dark:text-gray-500
                                   hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                        <span x-show="!notaVisible">{{ __('movilidad.sighting_note_toggle_open') }}</span>
                        <span x-show="notaVisible" x-cloak>{{ __('movilidad.sighting_note_toggle_close') }}</span>
                    </button>
                    <div x-show="notaVisible" x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-2">
                        <textarea name="notas"
                                  maxlength="280"
                                  placeholder="{{ __('movilidad.sighting_note_placeholder') }}"
                                  style="height:72px;resize:none;"
                                  class="w-full text-sm px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                                         bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                         placeholder-gray-400 dark:placeholder-gray-500
                                         focus:outline-none focus:ring-2 focus:ring-teal-400"></textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            :class="tipo
                                ? 'opacity-100 cursor-pointer'
                                : 'opacity-40 cursor-not-allowed pointer-events-none'"
                            class="flex-1 py-2.5 rounded-xl text-sm font-semibold
                                   bg-teal-600 hover:bg-teal-700 text-white transition-opacity">
                        {{ __('movilidad.sighting_btn_send') }}
                    </button>
                    <button type="button"
                            @click="reportando = false; tipo = ''; sentLabel = ''; notaVisible = false; sent = false"
                            class="px-4 py-2.5 rounded-xl text-sm border border-gray-200 dark:border-gray-700
                                   text-gray-500 dark:text-gray-400
                                   hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        {{ __('movilidad.sighting_btn_cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endauth

    {{-- ── GUEST: login prompt ──────────────────────────────────────────── --}}
    @guest
    <div x-show="reportando" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="border-t border-red-100 dark:border-red-900/30 px-4 py-4
                bg-red-50/40 dark:bg-red-900/10">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
            {{ __('movilidad.departure_reaction_negative_title') }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mb-3">
            {{ __('movilidad.departure_reaction_negative_subtitle') }}
        </p>
        <div class="flex items-center gap-3">
            <a href="{{ route('login') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold
                      text-white bg-teal-600 hover:bg-teal-700 transition-colors">
                {{ __('movilidad.departure_reaction_login_cta') }}
            </a>
            <button @click="reportando = false"
                    class="text-xs text-gray-400 dark:text-gray-500
                           hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                {{ __('movilidad.departure_reaction_dismiss_cta') }}
            </button>
        </div>
    </div>
    @endguest

</div>
