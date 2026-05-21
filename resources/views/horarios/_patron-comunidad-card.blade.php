{{--
  Partial: _patron-comunidad-card
  Expected variables:
    $patron (PatronComunidad), $miReaccion (string: 'positivo'|'negativo'|'')
--}}
@php
    $hora = \Carbon\Carbon::today()->setTimeFromTimeString($patron->hora_referencia);
    $min  = (int) now()->diffInMinutes($hora, false);

    if ($min <= 0) {
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

    $verificado     = $patron->verificado;
    $confirmaciones = $patron->confirmaciones;
    $nombreDisplay  = $patron->nombreDisplay();
    $autorNombre    = $patron->user?->name ?? '';
@endphp

<div x-data="{
        voted:          @js($miReaccion),
        posCount:       {{ $confirmaciones }},
        negCount:       0,
        verificado:     @js($verificado),
        confirmaciones: {{ $confirmaciones }},
        async vote(voto) {
            if (this.voted) return;
            this.voted = voto;
            if (voto === 'positivo') {
                this.posCount++;
                this.confirmaciones++;
                if (this.confirmaciones >= 5) this.verificado = true;
            } else {
                this.negCount++;
            }
            const res = await fetch('{{ route('horarios.comunidad.reaccionar', $patron) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({ tipo: voto })
            });
            const data = await res.json();
            if (data.verificado    !== undefined) this.verificado     = data.verificado;
            if (data.confirmaciones !== undefined) this.confirmaciones = data.confirmaciones;
        }
     }"
     :class="verificado
         ? 'border border-teal-200 dark:border-teal-800 bg-white dark:bg-gray-800/50'
         : 'border border-dashed border-blue-200 dark:border-blue-900/60 bg-white dark:bg-gray-800/30'"
     class="rounded-2xl overflow-hidden">

    {{-- Main info row --}}
    <div class="px-4 py-3.5">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-baseline gap-2 shrink-0">
                <span class="font-black font-mono tabular-nums text-gray-900 dark:text-gray-50 leading-none"
                      style="font-size:1.75rem;line-height:1">
                    {{ $hora->format('H:i') }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ __('movilidad.tolerancia_label', ['min' => $patron->ventana_min]) }}
                </span>
            </div>

            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                    {{ $nombreDisplay }}
                </p>
                @if($patron->empresa)
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    → {{ $patron->destino }}
                </p>
                @endif
            </div>

            <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full"
                  style="background:{{ $countdownColor }}1a;color:{{ $countdownColor }}">
                {{ $countdownText }}
            </span>
        </div>
    </div>

    {{-- Trust indicator --}}
    <div class="px-4 pb-3">

        {{-- Pending state --}}
        <template x-if="!verificado">
            <div>
                <div class="flex items-center justify-between gap-3 mb-1.5">
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full
                                 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400
                                 border border-blue-200 dark:border-blue-800">
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path stroke-linecap="round" d="M12 6v6l4 2"/>
                        </svg>
                        {{ __('schedule.trust_pending') }}
                    </span>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 truncate">
                        {{ $autorNombre }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-[3px] rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                        <div class="h-full bg-blue-400 dark:bg-blue-500 rounded-full transition-all duration-300"
                             :style="`width: ${Math.min(100, (confirmaciones / 5) * 100)}%`">
                        </div>
                    </div>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 whitespace-nowrap"
                          x-text="`${confirmaciones}{{ str_replace(':count', '', __('schedule.trust_progress')) }}`">
                    </span>
                </div>
            </div>
        </template>

        {{-- Verified state --}}
        <template x-if="verificado">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex items-center gap-1 text-[11px] font-medium px-2 py-0.5 rounded-full
                             bg-teal-50 dark:bg-teal-900/20 text-teal-700 dark:text-teal-300
                             border border-teal-200 dark:border-teal-800">
                    <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ __('schedule.trust_verified') }}
                </span>
                <span class="text-[11px] text-gray-400 dark:text-gray-500 truncate">
                    {{ $autorNombre }}
                </span>
            </div>
        </template>
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

</div>
