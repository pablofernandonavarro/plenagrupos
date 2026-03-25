@php
    $invert = $invert ?? false;
@endphp
@if($v === null)
    <span class="text-gray-300">—</span>
@else
    @php
        $good = $invert ? ($v < 0) : ($v > 0);
        $cls = $good ? 'text-green-600' : ($v == 0 ? 'text-gray-500' : 'text-red-500');
    @endphp
    <span class="{{ $cls }}">{{ $v > 0 ? '+' : '' }}{{ number_format($v, 2, ',', '.') }}</span>
@endif
