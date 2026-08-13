@extends('layouts.app')
@section('title', 'Requisitos de turnos')

@section('content')
<div class="max-w-lg space-y-5">

    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Requisitos de turnos mensuales</h1>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700">
        Definí cuántos turnos por mes calendario debe completar cada paciente con cada especialidad. Este mínimo se usa solo como indicador de cumplimiento (dashboard del paciente y reporte de cumplimiento) — no bloquea ningún otro flujo de la app.
    </div>

    <form method="POST" action="{{ route('admin.appointment-requirements.save') }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
        @csrf

        @foreach($specialties as $specialty)
            @php $req = $requirements->get($specialty); @endphp
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs px-2 py-0.5 rounded-full {{ $specialty === 'medico' ? 'bg-indigo-50 text-indigo-700' : 'bg-purple-50 text-purple-700' }} font-medium">
                    {{ $specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
                </span>
                <div class="flex items-center gap-2">
                    <input type="number" name="required[{{ $specialty }}]" min="0" max="99"
                        value="{{ $req?->monthly_required_count ?? 1 }}"
                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-teal-500 outline-none">
                    <span class="text-xs text-gray-500">por mes</span>
                </div>
            </div>
        @endforeach

        <div class="pt-2">
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                Guardar requisitos
            </button>
        </div>
    </form>
</div>
@endsection
