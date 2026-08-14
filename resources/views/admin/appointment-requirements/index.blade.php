@extends('layouts.app')
@section('title', 'Requisitos de turnos')

@section('content')
@php
    $planLabels = [
        'descenso'            => 'Descenso',
        'mantenimiento'       => 'Mantenimiento',
        'mantenimiento_pleno' => 'Mant. Pleno',
    ];
    $specialtyLabels = [
        'medico'         => 'Médico clínico',
        'nutricionista'  => 'Nutricionista',
    ];
@endphp

<div class="max-w-lg space-y-5">

    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Requisitos de turnos mensuales</h1>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700">
        Definí cuántos turnos por mes debe completar cada paciente con cada especialidad según su <strong>plan contratado</strong>. Este mínimo se usa como indicador de cumplimiento — no bloquea ningún otro flujo de la app.
    </div>

    <form method="POST" action="{{ route('admin.appointment-requirements.save') }}" class="space-y-4">
        @csrf

        @foreach($plans as $plan)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100" style="background:#f8fafc">
                @if($plan === 'mantenimiento')
                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold">Mantenimiento</span>
                @elseif($plan === 'mantenimiento_pleno')
                    <span class="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 font-semibold">Mantenimiento Pleno</span>
                @else
                    <span class="text-xs px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-semibold">Descenso</span>
                @endif
                <span class="text-sm font-semibold text-gray-700 ml-1">Plan: {{ $planLabels[$plan] }}</span>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach($specialties as $specialty)
                @php $req = $requirements->get("{$plan}.{$specialty}"); @endphp
                <div class="px-5 py-4 flex items-center gap-4">
                    <div class="w-36 shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $specialty === 'medico' ? 'bg-indigo-50 text-indigo-700' : 'bg-purple-50 text-purple-700' }} font-medium">
                            {{ $specialtyLabels[$specialty] }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" name="required[{{ $plan }}][{{ $specialty }}]"
                            min="0" max="99"
                            value="{{ $req?->monthly_required_count ?? 1 }}"
                            class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-teal-500 outline-none">
                        <span class="text-xs text-gray-500">por mes</span>
                    </div>
                </div>
                @endforeach
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
