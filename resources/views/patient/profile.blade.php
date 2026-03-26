@extends('layouts.app')
@section('title', 'Mi Perfil')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Mi perfil</h1>
        <p class="text-sm text-gray-500 mt-0.5">Tus datos personales y configuración</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Foto y grupo de pertenencia --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Datos personales</h2>
        </div>
        <div class="px-5 py-4">
            <form action="{{ route('patient.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                {{-- Avatar --}}
                <div class="flex items-center gap-4">
                    <x-avatar :user="auth()->user()" size="lg" />
                    <div class="flex-1">
                        <p class="text-base font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ auth()->user()->email }}</p>
                        <label class="block text-xs text-gray-500 mt-2 mb-1">Cambiar foto</label>
                        <input type="file" name="avatar" accept="image/*"
                            class="block w-full text-sm text-gray-500
                                   file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                   file:text-xs file:font-medium file:bg-teal-50 file:text-teal-700
                                   hover:file:bg-teal-100">
                        @error('avatar')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                {{-- Grupo de pertenencia --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grupo de pertenencia</label>
                    <select name="belonging_group_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm bg-white">
                        <option value="">— Sin grupo —</option>
                        @foreach($availableGroups as $ag)
                            <option value="{{ $ag->id }}" {{ old('belonging_group_id', auth()->user()->belonging_group_id) == $ag->id ? 'selected' : '' }}>
                                {{ $ag->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('belonging_group_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                    Guardar
                </button>
            </form>
        </div>
    </div>

    {{-- Rango de mantenimiento --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Rango de mantenimiento</h2>
            <p class="text-xs text-gray-400 mt-0.5">Peso mínimo (piso) y máximo (techo) que querés mantener.</p>
        </div>
        <div class="px-5 py-4">
            <form action="{{ route('patient.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Piso (kg)</label>
                        <input type="number" step="0.01" min="0" max="300" name="peso_piso"
                            value="{{ old('peso_piso', auth()->user()->peso_piso) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Ej: 68.00">
                        @error('peso_piso')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Techo (kg)</label>
                        <input type="number" step="0.01" min="0" max="300" name="peso_techo"
                            value="{{ old('peso_techo', auth()->user()->peso_techo) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Ej: 72.00">
                        @error('peso_techo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                    Guardar
                </button>
            </form>
        </div>
    </div>

    {{-- Historial de pesos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Historial de pesos</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($weightRecords as $record)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $record->group?->name ?? '(Grupo eliminado)' }}</p>
                        <p class="text-xs text-gray-400">{{ $record->recorded_at->format('d/m/Y H:i') }}</p>
                        @if($record->notes)
                            <p class="text-xs text-gray-500 mt-0.5 italic">{{ $record->notes }}</p>
                        @endif
                    </div>
                    <span class="text-xl font-bold text-teal-600">{{ $record->weight }} kg</span>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">
                    <p>Aún no tenés pesos registrados.</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
