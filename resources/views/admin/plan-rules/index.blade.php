@extends('layouts.app')
@section('title', 'Reglas de acceso')

@section('content')
@php
    $labels = [
        'descenso'           => 'Descenso',
        'mantenimiento'      => 'Mantenimiento',
        'mantenimiento_pleno'=> 'Mant. Pleno',
    ];
@endphp

<div class="max-w-3xl space-y-5">

    <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Reglas de acceso a grupos</h1>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700">
        Definí cuántas veces por mes puede asistir un paciente a cada tipo de grupo según la <strong>fase aplicable</strong> (fase clínica que cargó el coordinador, o el plan contratado si no hay fase). Los valores de fila coinciden con descenso / mantenimiento / mantenimiento pleno.<br>
        Dejá el límite <strong>vacío</strong> para permitir acceso ilimitado. Marcá <strong>Finde libre</strong> para que los sábados y domingos no aplique el límite.
    </div>

    <form method="POST" action="{{ route('admin.plan-rules.save') }}">
        @csrf

        @foreach($plans as $plan)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2" style="background:#f8fafc">
                @if($plan === 'mantenimiento')
                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold">Mantenimiento</span>
                @elseif($plan === 'mantenimiento_pleno')
                    <span class="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 font-semibold">Mantenimiento Pleno</span>
                @else
                    <span class="text-xs px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-semibold">Descenso</span>
                @endif
                <p class="text-sm font-semibold text-gray-700">Plan: {{ $labels[$plan] }}</p>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach($groupTypes as $gt)
                @php $rule = $rules->get("{$plan}.{$gt}"); @endphp
                <div class="px-5 py-4 flex items-center gap-4">
                    <div class="w-36 shrink-0">
                        @if($gt === 'mantenimiento')
                            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Mantenimiento</span>
                        @elseif($gt === 'mantenimiento_pleno')
                            <span class="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 font-medium">Mant. Pleno</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-medium">Descenso</span>
                        @endif
                        <p class="text-xs text-gray-500 mt-1">Grupos tipo</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="number" name="limit[{{ $plan }}][{{ $gt }}]"
                            min="0" max="99" placeholder="∞"
                            value="{{ $rule?->monthly_limit }}"
                            class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-teal-500 outline-none">
                        <span class="text-xs text-gray-500">por mes</span>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer ml-auto">
                        <div class="relative">
                            <input type="checkbox" name="weekend[{{ $plan }}][{{ $gt }}]"
                                value="1"
                                class="sr-only peer"
                                {{ $rule?->weekend_unlimited ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 rounded-full peer-checked:bg-teal-500 transition"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-xs text-gray-600 whitespace-nowrap">Finde libre</span>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <div class="flex gap-3 pt-2">
            <button type="submit"
                class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                Guardar reglas
            </button>
        </div>
    </form>
</div>
@endsection
