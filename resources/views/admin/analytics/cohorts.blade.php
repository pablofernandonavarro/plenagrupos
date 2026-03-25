@extends('layouts.app')
@section('title', 'Indicadores — Cohortes')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Cohortes y retención</h1>
        <p class="text-gray-500 text-sm mt-1">
            Basado en la fecha de alta en cada grupo (<code class="text-xs bg-gray-100 px-1 rounded">group_patient.joined_at</code>) y asistencias en ese mismo grupo.
            Solo cuentan altas donde ya pasó el período (p. ej. 30 días desde el alta).
            <span class="text-gray-600">No se incluyen pacientes con estado <strong>Egreso</strong> en el usuario.</span>
        </p>
    </div>

    @include('admin.analytics._nav', ['active' => 'cohorts'])

    <form method="get" action="{{ route('admin.analytics.cohorts') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label for="group_id" class="block text-xs text-gray-500 mb-1">Filtrar por grupo</label>
            <select name="group_id" id="group_id" class="rounded-lg border border-gray-200 text-sm py-2 px-3 min-w-[200px]" onchange="this.form.submit()">
                <option value="">Todos ({{ $enrollmentN }} altas)</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ (int) $groupId === (int) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700">Primeros 30 días</h2>
            <p class="text-xs text-gray-400 mt-1">Altas con ≥30 días desde el alta: <strong>{{ $stats30['n'] }}</strong></p>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Visitas promedio</dt><dd class="font-semibold text-gray-800">{{ $stats30['avg'] !== null ? $stats30['avg'] : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Con ≥1 visita</dt><dd class="font-semibold text-teal-600">{{ $stats30['pct1'] !== null ? $stats30['pct1'].'%' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Con ≥2 visitas</dt><dd class="font-semibold text-gray-800">{{ $stats30['pct2'] !== null ? $stats30['pct2'].'%' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Con ≥4 visitas</dt><dd class="font-semibold text-gray-800">{{ $stats30['pct4'] !== null ? $stats30['pct4'].'%' : '—' }}</dd></div>
            </dl>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="text-sm font-semibold text-gray-700">Primeros 60 días</h2>
            <p class="text-xs text-gray-400 mt-1">Altas con ≥60 días desde el alta: <strong>{{ $stats60['n'] }}</strong></p>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Visitas promedio</dt><dd class="font-semibold text-gray-800">{{ $stats60['avg'] !== null ? $stats60['avg'] : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Con ≥1 visita</dt><dd class="font-semibold text-teal-600">{{ $stats60['pct1'] !== null ? $stats60['pct1'].'%' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Con ≥2 visitas</dt><dd class="font-semibold text-gray-800">{{ $stats60['pct2'] !== null ? $stats60['pct2'].'%' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Con ≥4 visitas</dt><dd class="font-semibold text-gray-800">{{ $stats60['pct4'] !== null ? $stats60['pct4'].'%' : '—' }}</dd></div>
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <h2 class="text-sm font-semibold text-gray-800 px-1 mb-1">Curva semanal (semanas 1–8 desde el alta)</h2>
        <p class="text-xs text-gray-400 px-1 mb-4">% de altas con al menos una visita en esa semana (solo se incluyen altas para las que ya terminó esa semana).</p>
        <div style="position:relative; height:240px;">
            <canvas id="cohortWeekChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const weekPct = @json($weekPct);
    new Chart(document.getElementById('cohortWeekChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: weekPct.map(w => w.label),
            datasets: [{
                label: '% con ≥1 visita',
                data: weekPct.map(w => w.pct ?? 0),
                backgroundColor: weekPct.map((w, i) => i === 7 ? '#0d9488' : 'rgba(13,148,136,0.35)'),
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        afterLabel: (ctx) => {
                            const w = weekPct[ctx.dataIndex];
                            return w ? ('n=' + w.eligible) : '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: v => v + '%' },
                    grid: { color: '#f3f4f6' },
                },
                x: { grid: { display: false } },
            }
        }
    });
})();
</script>
@endsection
