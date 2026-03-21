@extends('layouts.app')
@section('title', 'Grupos')

@section('content')
<div class="space-y-5">

    {{-- Header --}}
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Grupos</h1>
        <a href="{{ route('admin.groups.create') }}"
            class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            + Nuevo grupo
        </a>
    </div>

    {{-- Search & filter --}}
    <form method="GET" action="{{ route('admin.groups.index') }}" class="flex flex-col sm:flex-row gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar grupo..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white">
        </div>
        <div class="flex gap-2">
            @foreach([''=>'Todos', 'active'=>'En curso', 'pending'=>'Sin iniciar', 'closed'=>'Finalizados'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                    class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl text-sm font-medium border transition
                        {{ request('status', '') === $val
                            ? 'bg-teal-600 text-white border-teal-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-teal-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    {{-- Groups list --}}
    <div class="space-y-3">
        @forelse($groups as $group)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                {{-- Main row --}}
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3">

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h2 class="font-semibold text-gray-900 text-base">{{ $group->name }}</h2>
                            @if($group->status === 'active')
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    En curso
                                </span>
                            @elseif($group->status === 'pending')
                                <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium">
                                    Sin iniciar
                                </span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">
                                    Finalizado
                                </span>
                            @endif
                            @if($group->started_at)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $group->started_at->format('d/m · H:i') }}@if($group->ended_at) → {{ $group->started_at->isSameDay($group->ended_at) ? $group->ended_at->format('H:i') : $group->ended_at->format('d/m · H:i') }}@endif
                                </span>
                            @endif
                            @if($group->auto_sessions)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 border border-purple-100 font-medium">
                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Sesiones automáticas
                                </span>
                            @endif
                        </div>

                        @if($group->description)
                            <p class="text-sm text-gray-500 mb-1">{{ $group->description }}</p>
                        @endif

                        @if($group->meeting_day || $group->meeting_time)
                            <p class="text-xs text-teal-600 font-medium mb-2">
                                <svg class="inline w-3 h-3 mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $group->meeting_day }}{{ $group->meeting_day && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? $group->meeting_time_formatted . ' hs' : '' }}
                            </p>
                        @endif

                        <div class="flex gap-3 text-xs text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $group->coordinators->count() }} coordinador(es)
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $group->patients->count() }} paciente(s)
                            </span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 sm:shrink-0">
                        @if($group->status === 'active')
                            <form action="{{ route('admin.groups.toggle', $group) }}" method="POST"
                                  onsubmit="return confirm('¿Finalizar el grupo? Esta acción no se puede deshacer.')">
                                @csrf
                                <button type="submit"
                                    class="text-sm font-medium px-4 py-2 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 transition">
                                    Finalizar
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.groups.show', $group) }}"
                            class="text-sm font-medium px-4 py-2 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 transition">
                            Gestionar
                        </a>
                        <form action="{{ route('admin.groups.destroy', $group) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar grupo?')">
                            @csrf @method('DELETE')
                            <button class="p-2 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="font-medium text-gray-500">No hay grupos creados</p>
                <a href="{{ route('admin.groups.create') }}" class="mt-2 inline-block text-teal-600 hover:underline text-sm">
                    Crear el primer grupo
                </a>
            </div>
        @endforelse
    </div>

    @if($groups->hasPages())
        <div>{{ $groups->links() }}</div>
    @endif

</div>
@endsection
