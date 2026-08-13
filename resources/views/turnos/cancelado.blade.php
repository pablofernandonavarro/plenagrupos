@extends('layouts.app')
@section('title', 'Turno cancelado')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-full bg-gray-100 flex items-center justify-center">
            <svg class="w-7 h-7 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800">Turno cancelado</h1>
        <p class="text-sm text-gray-600">
            {{ $appointment->specialty === 'medico' ? 'Médico clínico' : 'Nutricionista' }}
            con {{ $appointment->professional?->name ?? '—' }}
        </p>
        <p class="text-sm text-gray-500">{{ $appointment->starts_at->format('d/m/Y') }} a las {{ $appointment->starts_at->format('H:i') }}</p>
    </div>
</div>
@endsection
