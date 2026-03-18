@extends('layouts.app')
@section('title', 'Grupos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Grupos</h1>
        <a href="{{ route('admin.groups.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            + Nuevo grupo
        </a>
    </div>

    {{-- Search & filter --}}
    <form method="GET" action="{{ route('admin.groups.index') }}" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar grupo..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="flex gap-2">
            @foreach([''=>'Todos', 'active'=>'Activos', 'closed'=>'Finalizados'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                    class="px-4 py-2.5 rounded-lg text-sm font-medium border transition
                        {{ request('status', '') === $val
                            ? 'bg-teal-600 text-white border-teal-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-teal-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    <div class="grid gap-4">
        @forelse($groups as $group)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold text-gray-800">{{ $group->name }}</h2>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $group->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $group->active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        @if($group->description)
                            <p class="text-sm text-gray-500 mt-1">{{ $group->description }}</p>
                        @endif
                        @if($group->meeting_day || $group->meeting_time)
                            <p class="text-xs text-teal-600 mt-1">
                                {{ $group->meeting_day }}{{ $group->meeting_day && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? substr($group->meeting_time, 0, 5) . ' hs' : '' }}
                            </p>
                        @endif
                        <div class="flex gap-4 mt-3 text-xs text-gray-500">
                            <span>{{ $group->coordinators->count() }} coordinador(es)</span>
                            <span>{{ $group->patients->count() }} paciente(s)</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 justify-end">
                        @if($group->active)
                        <form action="{{ route('admin.groups.toggle', $group) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="text-sm font-semibold px-4 py-1.5 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                                Finalizar
                            </button>
                        </form>
                        @else
                            <span class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-400">Finalizado</span>
                        @endif
                        <a href="{{ route('admin.groups.show', $group) }}" class="text-sm text-teal-600 hover:underline self-center">Gestionar</a>
                        <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('¿Eliminar grupo?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl p-12 text-center text-gray-400">
                <p class="text-lg">No hay grupos creados.</p>
                <a href="{{ route('admin.groups.create') }}" class="mt-4 inline-block text-teal-600 hover:underline text-sm">Crear el primer grupo</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
