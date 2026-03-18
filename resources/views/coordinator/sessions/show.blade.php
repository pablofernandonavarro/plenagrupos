@extends('layouts.app')
@section('title', $session->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('coordinator.dashboard') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $session->name }}</h1>
            <p class="text-sm text-gray-500">{{ $session->group->name }} · {{ $session->session_date->format('d \d\e F, Y') }}</p>
        </div>
        <span class="ml-2 px-3 py-1 rounded-full text-sm font-medium {{ $session->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
            {{ $session->status === 'active' ? 'Activa' : 'Cerrada' }}
        </span>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-teal-600">{{ $session->attendances->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Asistentes</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-blue-600">{{ $session->weightRecords->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Pesos registrados</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-green-600">{{ $avgWeight ? number_format($avgWeight, 1) : '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Promedio (kg)</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-lg font-bold text-gray-600">
                {{ $minWeight ? number_format($minWeight, 1) : '—' }} /
                {{ $maxWeight ? number_format($maxWeight, 1) : '—' }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Min / Max (kg)</p>
        </div>
    </div>

    {{-- Attendees Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Detalle de asistentes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-left">Hora ingreso</th>
                        <th class="px-5 py-3 text-right">Peso (kg)</th>
                        <th class="px-5 py-3 text-left">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($session->attendances as $attendance)
                        @php $wr = $session->weightRecords->firstWhere('user_id', $attendance->user_id); @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-800">{{ $attendance->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $attendance->user->email }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $attendance->checked_in_at->format('H:i') }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $wr ? 'text-teal-600' : 'text-gray-300' }}">
                                {{ $wr ? $wr->weight : '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ $wr?->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-gray-400">Sin asistentes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
