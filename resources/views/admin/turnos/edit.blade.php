@extends('layouts.app')
@section('title', 'Turno')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.turnos.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Turno</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div class="space-y-1">
            <p class="text-sm text-gray-500">Paciente</p>
            <p class="font-medium text-gray-800">{{ $appointment->patient?->name ?? '(eliminado)' }}</p>
        </div>
        <div class="space-y-1">
            <p class="text-sm text-gray-500">Profesional</p>
            <p class="font-medium text-gray-800">{{ $appointment->professional?->name ?? '(eliminado)' }}</p>
            <p class="text-xs text-gray-400">{{ $appointment->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}</p>
        </div>
        <div class="space-y-1">
            <p class="text-sm text-gray-500">Horario</p>
            <p class="font-medium text-gray-800">{{ $appointment->starts_at->format('d/m/Y H:i') }} — {{ $appointment->ends_at->format('H:i') }}</p>
        </div>
        <div class="space-y-1">
            <p class="text-sm text-gray-500">Reservado por</p>
            <p class="text-sm text-gray-800">{{ $appointment->booked_by === 'admin' ? 'Administrador' : 'Paciente' }}</p>
        </div>

        <form method="POST" action="{{ route('admin.turnos.update', $appointment) }}" class="space-y-4 pt-2 border-t border-gray-100">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white">
                    @foreach(['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'completed' => 'Completado', 'no_show' => 'No asistió', 'cancelled' => 'Cancelado'] as $val => $label)
                        <option value="{{ $val }}" {{ $appointment->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <textarea name="notes" rows="3" maxlength="1000" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none">{{ old('notes', $appointment->notes) }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Guardar
                </button>
                <a href="{{ route('admin.turnos.index') }}" class="text-gray-600 hover:text-gray-800 px-4 py-2.5 text-sm">Volver</a>
            </div>
        </form>

        @if($appointment->status !== 'cancelled')
        <form method="POST" action="{{ route('admin.turnos.destroy', $appointment) }}" onsubmit="return confirm('¿Cancelar este turno?')" class="pt-2 border-t border-gray-100">
            @csrf @method('DELETE')
            <button type="submit" class="text-sm text-red-500 hover:text-red-700">Cancelar turno</button>
        </form>
        @endif
    </div>
</div>
@endsection
