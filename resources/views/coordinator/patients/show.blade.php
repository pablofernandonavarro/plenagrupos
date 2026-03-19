@extends('layouts.app')
@section('title', $patient->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start gap-3">
        <a href="{{ route('coordinator.patients.index') }}" class="mt-1 text-gray-400 hover:text-gray-600 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $patient->name }}</h1>
            <p class="text-sm text-gray-400 mt-0.5">
                {{ $patient->email }}
                @if($patient->phone) · {{ $patient->phone }} @endif
            </p>
            @if($groups->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach($groups as $g)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $g->status === 'active' ? 'bg-green-100 text-green-700' : ($g->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500') }}">
                            {{ $g->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-teal-600">{{ $lastWeight ? $lastWeight . ' kg' : '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Último peso</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            @if($totalChange !== null)
                <p class="text-2xl font-bold {{ $totalChange < 0 ? 'text-green-600' : ($totalChange > 0 ? 'text-red-500' : 'text-gray-400') }}">
                    {{ $totalChange > 0 ? '+' : '' }}{{ $totalChange }} kg
                </p>
            @else
                <p class="text-2xl font-bold text-gray-300">—</p>
            @endif
            <p class="text-xs text-gray-500 mt-1">Variación total</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $attendances->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Asistencias</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl font-bold text-purple-600">{{ $weightRecords->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Pesos registrados</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: info + groups --}}
        <div class="space-y-4">

            {{-- Range --}}
            @if($patient->peso_piso || $patient->peso_techo || $patient->ideal_weight)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="font-semibold text-gray-800 text-sm mb-3">Datos de peso</h2>
                <div class="space-y-2 text-sm">
                    @if($patient->ideal_weight)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Peso ideal</span>
                            <span class="font-medium text-gray-800">{{ $patient->ideal_weight }} kg</span>
                        </div>
                    @endif
                    @if($patient->peso_piso || $patient->peso_techo)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Rango mant.</span>
                            <span class="font-medium text-teal-700">
                                {{ $patient->peso_piso ?? '?' }} – {{ $patient->peso_techo ?? '?' }} kg
                            </span>
                        </div>
                    @endif
                    @if($lastWeight && ($patient->peso_piso || $patient->peso_techo))
                        @php
                            $piso = $patient->peso_piso; $techo = $patient->peso_techo;
                            if ($techo && $lastWeight > $techo) { $rangeStatus = 'above'; $diff = round($lastWeight - $techo, 2); }
                            elseif ($piso && $lastWeight < $piso) { $rangeStatus = 'below'; $diff = round($lastWeight - $piso, 2); }
                            else { $rangeStatus = 'ok'; $diff = null; }
                        @endphp
                        <div class="flex justify-between items-center pt-1 border-t border-gray-50">
                            <span class="text-gray-500">Estado actual</span>
                            @if($rangeStatus === 'ok')
                                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">✓ En rango</span>
                            @elseif($rangeStatus === 'above')
                                <span class="text-xs font-semibold text-red-500 bg-red-50 px-2 py-0.5 rounded-full">↑ +{{ $diff }} kg</span>
                            @else
                                <span class="text-xs font-semibold text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">↓ {{ $diff }} kg</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- Right: weight history + attendance --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Weight history --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Historial de pesos</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($weightRecords as $record)
                        @php
                            $prev = $weightRecords->get($loop->index + 1)?->weight;
                            $diff = $prev ? round($record->weight - $prev, 2) : null;
                        @endphp
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $record->group->name }}</p>
                                <p class="text-xs text-gray-400">{{ $record->recorded_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($diff !== null)
                                    <span class="text-xs font-semibold {{ $diff < 0 ? 'text-green-600' : ($diff > 0 ? 'text-red-500' : 'text-gray-400') }}">
                                        {{ $diff > 0 ? '↑ +' : ($diff < 0 ? '↓ ' : '= ') }}{{ $diff }} kg
                                    </span>
                                @endif
                                <span class="text-lg font-bold text-teal-600">{{ $record->weight }} kg</span>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400">Sin pesos registrados.</p>
                    @endforelse
                </div>
            </div>

            {{-- Attendance history --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Historial de asistencias</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($attendances as $att)
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $att->group->name }}</p>
                                <p class="text-xs text-gray-400">{{ $att->attended_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full bg-teal-50 text-teal-700 font-medium">✓ Presente</span>
                        </div>
                    @empty
                        <p class="px-5 py-8 text-center text-sm text-gray-400">Sin asistencias registradas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
