@extends('layouts.app')
@section('title', 'Bibliografía IA')

@section('content')
<div class="space-y-6">

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Bibliografía IA</h1>
            <p class="text-sm text-gray-500 mt-1">Fragmentos de los libros del Dr. Ravenna que usa la IA para generar análisis clínicos.</p>
        </div>
        <a href="{{ route('admin.ai-documents.create') }}"
           class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            + Agregar fragmento
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    @if($documents->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-12 text-center">
            <p class="text-gray-400 text-sm">Todavía no hay fragmentos cargados.</p>
            <p class="text-gray-300 text-xs mt-1">Agregá conceptos clave o párrafos de los libros del Dr. Ravenna.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($documents as $doc)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-gray-800 text-sm">{{ $doc->title }}</p>
                                @if($doc->source)
                                    <span class="text-xs text-gray-400 italic">— {{ $doc->source }}</span>
                                @endif
                                @if(!$doc->active)
                                    <span class="text-xs bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full">Inactivo</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-2 line-clamp-2">{{ $doc->content }}</p>
                            <p class="text-xs text-gray-300 mt-1">{{ mb_strlen($doc->content) }} caracteres</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('admin.ai-documents.edit', $doc) }}"
                               class="text-xs text-teal-600 hover:underline">Editar</a>
                            <form action="{{ route('admin.ai-documents.destroy', $doc) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar este fragmento?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-400 hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-xs text-gray-400 text-center">
            {{ $documents->where('active', true)->count() }} fragmentos activos ·
            {{ $documents->sum(fn($d) => mb_strlen($d->content)) }} caracteres totales
        </p>
    @endif

</div>
@endsection
