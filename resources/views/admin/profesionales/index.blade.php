@extends('layouts.app')
@section('title', 'Profesionales')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Profesionales</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.create', ['role' => 'medico']) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Médico
            </a>
            <a href="{{ route('admin.users.create', ['role' => 'nutricionista']) }}" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Nutricionista
            </a>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700">
        Configurá el horario de atención y las ausencias/vacaciones de cada profesional. Esto determina los turnos disponibles que ven los pacientes y el admin al reservar.
    </div>

    @foreach([['medico', 'Médicos', $medicos, 'indigo'], ['nutricionista', 'Nutricionistas', $nutricionistas, 'purple']] as [$role, $label, $list, $color])
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2" style="background:#f8fafc">
            <span class="text-xs px-2 py-0.5 rounded-full bg-{{ $color }}-50 text-{{ $color }}-700 font-semibold">{{ $label }}</span>
            <p class="text-sm font-semibold text-gray-700">{{ $list->count() }}</p>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($list as $professional)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$professional" size="sm" />
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $professional->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $professional->email }}
                                · {{ $professional->professional_schedules_count }} bloque(s) de horario
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.professionals.schedule.edit', $professional) }}" class="text-sm text-teal-600 hover:underline">
                        Horarios y ausencias
                    </a>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin {{ strtolower($label) }} cargados.</p>
            @endforelse
        </div>
    </div>
    @endforeach
</div>
@endsection
