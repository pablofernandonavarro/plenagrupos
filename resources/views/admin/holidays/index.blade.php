@extends('layouts.app')
@section('title', 'Feriados')

@section('content')
<div class="max-w-2xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Feriados</h1>
        <p class="text-sm text-gray-400">Los días cargados acá quedan bloqueados para sacar turnos nuevos (médico y nutricionista, todos los profesionales). No afecta turnos ya reservados.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100" style="background:#f8fafc">
            <p class="text-sm font-semibold text-gray-700">Próximos feriados</p>
        </div>

        <div class="divide-y divide-gray-50">
            @forelse($holidays as $h)
                <div class="px-5 py-3 flex justify-between items-center {{ $h->date->isPast() ? 'opacity-50' : '' }}">
                    <div>
                        <p class="text-sm text-gray-800">{{ $h->date->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $h->name }}</p>
                    </div>
                    <form action="{{ route('admin.holidays.destroy', $h) }}" method="POST" onsubmit="return confirm('¿Eliminar este feriado?')">
                        @csrf @method('DELETE')
                        <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin feriados cargados.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('admin.holidays.store') }}" class="p-5 border-t border-gray-100 space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fecha</label>
                    <input type="date" name="date" required value="{{ old('date') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre</label>
                    <input type="text" name="name" maxlength="255" required value="{{ old('name') }}" placeholder="Ej: Día de la Independencia"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            @error('date')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
            @error('name')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
            <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Agregar feriado
            </button>
        </form>
    </div>
</div>
@endsection
