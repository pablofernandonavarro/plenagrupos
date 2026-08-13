@extends('layouts.app')
@section('title', 'Confirmar turno')
@section('og_title', 'Confirmar turno — Plena Grupos')
@section('og_description', 'Tocá para confirmar tu turno con Plena Grupos.')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-full bg-teal-50 flex items-center justify-center">
            <svg class="w-7 h-7 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800">Confirmá tu turno</h1>
        <p class="text-sm text-gray-600">
            {{ $appointment->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
            con {{ $appointment->professional?->name ?? '—' }}
        </p>
        <p class="text-sm text-gray-500">{{ $appointment->starts_at->format('d/m/Y') }} a las {{ $appointment->starts_at->format('H:i') }}</p>

        <form method="POST" action="{{ request()->fullUrl() }}" class="pt-2">
            @csrf
            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-3 rounded-lg transition">
                Confirmar turno
            </button>
        </form>
    </div>
</div>
@endsection
