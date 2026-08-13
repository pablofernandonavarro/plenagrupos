@extends('layouts.app')
@section('title', 'Turno confirmado')
@section('og_title', 'Turno confirmado — Plena Grupos')
@section('og_description', $appointment->status === 'cancelled' ? 'Este turno ya fue cancelado.' : 'Tu turno con Plena Grupos quedó confirmado.')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center space-y-4">
        @if($appointment->status === 'cancelled')
            <div class="w-14 h-14 mx-auto rounded-full bg-red-50 flex items-center justify-center">
                <svg class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">Este turno ya fue cancelado</h1>
            <p class="text-sm text-gray-500">No se puede confirmar un turno cancelado.</p>
        @else
            <div class="w-14 h-14 mx-auto rounded-full bg-green-50 flex items-center justify-center">
                <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-xl font-bold text-gray-800">¡Turno confirmado!</h1>
            <p class="text-sm text-gray-600">
                {{ $appointment->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
                con {{ $appointment->professional?->name ?? '—' }}
            </p>
            <p class="text-sm text-gray-500">{{ $appointment->starts_at->format('d/m/Y') }} a las {{ $appointment->starts_at->format('H:i') }}</p>
        @endif
    </div>
</div>
@endsection
