@extends('layouts.app')
@section('title', 'Agregar fragmento')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.ai-documents.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Agregar fragmento</h1>
    </div>

    <div class="bg-teal-50 border border-teal-200 rounded-xl px-5 py-4 mb-6">
        <p class="text-sm text-teal-800 font-medium">¿Qué cargar?</p>
        <ul class="text-xs text-teal-700 mt-1 space-y-0.5 list-disc list-inside">
            <li>Conceptos clave del método Ravenna (hambre emocional, el cuerpo como síntoma, etc.)</li>
            <li>Párrafos de sus libros que describen el abordaje terapéutico</li>
            <li>Criterios clínicos para evaluar progreso o estancamiento</li>
            <li>Frases o marcos conceptuales específicos que usás en las sesiones</li>
        </ul>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.ai-documents.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título del fragmento *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    placeholder="Ej: Hambre emocional vs hambre real"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fuente</label>
                <input type="text" name="source" value="{{ old('source') }}"
                    placeholder="Ej: Ravenna — Obesos, Cap. 3"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contenido *</label>
                <textarea name="content" rows="10" required
                    placeholder="Pegá acá el texto del libro o escribí el concepto con tus palabras..."
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm font-mono">{{ old('content') }}</textarea>
                @error('content')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>
                <div class="flex items-center gap-2 mt-5">
                    <input type="checkbox" name="active" value="1" id="active"
                        {{ old('active', true) ? 'checked' : '' }}
                        class="rounded text-teal-600 focus:ring-teal-500">
                    <label for="active" class="text-sm text-gray-700">Activo (incluir en análisis)</label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Guardar fragmento
                </button>
                <a href="{{ route('admin.ai-documents.index') }}"
                   class="text-gray-600 hover:text-gray-800 px-4 py-2.5 text-sm">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
