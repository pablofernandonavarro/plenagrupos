@extends('layouts.app')
@section('title', 'Turnos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Turnos</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.turnos.calendar') }}" class="border border-gray-200 text-gray-600 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                Ver calendario
            </a>
            <a href="{{ route('admin.turnos.compliance') }}" class="border border-gray-200 text-gray-600 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                Cumplimiento
            </a>
            <a href="{{ route('admin.turnos.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Nuevo turno
            </a>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <select name="specialty" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" onchange="this.form.submit()">
            <option value="">Todas las especialidades</option>
            <option value="medico" {{ $specialty === 'medico' ? 'selected' : '' }}>Médico clínico</option>
            <option value="nutricionista" {{ $specialty === 'nutricionista' ? 'selected' : '' }}>Nutricionista</option>
        </select>
        <select name="professional_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" onchange="this.form.submit()">
            <option value="">Todos los profesionales</option>
            @foreach($professionals as $p)
                <option value="{{ $p->id }}" {{ (string) $professionalId === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        @if($specialty || $professionalId)
            <a href="{{ route('admin.turnos.index') }}" class="px-3 py-2 border border-gray-200 text-gray-500 text-sm rounded-lg hover:bg-gray-50">Limpiar</a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="divide-y divide-gray-50">
            @forelse($appointments as $a)
                <div class="px-5 py-3 flex justify-between items-center flex-wrap gap-2">
                    <div>
                        <p class="text-sm font-medium text-gray-800">
                            {{ $a->patient?->name ?? '(paciente eliminado)' }}
                            <span class="text-gray-400">con</span>
                            {{ $a->professional?->name ?? '(profesional eliminado)' }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $a->starts_at->format('d/m/Y H:i') }}
                            · {{ $a->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
                            · {{ $a->booked_by === 'admin' ? 'Reservado por admin' : 'Reservado por paciente' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-appointment-status-badge :status="$a->status" />
                        <a href="{{ route('admin.turnos.edit', $a) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin turnos.</p>
            @endforelse
        </div>
    </div>

    {{ $appointments->links() }}
</div>
@endsection
