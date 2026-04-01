@props(['title' => 'Ayuda', 'icon' => 'info', 'color' => 'blue'])

@php
$colorClasses = [
    'blue' => 'bg-blue-50 border-blue-200 text-blue-900',
    'teal' => 'bg-teal-50 border-teal-200 text-teal-900',
    'green' => 'bg-green-50 border-green-200 text-green-900',
    'purple' => 'bg-purple-50 border-purple-200 text-purple-900',
];
$iconColorClasses = [
    'blue' => 'text-blue-500',
    'teal' => 'text-teal-500',
    'green' => 'text-green-500',
    'purple' => 'text-purple-500',
];
$icons = [
    'info' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'check' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
];
$classes = $colorClasses[$color] ?? $colorClasses['blue'];
$iconColor = $iconColorClasses[$color] ?? $iconColorClasses['blue'];
$iconPath = $icons[$icon] ?? $icons['info'];
@endphp

<div class="rounded-xl border px-5 py-4 {{ $classes }}">
    <div class="flex items-start gap-3">
        <svg class="w-6 h-6 shrink-0 {{ $iconColor }} mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
        </svg>
        <div class="flex-1">
            <h3 class="font-semibold text-sm mb-2">{{ $title }}</h3>
            <div class="text-sm space-y-2 opacity-90">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
