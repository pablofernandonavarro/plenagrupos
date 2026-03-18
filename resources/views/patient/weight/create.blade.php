@extends('layouts.app')
@section('title', 'Registrar Peso')

@section('content')
<div class="max-w-md mx-auto">

    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-teal-100 rounded-2xl mb-3">
            <svg class="w-8 h-8 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-800">Registrar mi peso</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $attendance->group->name }} · {{ now()->format('d/m/Y') }}</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('patient.weight.store') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 text-center">Peso actual (kg)</label>
                <input type="number" name="weight" step="0.1" min="1" max="300" required autofocus
                    class="w-full px-4 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 outline-none text-3xl font-bold text-center text-teal-600"
                    placeholder="0.0">
                @error('weight')<p class="text-red-500 text-xs mt-1 text-center">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
                <textarea name="notes" rows="2"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="¿Cómo te sentiste esta semana?">{{ old('notes') }}</textarea>
            </div>

            <button type="submit"
                class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-xl transition text-base">
                Guardar peso
            </button>
        </form>
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('patient.dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600">
            Omitir por ahora
        </a>
    </div>
</div>
@endsection
