@extends('layouts.app')
@section('title', 'Crear Grupo')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.groups.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Crear Grupo</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.groups.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del grupo *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none text-sm"
                    placeholder="Ej: Grupo Lunes Mañana">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" rows="2"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none text-sm"
                    placeholder="Descripción opcional...">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Día de reunión</label>
                    <select name="meeting_day"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm bg-white">
                        <option value="">Sin día fijo</option>
                        @foreach(['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'] as $day)
                            <option value="{{ $day }}" {{ old('meeting_day') === $day ? 'selected' : '' }}>{{ $day }}</option>
                        @endforeach
                    </select>
                    @error('meeting_day')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora de inicio</label>
                    <input type="time" name="meeting_time" value="{{ old('meeting_time') }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>
            </div>

            {{-- Auto sessions toggle --}}
            <div class="bg-teal-50 border border-teal-200 rounded-lg px-4 py-3">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_sessions" value="1"
                        {{ old('auto_sessions') ? 'checked' : '' }}
                        class="mt-0.5 rounded text-teal-600 focus:ring-teal-500">
                    <div>
                        <p class="text-sm font-medium text-teal-800">Sesiones automáticas</p>
                        <p class="text-xs text-teal-600 mt-0.5">Se creará una sesión automáticamente 1 día antes de cada reunión programada.</p>
                    </div>
                </label>
            </div>

            @if($coordinators->isNotEmpty())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Coordinadores</label>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @foreach($coordinators as $coordinator)
                        <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="coordinator_ids[]" value="{{ $coordinator->id }}"
                                {{ in_array($coordinator->id, old('coordinator_ids', [])) ? 'checked' : '' }}
                                class="rounded text-teal-600">
                            <span class="text-sm text-gray-700">{{ $coordinator->name }}</span>
                            <span class="text-xs text-gray-400">{{ $coordinator->email }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Crear grupo
                </button>
                <a href="{{ route('admin.groups.index') }}" class="text-gray-600 hover:text-gray-800 px-4 py-2.5 text-sm">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
