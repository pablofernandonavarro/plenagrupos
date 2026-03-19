@extends('layouts.app')
@section('title', 'Pacientes')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">Pacientes</h1>
        <p class="text-sm text-gray-500 mt-1">Pacientes de tus grupos.</p>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('coordinator.patients.index') }}" class="flex gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar por nombre o email..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white">
        </div>
        <button type="submit" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-xl transition">
            Buscar
        </button>
        @if(request('search'))
            <a href="{{ route('coordinator.patients.index') }}"
               class="px-4 py-2.5 border border-gray-200 text-gray-500 text-sm rounded-xl hover:bg-gray-50 transition">✕</a>
        @endif
    </form>

    {{-- Patient list --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="divide-y divide-gray-50">
            @forelse($patients as $patient)
                @php
                    $lastWeight = $patient->weightRecords->first()?->weight;
                    $groups = $patient->patientGroups;
                    $attendedGroupIds = $patient->attendances->pluck('group_id')->unique();
                    $groupBreakdown = $groups->whereIn('id', $attendedGroupIds)->map(function ($g) {
                        $mins = 0;
                        if ($g->started_at && $g->ended_at) $mins = (int) $g->started_at->diffInMinutes($g->ended_at);
                        elseif ($g->started_at && $g->active) $mins = (int) $g->started_at->diffInMinutes(now());
                        return ['name' => $g->name, 'status' => $g->status, 'minutes' => $mins];
                    })->filter(fn($g) => $g['minutes'] > 0)->values();
                    $totalMins = $groupBreakdown->sum('minutes');
                    $hrs = intdiv($totalMins, 60); $min = $totalMins % 60;
                @endphp
                <div class="px-5 py-4 flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-800 text-sm">{{ $patient->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5 truncate">
                            {{ $patient->email }}
                            @if($patient->phone) · {{ $patient->phone }} @endif
                        </p>

                        {{-- Per-group time breakdown --}}
                        @if($groupBreakdown->isNotEmpty())
                            <div class="mt-2 space-y-1">
                                @foreach($groupBreakdown as $gb)
                                    @php $gh = intdiv($gb['minutes'], 60); $gm = $gb['minutes'] % 60; @endphp
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full shrink-0
                                            {{ $gb['status'] === 'active' ? 'bg-green-400' : ($gb['status'] === 'pending' ? 'bg-yellow-400' : 'bg-gray-300') }}"></span>
                                        <span class="text-gray-600 truncate">{{ $gb['name'] }}</span>
                                        <span class="text-gray-400 shrink-0 ml-auto">
                                            {{ $gh > 0 ? $gh.'h ' : '' }}{{ $gm }}m
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-300 mt-1.5">Sin tiempo registrado</p>
                        @endif
                    </div>

                    <div class="flex flex-col items-end gap-2 shrink-0">
                        {{-- Total time --}}
                        @if($totalMins > 0)
                            <div class="text-right">
                                <p class="text-base font-bold text-purple-600">{{ $hrs > 0 ? $hrs.'h ' : '' }}{{ $min }}m</p>
                                <p class="text-xs text-gray-400">Total en grupos</p>
                            </div>
                        @endif
                        {{-- Last weight --}}
                        @if($lastWeight)
                            <div class="text-right">
                                <p class="text-base font-bold text-teal-600">{{ $lastWeight }} kg</p>
                                <p class="text-xs text-gray-400">Último peso</p>
                            </div>
                        @endif
                        <a href="{{ route('coordinator.patients.show', $patient) }}"
                           class="text-xs bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-lg transition font-medium">
                            Ver historial
                        </a>
                    </div>
                </div>
            @empty
                <p class="px-5 py-12 text-center text-gray-400 text-sm">No hay pacientes en tus grupos.</p>
            @endforelse
        </div>
    </div>

    @if($patients->hasPages())
        <div>{{ $patients->links() }}</div>
    @endif

</div>
@endsection
