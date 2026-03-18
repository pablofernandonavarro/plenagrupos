@extends('layouts.app')
@section('title', 'Usuarios')

@section('content')
<div class="space-y-6">
    {{-- Search --}}
    <form method="GET" action="{{ route('admin.users.index') }}" id="search-form" class="flex gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                placeholder="Buscar por nombre o email..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white">
        </div>
        <button type="submit"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-xl transition">
            Buscar
        </button>
        @if(request('search'))
            <a href="{{ route('admin.users.index') }}"
               class="px-4 py-2.5 border border-gray-200 text-gray-500 text-sm rounded-xl hover:bg-gray-50 transition">
                ✕
            </a>
        @endif
    </form>

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.create', ['role' => 'coordinator']) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Coordinador
            </a>
            <a href="{{ route('admin.users.create', ['role' => 'patient']) }}" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Paciente
            </a>
        </div>
    </div>

    {{-- Coordinators --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <h2 class="font-semibold text-gray-800">Coordinadores ({{ $coordinators->count() }})</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($coordinators as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin coordinadores.</p>
            @endforelse
        </div>
    </div>

    {{-- Patients --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500"></span>
            <h2 class="font-semibold text-gray-800">Pacientes ({{ $patients->count() }})</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($patients as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin pacientes.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
