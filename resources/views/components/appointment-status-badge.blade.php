@php
$labels = [
    'pending'   => 'Pendiente',
    'confirmed' => 'Confirmado',
    'completed' => 'Asistió',
    'cancelled' => 'Cancelado',
    'no_show'   => 'No asistió',
];
$colors = [
    'pending'   => 'bg-amber-50 text-amber-700',
    'confirmed' => 'bg-teal-50 text-teal-700',
    'completed' => 'bg-green-50 text-green-700',
    'cancelled' => 'bg-gray-100 text-gray-500',
    'no_show'   => 'bg-red-50 text-red-600',
];
@endphp
<span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $colors[$status] ?? 'bg-gray-100 text-gray-500' }}">
    {{ $labels[$status] ?? $status }}
</span>
