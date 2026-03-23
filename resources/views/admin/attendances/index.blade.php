@extends('layouts.app')
@section('title', 'Control de asistencias')

@section('content')
@php
$typeLabels = [
    'descenso'            => 'Descenso',
    'mantenimiento'       => 'Mantenimiento',
    'mantenimiento_pleno' => 'Mant. Pleno',
];
$typeBadge = [
    'descenso'            => 'bg-sky-50 text-sky-700',
    'mantenimiento'       => 'bg-amber-50 text-amber-700',
    'mantenimiento_pleno' => 'bg-purple-50 text-purple-700',
];
@endphp
<div class="space-y-6">

    <h1 class="text-2xl font-bold text-gray-800">Control de asistencias</h1>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.attendances.index') }}"
          class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Paciente</label>
                <select name="patient_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">Todos</option>
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}" {{ request('patient_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Grupo</label>
                <select name="group_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">Todos</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit"
                class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition">
                Filtrar
            </button>
            @if(request()->hasAny(['patient_id','group_id','date_from','date_to']))
                <a href="{{ route('admin.attendances.index') }}"
                   class="px-4 py-2 border border-gray-200 text-gray-500 text-sm rounded-lg hover:bg-gray-50 transition">
                    Limpiar
                </a>
            @endif
        </div>
    </form>

    {{-- Monthly summary table --}}
    @if($summary->count())
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2" style="background:#f8fafc">
            <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <h2 class="font-semibold text-gray-700 text-sm">
                Resumen mensual — {{ now()->startOfMonth()->format('d/m') }} al {{ now()->endOfMonth()->format('d/m') }}
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left" style="background:#f8fafc">
                        <th class="px-5 py-3 font-semibold text-gray-600">Paciente</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Plan</th>
                        @foreach($groupTypes as $gt)
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $typeBadge[$gt] }}">{{ $typeLabels[$gt] }}</span>
                        </th>
                        @endforeach
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($summary as $idx => $row)
                    @php $patient = $row['patient']; @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $patient->name }}</td>
                        <td class="px-5 py-3">
                            @if($patient->plan === 'mantenimiento')
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Mantenimiento</span>
                            @elseif($patient->plan === 'mantenimiento_pleno')
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-purple-50 text-purple-700 font-medium">Mant. Pleno</span>
                            @else
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-sky-50 text-sky-700 font-medium">Descenso</span>
                            @endif
                        </td>
                        @foreach($groupTypes as $gt)
                        @php $stat = $row['types'][$gt]; @endphp
                        <td class="px-4 py-3 text-center">
                            @if($stat['over'])
                                <span class="inline-flex items-center gap-1 text-red-600 font-semibold">
                                    {{ $stat['used'] }}/{{ $stat['limit'] }}
                                    <span class="text-xs text-red-400">(+{{ $stat['used'] - $stat['limit'] }})</span>
                                </span>
                            @elseif($stat['limit'] === null)
                                <span class="text-gray-500">{{ $stat['used'] }} / ∞</span>
                            @else
                                <span class="{{ $stat['remaining'] === 0 ? 'text-orange-500 font-semibold' : 'text-gray-700' }}">
                                    {{ $stat['used'] }}/{{ $stat['limit'] }}
                                </span>
                                <span class="block text-xs {{ $stat['remaining'] === 0 ? 'text-orange-400' : 'text-gray-400' }}">
                                    {{ $stat['remaining'] }} restante(s) este mes
                                </span>
                            @endif
                        </td>
                        @endforeach
                        <td class="px-5 py-3 text-right">
                            <button onclick="openModal({{ $idx }})"
                                class="text-xs px-3 py-1.5 rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 font-medium transition">
                                Detalle
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Attendance log --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2" style="background:#f8fafc">
            <h2 class="font-semibold text-gray-700 text-sm">Historial de asistencias</h2>
            <span class="text-xs text-gray-400">({{ $attendances->total() }} registro(s))</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left" style="background:#f8fafc">
                    <th class="px-5 py-3 font-semibold text-gray-600">Paciente</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Grupo</th>
                    <th class="px-5 py-3 font-semibold text-gray-600">Fecha y hora</th>
                    <th class="px-5 py-3 font-semibold text-gray-600 text-center">Peso</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($attendances as $att)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $att->user?->name ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            @php $gt = $att->group?->group_type; @endphp
                            <span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $typeBadge[$gt] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ $typeLabels[$gt] ?? $gt }}
                            </span>
                            <span class="text-gray-700">{{ $att->group?->name ?? '—' }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $att->attended_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($att->weightRecord)
                            <span class="font-semibold text-teal-600">{{ $att->weightRecord->weight }} kg</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <form action="{{ route('admin.attendances.destroy', $att) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar esta asistencia?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-400 hover:text-red-600 transition">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-gray-400">Sin asistencias registradas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($attendances->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
            <span>Página {{ $attendances->currentPage() }} de {{ $attendances->lastPage() }}</span>
            <div class="flex gap-2">
                @if($attendances->onFirstPage())
                    <span class="px-3 py-1 rounded border border-gray-200 text-gray-300">← Anterior</span>
                @else
                    <a href="{{ $attendances->previousPageUrl() }}" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 transition">← Anterior</a>
                @endif
                @if($attendances->hasMorePages())
                    <a href="{{ $attendances->nextPageUrl() }}" class="px-3 py-1 rounded border border-gray-200 hover:bg-gray-50 transition">Siguiente →</a>
                @else
                    <span class="px-3 py-1 rounded border border-gray-200 text-gray-300">Siguiente →</span>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>

{{-- Detail modals --}}
@foreach($summary as $idx => $row)
@php $patient = $row['patient']; $atts = $row['attendances']; @endphp
<div id="modal-{{ $idx }}"
     class="fixed inset-0 z-50 hidden flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.4)"
     onclick="if(event.target===this) closeModal({{ $idx }})">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div>
                <p class="font-bold text-gray-800">{{ $patient->name }}</p>
                <p class="text-xs text-gray-400">Historial de asistencias</p>
            </div>
            <button onclick="closeModal({{ $idx }})"
                class="text-gray-400 hover:text-gray-600 transition text-xl leading-none">&times;</button>
        </div>

        <div class="px-5 py-4 max-h-80 overflow-y-auto">
            @if($atts->isEmpty())
                <p class="text-sm text-gray-400 text-center py-6">Sin asistencias esta semana.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b border-gray-100">
                            <th class="pb-2 font-semibold text-gray-500">Fecha y hora</th>
                            <th class="pb-2 font-semibold text-gray-500">Grupo</th>
                            <th class="pb-2 font-semibold text-gray-500">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($atts as $a)
                        <tr>
                            <td class="py-2 text-gray-700">{{ $a->attended_at->format('d/m/Y H:i') }}</td>
                            <td class="py-2 text-gray-600">{{ $a->group?->name ?? '—' }}</td>
                            <td class="py-2">
                                @php $gt = $a->group?->group_type; @endphp
                                <span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $typeBadge[$gt] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $typeLabels[$gt] ?? '—' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="px-5 py-3 border-t border-gray-100 text-right">
            <button onclick="closeModal({{ $idx }})"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
                Cerrar
            </button>
        </div>
    </div>
</div>
@endforeach

<script>
function openModal(idx)  { document.getElementById('modal-' + idx).classList.remove('hidden'); }
function closeModal(idx) { document.getElementById('modal-' + idx).classList.add('hidden'); }
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.querySelectorAll('[id^="modal-"]').forEach(m => m.classList.add('hidden'));
});
</script>
@endsection
