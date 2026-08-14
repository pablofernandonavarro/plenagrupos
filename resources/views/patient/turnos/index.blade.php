@extends('layouts.app')
@section('title', 'Mis turnos')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Mis turnos</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-700">Médico clínico</p>
                <p class="text-xs text-gray-400">Este mes</p>
            </div>
            @if($medicoState === 'completed')
                <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 font-medium">✓ Cumplido</span>
            @elseif($medicoState === 'scheduled')
                <span class="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">📅 Agendado</span>
            @elseif($medicoState === 'pending')
                <span class="text-xs px-2 py-1 rounded-full bg-amber-50 text-amber-700 font-medium">⏳ Por confirmar</span>
            @else
                <a href="{{ route('patient.turnos.create', ['specialty' => 'medico']) }}" class="text-xs px-3 py-1.5 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition">Sacar turno</a>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-700">Nutricionista</p>
                <p class="text-xs text-gray-400">Este mes</p>
            </div>
            @if($nutriState === 'completed')
                <span class="text-xs px-2 py-1 rounded-full bg-green-50 text-green-700 font-medium">✓ Cumplido</span>
            @elseif($nutriState === 'scheduled')
                <span class="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-medium">📅 Agendado</span>
            @elseif($nutriState === 'pending')
                <span class="text-xs px-2 py-1 rounded-full bg-amber-50 text-amber-700 font-medium">⏳ Por confirmar</span>
            @else
                <a href="{{ route('patient.turnos.create', ['specialty' => 'nutricionista']) }}" class="text-xs px-3 py-1.5 rounded-full bg-purple-600 hover:bg-purple-700 text-white font-medium transition">Sacar turno</a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between" style="background:#f8fafc">
            <p class="text-sm font-semibold text-gray-700">Próximos turnos</p>
            <a href="{{ route('patient.turnos.create') }}" class="text-sm text-teal-600 hover:underline">+ Sacar turno</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($upcoming as $a)
                <div class="px-5 py-3 flex justify-between items-center flex-wrap gap-2">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $a->professional?->name ?? '(profesional eliminado)' }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $a->starts_at->format('d/m/Y H:i') }}
                            · {{ $a->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-appointment-status-badge :status="$a->status" />
                        <form action="{{ route('patient.turnos.destroy', $a) }}" method="POST" onsubmit="return confirm('¿Cancelar este turno?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Cancelar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">No tenés turnos próximos.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100" style="background:#f8fafc">
            <p class="text-sm font-semibold text-gray-700">Historial</p>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($history as $a)
                <div class="px-5 py-3 flex justify-between items-center flex-wrap gap-2">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $a->professional?->name ?? '(profesional eliminado)' }}</p>
                        <p class="text-xs text-gray-400">
                            {{ $a->starts_at->format('d/m/Y H:i') }}
                            · {{ $a->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
                        </p>
                    </div>
                    <x-appointment-status-badge :status="$a->status" />
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin turnos anteriores.</p>
            @endforelse
        </div>
    </div>
    {{ $history->links() }}
</div>
@endsection
