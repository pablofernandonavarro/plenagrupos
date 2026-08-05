@extends('layouts.app')
@section('title', 'Diagnóstico: grupo de pertenencia')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Diagnóstico: grupo de pertenencia</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            Pacientes cuyo "Grupo de pertenencia" no coincide con ninguno de los grupos en los que están
            actualmente inscriptos (sin salida registrada). Página temporal de solo lectura.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4 flex gap-8">
        <div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalWithBelongingGroup }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Pacientes con grupo de pertenencia definido</p>
        </div>
        <div>
            <p class="text-2xl font-bold {{ $patients->count() > 0 ? 'text-red-500' : 'text-green-600' }}">{{ $patients->count() }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Desincronizados</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Pacientes desincronizados</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($patients as $patient)
                <div class="px-5 py-3">
                    <p class="text-sm font-medium text-gray-800">{{ $patient->name }}</p>
                    <p class="text-xs text-gray-400">{{ $patient->email }}</p>
                    <p class="text-xs mt-1">
                        <span class="text-gray-500">Grupo de pertenencia:</span>
                        <span class="font-medium text-gray-700">{{ $patient->belongingGroup->name ?? '(grupo eliminado)' }}</span>
                    </p>
                    <p class="text-xs mt-0.5">
                        <span class="text-gray-500">Grupos activos hoy:</span>
                        <span class="font-medium text-gray-700">
                            {{ $patient->patientGroups->isNotEmpty() ? $patient->patientGroups->pluck('name')->join(', ') : '— ninguno —' }}
                        </span>
                    </p>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">
                    <p>Ningún paciente desincronizado. Todo coincide.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
