@props(['user', 'size' => 'sm'])

@php
    $sizes = [
        'xs' => 'w-6 h-6 text-[10px]',
        'sm' => 'w-8 h-8 text-xs',
        'md' => 'w-10 h-10 text-sm',
        'lg' => 'w-14 h-14 text-base',
    ];
    $cls = $sizes[$size] ?? $sizes['sm'];

    $colors = [
        'style="background-color:#09cda6"',
        'style="background-color:#3b82f6"',
        'style="background-color:#8b5cf6"',
        'style="background-color:#6366f1"',
        'style="background-color:#f43f5e"',
        'style="background-color:#f59e0b"',
        'style="background-color:#06b6d4"',
        'style="background-color:#10b981"',
    ];
    $colorStyle = $colors[$user->id % count($colors)];

    $parts    = explode(' ', trim($user->name));
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
@endphp

@if($user->avatar)
    <img src="{{ secure_asset('storage/' . $user->avatar) }}"
         alt="{{ $user->name }}"
         class="{{ $cls }} rounded-full object-cover shrink-0"
         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div class="{{ $cls }} rounded-full items-center justify-center shrink-0 font-semibold text-white" {!! $colorStyle !!} style="display:none">
        {{ $initials }}
    </div>
@else
    <div class="{{ $cls }} rounded-full flex items-center justify-center shrink-0 font-semibold text-white" {!! $colorStyle !!}>
        {{ $initials }}
    </div>
@endif
