@extends('layouts.app')
@section('title', 'Indicadores — InBody')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">InBody — evolución agregada</h1>
        <p class="text-gray-500 text-sm mt-1">Promedios mensuales de los últimos meses con datos. La tabla inferior compara primer vs último estudio por paciente (mínimo 2 estudios).</p>
    </div>

    @include('admin.analytics._nav', ['active' => 'inbody'])

    @if($monthly->isEmpty())
        <div class="bg-white rounded-xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
            Aún no hay registros InBody en el período analizado.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4" style="position:relative; height:260px;">
                <p class="text-xs text-gray-500 mb-2">Promedio mensual — grasa visceral y % grasa</p>
                <canvas id="inbodyChartFat"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4" style="position:relative; height:260px;">
                <p class="text-xs text-gray-500 mb-2">Promedio mensual — masa muscular esquelética (kg)</p>
                <canvas id="inbodyChartMuscle"></canvas>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Primer vs último estudio (por paciente)</h2>
            <p class="text-xs text-gray-400 mt-0.5">Δ = último − primero (negativo en grasa = mejora)</p>
        </div>
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3 font-medium">Paciente</th>
                    <th class="px-4 py-3 font-medium">Estudios</th>
                    <th class="px-4 py-3 font-medium text-right">Δ Grasa visceral</th>
                    <th class="px-4 py-3 font-medium text-right">Δ % grasa</th>
                    <th class="px-4 py-3 font-medium text-right">Δ Masa muscular</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($patientRows as $pr)
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-4 py-3">
                            <span class="font-medium text-gray-800">{{ $pr['user']->name }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $pr['first']->test_date->format('d/m/Y') }} → {{ $pr['last']->test_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right font-mono text-xs">
                            @include('admin.analytics._delta', ['v' => $pr['dVisceral'], 'invert' => true])
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs">
                            @include('admin.analytics._delta', ['v' => $pr['dFat'], 'invert' => true])
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs">
                            @include('admin.analytics._delta', ['v' => $pr['dMuscle'], 'invert' => false])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay pacientes con al menos dos estudios InBody.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!$monthly->isEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const raw = @json($chartData);
    const labels = raw.map(r => r.key);
    const common = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: {
            x: { ticks: { font: { size: 10 }, maxRotation: 45 } },
        }
    };
    new Chart(document.getElementById('inbodyChartFat').getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Grasa visceral',
                    data: raw.map(r => r.visceral),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245,158,11,0.08)',
                    tension: 0.25,
                    yAxisID: 'y',
                },
                {
                    label: '% Grasa corporal',
                    data: raw.map(r => r.fat_pct),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139,92,246,0.08)',
                    tension: 0.25,
                    yAxisID: 'y1',
                },
            ]
        },
        options: {
            ...common,
            scales: {
                ...common.scales,
                y: { type: 'linear', position: 'left', grid: { color: '#f3f4f6' } },
                y1: { type: 'linear', position: 'right', grid: { display: false } },
            }
        }
    });
    new Chart(document.getElementById('inbodyChartMuscle').getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Masa muscular esquelética (kg)',
                data: raw.map(r => r.muscle),
                borderColor: '#09cda6',
                backgroundColor: 'rgba(9,205,166,0.12)',
                tension: 0.25,
                fill: true,
            }]
        },
        options: {
            ...common,
            scales: {
                ...common.scales,
                y: { beginAtZero: false, grid: { color: '#f3f4f6' } },
            }
        }
    });
})();
</script>
@endif
@endsection
