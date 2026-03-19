@extends('layouts.app')
@section('title', $patient->name)

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-start gap-3">
        <a href="{{ route('coordinator.patients.index') }}" class="mt-1 text-gray-400 hover:text-gray-600 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $patient->name }}</h1>
                @php
                    $piso = $patient->peso_piso; $techo = $patient->peso_techo;
                    if ($lastWeight && $techo && $lastWeight > $techo) $badge = ['↑ Sobre techo', 'bg-red-100 text-red-600'];
                    elseif ($lastWeight && $piso && $lastWeight < $piso) $badge = ['↓ Bajo piso', 'bg-blue-100 text-blue-600'];
                    elseif ($lastWeight && ($piso || $techo)) $badge = ['✓ En rango', 'bg-green-100 text-green-700'];
                    else $badge = null;
                @endphp
                @if($badge)
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full {{ $badge[1] }}">{{ $badge[0] }}</span>
                @endif
            </div>
            <p class="text-sm text-gray-400 mt-0.5 truncate">
                {{ $patient->email }}@if($patient->phone) · {{ $patient->phone }}@endif
            </p>
            @if($groups->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach($groups as $g)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $g->status === 'active' ? 'bg-green-100 text-green-700' : ($g->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ $g->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-teal-600">{{ $lastWeight ? $lastWeight . ' kg' : '—' }}</p>
            <p class="text-xs text-gray-400 mt-1">Último peso</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @if($totalChange !== null)
                <p class="text-2xl font-bold {{ $totalChange < 0 ? 'text-green-600' : ($totalChange > 0 ? 'text-red-500' : 'text-gray-400') }}">
                    {{ $totalChange > 0 ? '+' : '' }}{{ $totalChange }} kg
                </p>
            @else
                <p class="text-2xl font-bold text-gray-300">—</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">Variación total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $attendances->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">Sesiones</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @php
                $hrs = intdiv($totalMinutes, 60);
                $min = $totalMinutes % 60;
            @endphp
            <p class="text-2xl font-bold text-purple-600">
                {{ $hrs > 0 ? $hrs . 'h ' : '' }}{{ $min }}m
            </p>
            <p class="text-xs text-gray-400 mt-1">Tiempo en grupos</p>
        </div>
    </div>

    {{-- Weight range info --}}
    @if($patient->ideal_weight || $piso || $techo)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-4">
        <div class="flex flex-wrap gap-4 sm:gap-8 text-sm">
            @if($patient->ideal_weight)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Peso ideal</p>
                    <p class="font-semibold text-gray-700">{{ $patient->ideal_weight }} kg</p>
                </div>
            @endif
            @if($piso)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Piso</p>
                    <p class="font-semibold text-gray-700">{{ $piso }} kg</p>
                </div>
            @endif
            @if($techo)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Techo</p>
                    <p class="font-semibold text-gray-700">{{ $techo }} kg</p>
                </div>
            @endif
            @if($firstWeight)
                <div>
                    <p class="text-xs text-gray-400 mb-0.5">Peso inicial</p>
                    <p class="font-semibold text-gray-700">{{ $firstWeight }} kg</p>
                </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Weight chart --}}
    @if($weightRecords->count() > 1)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-800 mb-4">Evolución del peso</h2>
        <div class="relative h-48 sm:h-64">
            <canvas id="weightChart"></canvas>
        </div>
    </div>
    @endif

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Trazabilidad</h2>
            <p class="text-xs text-gray-400 mt-0.5">Asistencias y pesos registrados por sesión</p>
        </div>

        @forelse($timelineWithChange as $entry)
            @php
                $w = $entry['weight'];
                $ch = $entry['change'];
            @endphp
            <div class="flex gap-4 px-5 py-4 border-b border-gray-50 last:border-0">
                {{-- Timeline dot --}}
                <div class="flex flex-col items-center pt-1 shrink-0">
                    <div class="w-3 h-3 rounded-full {{ $w ? 'bg-teal-500' : 'bg-gray-200' }} ring-2 ring-white ring-offset-1"></div>
                    @if(!$loop->last)
                        <div class="w-px flex-1 bg-gray-100 mt-1"></div>
                    @endif
                </div>

                {{-- Content --}}
                <div class="flex-1 pb-1">
                    <div class="flex items-start justify-between gap-2 flex-wrap">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $entry['group_name'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $entry['date']->format('d/m/Y · H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($ch !== null)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                    {{ $ch < 0 ? 'bg-green-100 text-green-700' : ($ch > 0 ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-500') }}">
                                    {{ $ch > 0 ? '↑ +' : ($ch < 0 ? '↓ ' : '= ') }}{{ $ch }} kg
                                </span>
                            @endif
                            @if($w)
                                <span class="text-base font-bold text-teal-600">{{ $w }} kg</span>
                            @else
                                <span class="text-sm text-gray-300">Sin peso</span>
                            @endif
                        </div>
                    </div>

                    {{-- Range indicator bar --}}
                    @if($w && ($piso || $techo))
                        @php
                            $low  = $piso  ?? ($techo - 10);
                            $high = $techo ?? ($piso  + 10);
                            $range = $high - $low;
                            $pct = $range > 0 ? max(0, min(100, (($w - $low) / $range) * 100)) : 50;
                        @endphp
                        <div class="mt-2">
                            <div class="relative h-1.5 bg-gray-100 rounded-full">
                                @if($piso && $techo)
                                    <div class="absolute inset-0 bg-green-100 rounded-full"></div>
                                @endif
                                <div class="absolute top-1/2 -translate-y-1/2 w-2.5 h-2.5 rounded-full border-2 border-white shadow
                                    {{ $w > ($techo ?? PHP_INT_MAX) ? 'bg-red-500' : ($w < ($piso ?? 0) ? 'bg-blue-500' : 'bg-teal-500') }}"
                                    style="left: calc({{ $pct }}% - 5px)">
                                </div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-300 mt-0.5">
                                <span>{{ $piso ?? '' }}</span>
                                <span>{{ $techo ?? '' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="px-5 py-10 text-center text-sm text-gray-400">Sin sesiones registradas.</p>
        @endforelse
    </div>

</div>

@if($weightRecords->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels = @json($weightRecords->reverse()->map(fn($r) => $r->recorded_at->format('d/m'))->values());
const data   = @json($weightRecords->reverse()->pluck('weight')->values());
@if($patient->peso_piso)  const piso  = {{ $patient->peso_piso }};  @else const piso  = null; @endif
@if($patient->peso_techo) const techo = {{ $patient->peso_techo }}; @else const techo = null; @endif

const ctx = document.getElementById('weightChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Peso',
                data,
                borderColor: '#09cda6',
                backgroundColor: 'rgba(9,205,166,0.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#09cda6',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.35,
                fill: true,
            },
            ...(piso ? [{
                label: 'Piso',
                data: labels.map(() => piso),
                borderColor: 'rgba(59,130,246,0.4)',
                borderWidth: 1.5,
                borderDash: [5, 4],
                pointRadius: 0,
                fill: false,
            }] : []),
            ...(techo ? [{
                label: 'Techo',
                data: labels.map(() => techo),
                borderColor: 'rgba(239,68,68,0.4)',
                borderWidth: 1.5,
                borderDash: [5, 4],
                pointRadius: 0,
                fill: false,
            }] : []),
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: piso || techo },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + ' kg'
                }
            }
        },
        scales: {
            y: {
                ticks: { callback: v => v + ' kg', font: { size: 11 } },
                grid: { color: 'rgba(0,0,0,0.04)' },
            },
            x: {
                ticks: { font: { size: 11 } },
                grid: { display: false },
            }
        }
    }
});
</script>
@endif

@endsection
