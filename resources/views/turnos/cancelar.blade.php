@extends('layouts.app')
@section('title', 'Cancelar turno')
@section('og_title', 'Cancelar turno — Plena Grupos')
@section('og_description', 'Tocá para cancelar tu turno con Plena Grupos.')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-full bg-red-50 flex items-center justify-center">
            <svg class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800">¿Cancelar tu turno?</h1>
        <p class="text-sm text-gray-600">
            {{ $appointment->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
            con {{ $appointment->professional?->name ?? '—' }}
        </p>
        <p class="text-sm text-gray-500">{{ $appointment->starts_at->format('d/m/Y') }} a las {{ $appointment->starts_at->format('H:i') }}</p>

        <form method="POST" action="{{ request()->fullUrl() }}" class="pt-2"
              onsubmit="return confirm('¿Seguro que querés cancelar este turno?')">
            @csrf
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-3 rounded-lg transition">
                Cancelar turno
            </button>
        </form>
    </div>
</div>
@endsection
