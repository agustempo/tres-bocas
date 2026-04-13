@php
    $classes = match($status) {
        'pending'   => 'bg-yellow-100 text-yellow-700',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-gray-100 text-gray-500',
        default     => 'bg-gray-100 text-gray-500',
    };
@endphp
<span class="text-xs font-medium px-2 py-0.5 rounded capitalize {{ $classes }}">
    {{ $status }}
</span>
