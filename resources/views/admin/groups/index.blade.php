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
    <form method="GET" action="{{ route('admin.groups.index') }}" class="flex flex-col gap-2">
        {{-- Preserva el status al buscar con Enter --}}
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="flex flex-col sm:flex-row gap-2">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Buscar grupo..."
                    class="w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-gray-400 hover:text-teal-600 hover:bg-teal-50 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                </button>
            </div>
            @if($coordinators->isNotEmpty())
            <select name="coordinator_id" onchange="this.form.submit()"
                class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white text-gray-600">
                <option value="">Todos los coordinadores</option>
                @foreach($coordinators as $c)
                    <option value="{{ $c->id }}" {{ request('coordinator_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
            @endif
            <select name="modality" onchange="this.form.submit()"
                class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white text-gray-600">
                <option value="">Todas las modalidades</option>
                <option value="presencial" {{ request('modality') === 'presencial' ? 'selected' : '' }}>Presencial</option>
                <option value="virtual"    {{ request('modality') === 'virtual'    ? 'selected' : '' }}>Virtual</option>
                <option value="hibrido"    {{ request('modality') === 'hibrido'    ? 'selected' : '' }}>Híbrido</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            @foreach(['today'=>'Hoy', 'active'=>'Activos', 'closed'=>'Finalizados', ''=>'Todos'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                    class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl text-sm font-medium border transition
                        {{ $status === $val
                            ? 'bg-teal-600 text-white border-teal-600'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-teal-400' }}">
                    {{ $label }}
                </button>
            @endforeach
            <select name="per_page" onchange="this.form.submit()"
                class="ml-auto px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white text-gray-600">
                @foreach([10=>10, 25=>25, 50=>50] as $n => $label)
                    <option value="{{ $n }}" {{ request('per_page', 10) == $n ? 'selected' : '' }}>{{ $n }} por página</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- Groups list --}}
    <div class="space-y-3">
        @forelse($groups as $group)
            @php
                $isInSession = $group->status === 'active';
                $borderClass = $isInSession ? 'border-green-200 shadow-green-50' : 'border-gray-100';
            @endphp
            <div class="bg-white rounded-2xl border {{ $borderClass }} shadow-sm overflow-hidden">

                {{-- Franja horaria destacada en vista Hoy --}}
                @if($status === 'today' && $group->meeting_time)
                    <div class="px-5 pt-3 pb-0 flex items-center gap-3">
                        @if($isInSession)
                            <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-3 py-1.5">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                <span class="text-sm font-bold text-green-700">En sesión ahora</span>
                                <span class="text-xs text-green-600">{{ $group->meeting_time_formatted }} hs</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-sm font-semibold text-gray-600">Hoy {{ $group->meeting_time_formatted }} hs</span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Main row --}}
                <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3">

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h2 class="font-semibold text-gray-900 text-base">{{ $group->name }}</h2>
                            @if($status !== 'today')
                                @if($group->status === 'active')
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                        En sesión
                                    </span>
                                @elseif($group->status === 'pending' && !$group->auto_sessions)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-medium">Sin iniciar</span>
                                @elseif($group->status === 'closed')
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium">Finalizado</span>
                                @endif
                            @endif
                            @php
                                $modalityStyles = ['presencial'=>'bg-blue-50 text-blue-700','virtual'=>'bg-violet-50 text-violet-700','hibrido'=>'bg-orange-50 text-orange-700'];
                                $modalityLabels = ['presencial'=>'Presencial','virtual'=>'Virtual','hibrido'=>'Híbrido'];
                                $m = $group->modality ?? 'presencial';
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $modalityStyles[$m] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ $modalityLabels[$m] ?? $m }}
                            </span>
                            @php $gt = $group->group_type ?? 'descenso'; @endphp
                            @if($gt === 'mantenimiento')
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-50 text-amber-700">Mantenimiento</span>
                            @elseif($gt === 'mantenimiento_pleno')
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-purple-50 text-purple-700">Mant. Pleno</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-sky-50 text-sky-700">Descenso</span>
                            @endif
                            @if($group->started_at && !$group->auto_sessions)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    {{ $group->started_at->format('d/m · H:i') }}@if($group->ended_at) → {{ $group->started_at->isSameDay($group->ended_at) ? $group->ended_at->format('H:i') : $group->ended_at->format('d/m · H:i') }}@endif
                                </span>
                            @elseif($group->status === 'pending' && !$group->auto_sessions && ($group->meetingDaysDisplay || $group->meeting_time))
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $group->meetingDaysDisplay }}{{ $group->meetingDaysDisplay && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time_formatted ? $group->meeting_time_formatted . ' hs' : '' }}
                                </span>
                            @endif
                            @if($group->auto_sessions)
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 border border-purple-100 font-medium">
                                    <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    {{ $group->recurrenceLabel }}
                                </span>
                                @if($group->nextSessionAt)
                                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 font-medium">
                                        {{ $group->status === 'pending' ? 'Inicio programado' : 'Próxima sesión' }}: {{ $group->nextSessionAt->translatedFormat('D d/m · H:i') }}
                                    </span>
                                @endif
                            @endif
                        </div>

                        @if($group->description)
                            <p class="text-sm text-gray-500 mb-1">{{ $group->description }}</p>
                        @endif

                        @if($group->meetingDaysDisplay || $group->meeting_time)
                            <p class="text-xs text-teal-600 font-medium mb-2">
                                <svg class="inline w-3 h-3 mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ $group->meetingDaysDisplay }}{{ $group->meetingDaysDisplay && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? $group->meeting_time_formatted . ' hs' : '' }}
                            </p>
                        @endif

                        <div class="flex gap-3 text-xs text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $group->coordinators->isNotEmpty() ? $group->coordinators->pluck('name')->join(', ') : 'Sin coordinador' }}
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
                        <a href="{{ route('admin.groups.edit', $group) }}"
                            class="text-sm font-medium px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                            Editar
                        </a>
                        <a href="{{ route('admin.groups.show', $group) }}"
                            class="text-sm font-medium px-4 py-2 rounded-xl bg-teal-50 text-teal-700 hover:bg-teal-100 transition">
                            Gestionar
                        </a>
                        <form action="{{ route('admin.groups.destroy', $group) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar el grupo «{{ addslashes($group->name) }}»?\n\nSi tiene asistencias o pesos registrados el sistema no lo permitirá. En ese caso usá Finalizar.')">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                @if($status === 'today')
                    <p class="font-medium text-gray-500">No hay grupos programados para hoy</p>
                    <p class="text-sm mt-1 text-gray-400">Los grupos con sesión hoy aparecerán aquí</p>
                @elseif($status === 'active')
                    <p class="font-medium text-gray-500">No hay grupos activos</p>
                    <a href="{{ route('admin.groups.create') }}" class="mt-2 inline-block text-teal-600 hover:underline text-sm">Crear grupo</a>
                @elseif($status === 'closed')
                    <p class="font-medium text-gray-500">No hay grupos finalizados</p>
                @else
                    <p class="font-medium text-gray-500">No hay grupos creados</p>
                    <a href="{{ route('admin.groups.create') }}" class="mt-2 inline-block text-teal-600 hover:underline text-sm">Crear el primer grupo</a>
                @endif
            </div>
        @endforelse
    </div>

    <div class="flex items-center justify-between text-xs text-gray-400">
        <span>{{ $groups->total() }} grupo(s) · página {{ $groups->currentPage() }} de {{ $groups->lastPage() }}</span>
        @if($groups->hasPages())
            <div>{{ $groups->links() }}</div>
        @endif
    </div>

</div>
@endsection
