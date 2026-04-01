@extends('layouts.app')
@section('title', 'Editar InBody')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('patient.inbody.create') }}" class="text-gray-400 hover:text-gray-600 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Editar InBody</h1>
            <p class="text-sm text-gray-400">Modificá los datos del registro del {{ $record->test_date->format('d/m/Y') }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    {{-- Edit form --}}
    <form method="POST" action="{{ route('patient.inbody.update', $record) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-5 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Datos del registro</h2>
                <span class="text-xs bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full font-medium">Editando</span>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fecha del estudio</label>
                <input type="date" name="test_date" value="{{ old('test_date', $record->test_date->format('Y-m-d')) }}" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                @error('test_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Composición corporal</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach([
                        ['weight',               'Peso (kg)',            $record->weight],
                        ['skeletal_muscle_mass', 'Masa muscular (kg)',   $record->skeletal_muscle_mass],
                        ['body_fat_mass',        'Masa grasa (kg)',      $record->body_fat_mass],
                        ['body_fat_percentage',  'Grasa corporal (%)',   $record->body_fat_percentage],
                    ] as [$name, $label, $value])
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                        <input type="number" step="any" name="{{ $name }}" value="{{ old($name, $value) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        @error($name)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Métricas adicionales</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach([
                        ['bmi',                 'IMC',                    $record->bmi],
                        ['basal_metabolic_rate','Metabolismo basal (kcal)',$record->basal_metabolic_rate],
                        ['visceral_fat_level',  'Grasa visceral',         $record->visceral_fat_level],
                        ['total_body_water',    'Agua corporal (kg)',      $record->total_body_water],
                        ['proteins',            'Proteínas (kg)',          $record->proteins],
                        ['minerals',            'Minerales (kg)',          $record->minerals],
                        ['inbody_score',        'Puntaje InBody',         $record->inbody_score],
                        ['obesity_degree',      'Grado de obesidad (%)',  $record->obesity_degree],
                    ] as [$name, $label, $value])
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                        <input type="number" step="any" name="{{ $name }}" value="{{ old($name, $value) }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                        @error($name)<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Notas (opcional)</label>
                <textarea name="notes" rows="2" placeholder="Observaciones..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none">{{ old('notes', $record->notes) }}</textarea>
                @error('notes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">
                    Actualizar imagen del reporte (opcional)
                    @if($record->image_path)
                        <a href="{{ Storage::url($record->image_path) }}" target="_blank" class="text-teal-600 hover:underline ml-1">Ver imagen actual</a>
                    @endif
                </label>
                <input type="file" name="images[]" accept="image/*" multiple
                    class="block w-full text-sm text-gray-600
                           file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                           file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-600
                           hover:file:bg-gray-100 cursor-pointer">
                <p class="text-xs text-gray-400 mt-1">Si subís una nueva imagen, reemplazará la anterior</p>
                @error('images')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-semibold px-5 py-3 rounded-xl transition text-sm">
                Guardar cambios
            </button>
            <a href="{{ route('patient.inbody.create') }}"
               class="px-5 py-3 border border-gray-200 text-gray-500 text-sm rounded-xl hover:bg-gray-50 transition text-center">
                Cancelar
            </a>
        </div>
    </form>

</div>
@endsection
