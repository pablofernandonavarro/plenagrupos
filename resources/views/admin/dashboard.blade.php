@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">Panel de Administración</h1>
        <p class="text-gray-500 text-sm mt-1">Bienvenido, {{ auth()->user()->name }}</p>
    </div>

    {{-- Stats row 1 --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Grupos</p>
            <p class="text-3xl font-bold text-teal-600 mt-1">{{ $stats['groups'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Coordinadores</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['coordinators'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pacientes</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['patients'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Visitas hoy</p>
            <p class="text-3xl font-bold text-purple-600 mt-1">{{ $stats['visits_today'] }}</p>
        </div>
    </div>

    {{-- Stats row 2: clinical KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pérdida promedio</p>
            @if($stats['avg_loss'] !== null)
                <p class="text-3xl font-bold mt-1 {{ $stats['avg_loss'] > 0 ? 'text-green-600' : 'text-red-500' }}">
                    {{ $stats['avg_loss'] > 0 ? '-' : '+' }}{{ abs($stats['avg_loss']) }} kg
                </p>
                <p class="text-xs text-gray-400 mt-1">por paciente en el programa</p>
            @else
                <p class="text-3xl font-bold text-gray-300 mt-1">—</p>
            @endif
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">En rango mantenimiento</p>
            @if($stats['patients_range'] > 0)
                @php $rangePct = round($stats['in_range'] / $stats['patients_range'] * 100); @endphp
                <p class="text-3xl font-bold mt-1 {{ $rangePct >= 60 ? 'text-teal-600' : ($rangePct >= 30 ? 'text-yellow-600' : 'text-red-500') }}">
                    {{ $rangePct }}%
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ $stats['in_range'] }} de {{ $stats['patients_range'] }} pacientes</p>
            @else
                <p class="text-3xl font-bold text-gray-300 mt-1">—</p>
            @endif
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 col-span-2 lg:col-span-1">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Activos este mes</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['active_patients'] }}</p>
            <p class="text-xs text-gray-400 mt-1">pacientes con visita en 30 días</p>
        </div>
    </div>

    {{-- Weekly activity chart --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Actividad semanal</h2>
            <p class="text-xs text-gray-400 mt-0.5">Registros de peso por semana (últimas 8 semanas)</p>
        </div>
        <div class="px-4 py-4" style="position:relative; height:180px;">
            <canvas id="weeklyChart"></canvas>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.adherence.index') }}" class="bg-white border-2 border-amber-400 text-amber-900 hover:bg-amber-50 rounded-xl p-5 flex items-center gap-3 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span class="font-semibold">Adherencia</span>
        </a>
        <a href="{{ route('admin.analytics.index') }}" class="bg-white border-2 border-teal-500 text-teal-700 hover:bg-teal-50 rounded-xl p-5 flex items-center gap-3 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span class="font-semibold">Indicadores</span>
        </a>
        <a href="{{ route('admin.groups.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white rounded-xl p-5 flex items-center gap-3 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="font-semibold">Nuevo Grupo</span>
        </a>
        <a href="{{ route('admin.users.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white rounded-xl p-5 flex items-center gap-3 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span class="font-semibold">Nuevo Usuario</span>
        </a>
    </div>

    {{-- Groups overview --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Grupos vigentes</h2>
            <a href="{{ route('admin.groups.index') }}" class="text-sm text-teal-600 hover:underline">Ver todos</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($groups as $group)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $group->name }}</p>
                        <p class="text-xs text-gray-400">{{ $group->patients->count() }} pacientes</p>
                    </div>
                    <a href="{{ route('admin.groups.show', $group) }}" class="text-xs text-teal-600 hover:underline">Ver QR y datos</a>
                </div>
            @empty
                <p class="px-5 py-6 text-center text-gray-400 text-sm">No hay grupos aún.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent attendances --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Últimas visitas</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentAttendances as $att)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $att->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $att->group->name }} · {{ $att->attended_at->format('d/m/Y H:i') }}@if($att->groupSession) · Sesión n.º {{ $att->groupSession->sequence_number }}@endif</p>
                    </div>
                    @if($att->weightRecord)
                        <span class="text-sm font-semibold text-teal-600">{{ $att->weightRecord->weight }} kg</span>
                    @else
                        <span class="text-xs text-gray-300">Sin peso</span>
                    @endif
                </div>
            @empty
                <p class="px-5 py-6 text-center text-gray-400 text-sm">Sin visitas aún.</p>
            @endforelse
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const labels = @json($weeklyData->pluck('label'));
    const counts = @json($weeklyData->pluck('count'));
    new Chart(document.getElementById('weeklyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                data: counts,
                backgroundColor: counts.map((v, i) =>
                    i === counts.length - 1 ? '#0d9488' : 'rgba(13,148,136,0.25)'
                ),
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.y + ' registros' }} },
            scales: {
                x: { ticks:{font:{size:11},color:'#9ca3af'}, grid:{display:false} },
                y: { ticks:{font:{size:11},color:'#9ca3af',stepSize:1}, grid:{color:'#f3f4f6'}, beginAtZero:true }
            }
        }
    });
})();
</script>
@endsection
