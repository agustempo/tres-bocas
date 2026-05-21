{{-- NextBoatCard — next departure from preferred dock with reactions and sighting panel --}}
@props(['muelle', 'proximo', 'avistaje' => null, 'confirmaciones' => 0, 'servicioPrincipal' => null, 'miReaccion' => ''])

@php
    use Carbon\Carbon;

    $hasMuelle  = $muelle !== null;
    $hasProximo = $proximo !== null;
    $patron     = null;

    if ($hasProximo) {
        $horaProximo = $proximo['hora'] instanceof Carbon
            ? $proximo['hora']
            : Carbon::parse($proximo['hora']);

        $min = (int) now()->diffInMinutes($horaProximo, false);

        if ($min <= 0) {
            $countdownLabel = __('ui.countdown_passed');
            $pillColor      = '#6b7280';
        } elseif ($min < 60) {
            $countdownLabel = __('ui.countdown_in_min', ['min' => $min]);
            $pillColor      = $min <= 15 ? '#ef4444' : ($min <= 30 ? '#f59e0b' : '#10b981');
        } else {
            $countdownLabel = __('ui.countdown_in_h', ['h' => (int) floor($min / 60)]);
            $pillColor      = '#10b981';
        }

        if ($avistaje) {
            $countdownLabel = $avistaje->tipoLabel();
            $pillColor      = $avistaje->tipo === 'cancelado' ? '#ef4444' : '#f59e0b';
        }

        $servicio = $proximo['patron']?->servicio ?? null;
        $patron   = $proximo['patron'] ?? null;
    }
@endphp

<div x-data="{
        cambiandoMuelle: false,
        voted:       @js($miReaccion),
        reportando:  false,
        posCount:    {{ $hasProximo ? $confirmaciones : 0 }},
        negCount:    {{ ($hasProximo && $avistaje) ? $avistaje->confirmaciones : 0 }},
        tipo:        '',
        sentLabel:   '',
        notaVisible: false,
        sent:        false,
        async vote(voto) {
            if (this.voted) return;
            this.voted = voto;
            if (voto === 'positivo') { this.posCount++; }
            else { this.negCount++; this.reportando = true; }
            fetch('{{ $patron ? route('horarios.salidas.reaccionar', $patron) : '#' }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({ tipo: voto })
            });
        }
     }"
     class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-50 dark:border-gray-800">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ __('ui.next_boat_heading') }}
        </span>
        @if($hasMuelle)
        <div class="flex items-center gap-3">
            <a href="{{ route('movilidad.muelles.show', $muelle->slug) }}"
               class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline"
               x-show="!cambiandoMuelle">
                {{ $muelle->nombre }}
            </a>
            <button @click="cambiandoMuelle = !cambiandoMuelle"
                    class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <span x-text="cambiandoMuelle ? '{{ __('ui.cancel') }}' : '{{ __('ui.change_dock') }}'"></span>
            </button>
        </div>
        @endif
    </div>

    {{-- Body --}}
    <div class="px-5 py-4">

        {{-- Inline dock picker --}}
        <div x-show="cambiandoMuelle" x-cloak class="mb-4">
            <x-dashboard.preferred-dock-prompt />
        </div>

        @if(!$hasMuelle)
            <x-dashboard.preferred-dock-prompt />

        @elseif($hasProximo)

            {{-- Main row: time + countdown pill --}}
            <a href="{{ route('horarios.index') }}" class="block">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="font-black tabular-nums leading-none
                                         {{ $avistaje && $avistaje->tipo === 'cancelado' ? 'text-red-500 dark:text-red-400' : 'text-gray-800 dark:text-gray-100' }}"
                                  style="font-size: 2.5rem; line-height: 1">
                                {{ $horaProximo->format('H:i') }}
                            </span>
                            @if($proximo['ventana'] ?? 0)
                            <span class="text-sm text-gray-400 dark:text-gray-500">
                                ± {{ $proximo['ventana'] }} min
                            </span>
                            @endif
                        </div>
                        @if($servicio)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">
                            {{ $servicio->nombre }}
                        </p>
                        @endif
                    </div>
                    @if($min > 0 || $avistaje)
                    <span class="shrink-0 inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold"
                          style="background:{{ $pillColor }}1a;color:{{ $pillColor }}">
                        {{ $countdownLabel }}
                    </span>
                    @endif
                </div>
            </a>

            {{-- Active incident strip --}}
            @if($avistaje)
            <div class="mt-3 flex items-center gap-2 rounded-xl px-3 py-2.5
                        bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50">
                <span class="text-xs text-amber-700 dark:text-amber-400 leading-snug">
                    {{ $avistaje->tipoLabel() }}
                    @if($avistaje->notas) · {{ Str::limit($avistaje->notas, 60) }} @endif
                    @if($avistaje->user) · {{ $avistaje->user->name }} @endif
                    · {{ $avistaje->hora_evento->diffForHumans() }}
                </span>
            </div>
            @endif

            {{-- Reaction row --}}
            <div class="mt-3 flex items-center gap-2">
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
                <a href="{{ route('horarios.index') }}"
                   class="ml-auto text-xs text-gray-400 dark:text-gray-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                    {{ __('ui.view_full_schedule') }} →
                </a>
            </div>

            {{-- ── Auth: sighting report panel (opens on 👎) ──────────────── --}}
            @auth
            <div x-show="reportando" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-3 border-t border-gray-100 dark:border-gray-700/50">

                {{-- Success state --}}
                <div x-show="sent"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="pt-5 pb-3 text-center">
                    <p class="text-2xl mb-2">🙌</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('movilidad.sighting_submit_success_title') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                       x-text="'{{ __('movilidad.sighting_submit_success_subtitle') }}'.replace(':type', sentLabel)">
                    </p>
                </div>

                {{-- Form state --}}
                <div x-show="!sent" class="pt-4 space-y-3">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('movilidad.sighting_panel_title') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('movilidad.sighting_panel_subtitle') }}
                    </p>

                    <form method="POST" action="{{ route('movilidad.avistajes.store') }}"
                          @submit.prevent="if (!tipo) return; sent = true; setTimeout(() => $el.submit(), 2200)">
                        @csrf
                        <input type="hidden" name="muelle_id"   value="{{ $muelle->id }}">
                        <input type="hidden" name="servicio_id" value="{{ $patron?->servicio_id }}">
                        <input type="hidden" name="patron_id"   value="{{ $patron?->id }}">
                        <input type="hidden" name="hora_exacta" value="{{ $patron ? substr($patron->hora_referencia, 0, 5) : '' }}">
                        <input type="hidden" name="sentido"
                               value="{{ $patron && in_array($patron->sentido, ['ida','vuelta']) ? $patron->sentido : 'vuelta' }}">
                        <input type="hidden" name="tipo" x-model="tipo">

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

                        <div class="pt-2">
                            <button type="button"
                                    @click="notaVisible = !notaVisible"
                                    class="text-xs text-gray-400 dark:text-gray-500
                                           hover:text-teal-600 dark:hover:text-teal-400 transition-colors">
                                <span x-show="!notaVisible">{{ __('movilidad.sighting_note_toggle_open') }}</span>
                                <span x-show="notaVisible" x-cloak>{{ __('movilidad.sighting_note_toggle_close') }}</span>
                            </button>
                            <div x-show="notaVisible" x-cloak class="mt-2">
                                <textarea name="notas" maxlength="280"
                                          placeholder="{{ __('movilidad.sighting_note_placeholder') }}"
                                          style="height:64px;resize:none;"
                                          class="w-full text-sm px-3 py-2 rounded-xl border border-gray-200 dark:border-gray-700
                                                 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
                                                 placeholder-gray-400 dark:placeholder-gray-500
                                                 focus:outline-none focus:ring-2 focus:ring-teal-400"></textarea>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit"
                                    :class="tipo ? 'opacity-100 cursor-pointer' : 'opacity-40 cursor-not-allowed pointer-events-none'"
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

            {{-- ── Guest: login prompt ──────────────────────────────────────── --}}
            @guest
            <div x-show="reportando" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-3 border-t border-red-100 dark:border-red-900/30 pt-4">
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

        @else
            {{-- No more boats today --}}
            <div class="space-y-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('ui.no_more_boats_today') }}
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $muelle->nombre }}</span>.
                </p>
                <a href="{{ route('horarios.index') }}"
                   class="inline-flex items-center gap-1 text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                    {{ __('ui.view_full_schedule') }} →
                </a>
            </div>
        @endif

    </div>

    {{-- ── Compact admin suspend row ─────────────────────────────────────────── --}}
    @auth
    @if(auth()->user()->isAdmin() && $servicioPrincipal)
    <div class="border-t border-gray-50 dark:border-gray-800 px-5 py-3 flex items-center justify-between gap-3">
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ $servicioPrincipal->nombre }}
            <span class="{{ $servicioPrincipal->suspendido ? 'text-red-500 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                · {{ $servicioPrincipal->suspendido ? __('ui.servicio_suspendido_label') : __('ui.servicio_activo_label') }}
            </span>
        </span>
        <form method="POST" action="{{ route('admin.movilidad.servicios.suspension', $servicioPrincipal) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="suspendido" value="{{ $servicioPrincipal->suspendido ? '0' : '1' }}">
            <button type="submit"
                    class="text-xs font-medium transition-colors
                           {{ $servicioPrincipal->suspendido
                               ? 'text-emerald-600 dark:text-emerald-400 hover:text-emerald-700'
                               : 'text-red-500 dark:text-red-400 hover:text-red-600' }}">
                {{ $servicioPrincipal->suspendido ? __('ui.reactivar_servicio') : __('ui.suspender_servicio') }}
            </button>
        </form>
    </div>
    @endif
    @endauth

</div>
