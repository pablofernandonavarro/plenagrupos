@extends('layouts.app')
@section('title', 'Indicadores — Por grupo')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Comparativa por grupo</h1>
        <p class="text-gray-500 text-sm mt-1">
            Promedio de asistencias por semana en las últimas {{ $weeksWindow }} semanas (desde {{ now()->subWeeks($weeksWindow)->startOfWeek()->format('d/m/Y') }}).
            Pérdida media solo con ≥2 pesajes en ese grupo. % en rango con piso/techo definidos.
        </p>
    </div>

    @include('admin.analytics._nav', ['active' => 'groups'])

    <div class="flex flex-wrap gap-2 text-sm">
        <span class="text-gray-500">Ordenar:</span>
        <a href="{{ route('admin.analytics.groups', ['sort' => 'nombre']) }}" class="{{ $sort === 'nombre' ? 'font-semibold text-teal-600' : 'text-gray-600 hover:underline' }}">Nombre</a>
        <span class="text-gray-300">|</span>
        <a href="{{ route('admin.analytics.groups', ['sort' => 'asistencias']) }}" class="{{ $sort === 'asistencias' ? 'font-semibold text-teal-600' : 'text-gray-600 hover:underline' }}">Asistencias/sem</a>
        <span class="text-gray-300">|</span>
        <a href="{{ route('admin.analytics.groups', ['sort' => 'rango']) }}" class="{{ $sort === 'rango' ? 'font-semibold text-teal-600' : 'text-gray-600 hover:underline' }}">% en rango</a>
        <span class="text-gray-300">|</span>
        <a href="{{ route('admin.analytics.groups', ['sort' => 'perdida']) }}" class="{{ $sort === 'perdida' ? 'font-semibold text-teal-600' : 'text-gray-600 hover:underline' }}">Pérdida media</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3 font-medium">Grupo</th>
                    <th class="px-4 py-3 font-medium text-right">Pacientes</th>
                    <th class="px-4 py-3 font-medium text-right">Asist. / sem <span class="normal-case text-gray-400">(prom.)</span></th>
                    <th class="px-4 py-3 font-medium text-right">% en rango</th>
                    <th class="px-4 py-3 font-medium text-right">Pérdida media</th>
                    <th class="px-4 py-3 font-medium text-right">N</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.groups.show', $row['group']) }}" class="font-medium text-gray-800 hover:text-teal-600">{{ $row['group']->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $row['patientCount'] }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-800">{{ number_format($row['avgWeekly'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($row['rangePct'] !== null)
                                <span class="font-semibold {{ $row['rangePct'] >= 60 ? 'text-teal-600' : ($row['rangePct'] >= 30 ? 'text-yellow-600' : 'text-red-500') }}">{{ $row['rangePct'] }}%</span>
                                <span class="text-gray-400 text-xs">({{ $row['inRange'] }}/{{ $row['withRange'] }})</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($row['avgLoss'] !== null)
                                <span class="font-semibold {{ $row['avgLoss'] > 0 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ $row['avgLoss'] > 0 ? '−' : '+' }}{{ number_format(abs($row['avgLoss']), 2, ',', '.') }} kg
                                </span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-500" title="Pacientes con ≥2 pesajes en el grupo">{{ $row['lossN'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay grupos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
