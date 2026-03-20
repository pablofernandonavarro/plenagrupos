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
            <p class="text-xs text-gray-400 mt-1">Peso actual</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @if($totalChange !== null)
                <p class="text-2xl font-bold {{ $totalChange < 0 ? 'text-green-600' : ($totalChange > 0 ? 'text-red-500' : 'text-gray-400') }}">
                    {{ $totalChange > 0 ? '+' : '' }}{{ $totalChange }} kg
                </p>
            @else
                <p class="text-2xl font-bold text-gray-300">—</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">Pérdida total</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @if($attendanceRate !== null)
                <p class="text-2xl font-bold {{ $attendanceRate >= 75 ? 'text-blue-600' : ($attendanceRate >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                    {{ $attendanceRate }}%
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ $attendedSessions }}/{{ $totalSessions }} sesiones</p>
            @else
                <p class="text-2xl font-bold text-gray-300">—</p>
                <p class="text-xs text-gray-400 mt-1">Asistencia</p>
            @endif
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 text-center">
            @php $hrs = intdiv($totalMinutes, 60); $min = $totalMinutes % 60; @endphp
            <p class="text-2xl font-bold text-purple-600">{{ $hrs > 0 ? $hrs.'h ' : '' }}{{ $min }}m</p>
            <p class="text-xs text-gray-400 mt-1">En grupos</p>
        </div>
    </div>

    {{-- Trend + range banner --}}
    @if($weightRecords->count() >= 2)
    @php
        $absT = abs($trend);
        if ($trend < -0.5)      { $tc='text-blue-600';  $ti='↓↓'; $tt='Pérdida acelerada ('  .round($absT,2).' kg/ses.)'; $ts='bg-blue-50 border-blue-200'; }
        elseif ($trend < -0.05) { $tc='text-green-600'; $ti='↓';  $tt='Bajando — ritmo saludable ('.round($absT,2).' kg/ses.)'; $ts='bg-green-50 border-green-200'; }
        elseif ($trend < 0.05)  { $tc='text-gray-500';  $ti='→';  $tt='Peso estable';              $ts='bg-gray-50 border-gray-200'; }
        elseif ($trend < 0.3)   { $tc='text-yellow-600';$ti='↑';  $tt='Leve aumento — monitorear'; $ts='bg-yellow-50 border-yellow-200'; }
        else                    { $tc='text-red-500';   $ti='↑↑'; $tt='Aumento sostenido — requiere seguimiento'; $ts='bg-red-50 border-red-200'; }
    @endphp
    <div class="rounded-xl border px-4 py-3 flex items-center gap-3 {{ $ts }}">
        <span class="text-2xl font-bold {{ $tc }} shrink-0">{{ $ti }}</span>
        <div class="flex-1">
            <p class="text-sm font-semibold text-gray-800">Tendencia de peso</p>
            <p class="text-xs {{ $tc }}">{{ $tt }}</p>
        </div>
        @if($inRange !== null)
            @php $piso = $patient->peso_piso; $techo = $patient->peso_techo; @endphp
            <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full
                {{ $inRange ? 'bg-green-100 text-green-700' : ($lastWeight > $techo ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700') }}">
                {{ $inRange ? '✓ En rango' : ($lastWeight > $techo ? '↑ Sobre techo' : '↓ Bajo piso') }}
            </span>
        @endif
    </div>
    @endif

    {{-- Progress toward ideal --}}
    @if($progressPct !== null)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
        <div class="flex justify-between items-center mb-2">
            <p class="text-sm font-semibold text-gray-700">Progreso hacia peso ideal</p>
            <span class="text-sm font-bold text-teal-600">{{ $progressPct }}%</span>
        </div>
        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-teal-500 rounded-full" style="width:{{ $progressPct }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-400 mt-1.5">
            <span>Inicial: {{ $firstWeight }} kg</span>
            <span>Ideal: {{ $patient->ideal_weight }} kg</span>
        </div>
    </div>
    @endif

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
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Evolución del peso</h2>
        </div>
        <div class="px-4 py-4" style="position:relative; height:240px;">
            <canvas id="weightChart"></canvas>
        </div>
    </div>
    @endif

    {{-- AI Clinical Analysis --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-gray-800">Análisis clínico IA</h2>
                <p class="text-xs text-gray-400 mt-0.5">Generado por LLaMA 3.3 · Se actualiza una vez por día</p>
            </div>
            <button id="btn-ai"
                onclick="loadAiAnalysis(true)"
                class="flex items-center gap-1.5 text-xs bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-lg transition font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Generar
            </button>
        </div>
        <div class="px-5 py-4">
            <p id="ai-text" class="text-sm text-gray-400 italic">
                Presioná "Generar" para obtener un análisis clínico basado en los datos del paciente.
            </p>
        </div>
    </div>

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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const cd = @json($chartData);
    const datasets = [{
        label: 'Peso',
        data: cd.weights,
        borderColor: '#0d9488',
        backgroundColor: 'rgba(13,148,136,0.08)',
        borderWidth: 2.5,
        pointRadius: cd.weights.length > 20 ? 2 : 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.3,
    }];
    if (cd.piso)  datasets.push({ label: 'Piso ('  + cd.piso  + ' kg)', data: cd.labels.map(() => cd.piso),
        borderColor: '#16a34a', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, fill: false, tension: 0 });
    if (cd.techo) datasets.push({ label: 'Techo (' + cd.techo + ' kg)', data: cd.labels.map(() => cd.techo),
        borderColor: '#ef4444', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, fill: false, tension: 0 });

    new Chart(document.getElementById('weightChart').getContext('2d'), {
        type: 'line',
        data: { labels: cd.labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: !!(cd.piso || cd.techo), position: 'bottom',
                    labels: { font:{size:11}, boxWidth:20, padding:10 } },
                tooltip: { callbacks: {
                    label: ctx => ctx.datasetIndex === 0
                        ? ' ' + ctx.parsed.y.toFixed(2) + ' kg'
                        : ctx.dataset.label
                }}
            },
            scales: {
                x: { ticks:{font:{size:11},color:'#9ca3af',maxRotation:45,autoSkip:true,maxTicksLimit:8}, grid:{display:false} },
                y: { ticks:{font:{size:11},color:'#9ca3af',callback:v=>v+' kg'}, grid:{color:'#f3f4f6'}, grace:'8%' }
            }
        }
    });
})();
</script>
@endif

<script>
async function loadAiAnalysis(force = false) {
    const btn  = document.getElementById('btn-ai');
    const text = document.getElementById('ai-text');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg> Analizando...';
    text.textContent = 'Generando análisis clínico...';
    text.className = 'text-sm text-gray-400 italic';
    try {
        const url = '{{ route("coordinator.patients.ai-analysis", $patient) }}' + (force ? '?force=1' : '');
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await res.json();
        text.textContent = data.analysis;
        text.className = 'text-sm text-gray-700 leading-relaxed';
    } catch {
        text.textContent = 'Error al conectar con el servicio de IA. Intentá nuevamente.';
        text.className = 'text-sm text-red-400 italic';
    }
    btn.disabled = false;
    btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Regenerar';
}
</script>

@endsection
