@props([
    'fuente'    => 'estimado',   // 'oficial' | 'comunidad' | 'estimado'
    'votosOk'   => 0,
    'votosMal'  => 0,
    'size'      => 'sm',         // 'sm' | 'xs'
])

@php
    $total = $votosOk + $votosMal;

    // Dot color based on fuente + community signal
    if ($total > 0) {
        $ratio = $votosOk / $total;
        if ($ratio >= 0.75) {
            $color = 'bg-green-400';
            $tip   = __('movilidad.confidence_confirmado');
        } elseif ($ratio >= 0.4) {
            $color = 'bg-amber-400';
            $tip   = __('movilidad.confidence_dudoso');
        } else {
            $color = 'bg-red-400';
            $tip   = __('movilidad.confidence_contradictorio');
        }
    } else {
        $color = match($fuente) {
            'oficial'   => 'bg-blue-400',
            'comunidad' => 'bg-amber-400',
            default     => 'bg-gray-300 dark:bg-gray-600',
        };
        $tip = match($fuente) {
            'oficial'   => __('movilidad.confidence_oficial'),
            'comunidad' => __('movilidad.confidence_comunidad'),
            default     => __('movilidad.confidence_estimado'),
        };
    }

    $sizeClass = $size === 'xs' ? 'w-1.5 h-1.5' : 'w-2 h-2';
@endphp

<span
    class="{{ $sizeClass }} rounded-full shrink-0 {{ $color }}"
    title="{{ $tip }}"
></span>
