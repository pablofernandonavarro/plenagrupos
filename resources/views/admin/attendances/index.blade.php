@extends('layouts.app')
@section('title', 'Control de asistencias')

@section('content')
<div class="space-y-5">

    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Control de asistencias</h1>
        <span class="text-sm text-gray-400">{{ $attendances->total() }} registro(s)</span>
    </div>

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

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
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
                            @if($gt === 'mantenimiento')
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Mant.</span>
                            @elseif($gt === 'mantenimiento_pleno')
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-purple-50 text-purple-700 font-medium">M. Pleno</span>
                            @else
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-sky-50 text-sky-700 font-medium">Desc.</span>
                            @endif
                            <span class="text-gray-700">{{ $att->group?->name ?? '—' }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-500">
                        {{ $att->attended_at->format('d/m/Y H:i') }}
                    </td>
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
@endsection
