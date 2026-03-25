@extends('layouts.app')
@section('title', 'Mis Grupos')

@section('content')
<div class="space-y-5">

    <div>
        <h1 class="text-xl font-bold text-gray-800">Mis Grupos</h1>
        <p class="text-gray-500 text-sm mt-0.5">Bienvenido, {{ auth()->user()->name }}</p>
    </div>

    {{-- Search & filter --}}
    <form method="GET" action="{{ route('coordinator.dashboard') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar grupo..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="grid grid-cols-2 sm:flex gap-2">
            @foreach([''=>'Todos', 'active'=>'En curso', 'pending'=>'Sin iniciar', 'closed'=>'Finalizados'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                    class="py-2 rounded-lg text-sm font-medium border transition text-center
                        {{ request('status', '') === $val
                            ? 'bg-teal-600 text-white border-teal-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-teal-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    @forelse($groups as $group)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Card header --}}
            <div class="px-4 pt-4 pb-3 border-b border-gray-100 space-y-2">

                {{-- Nombre + link Ver detalle --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="font-semibold text-gray-800 leading-snug">{{ $group->name }}</h2>
                        @if($group->meetingDaysDisplay || $group->meeting_time)
                            <p class="text-xs text-teal-600 font-medium mt-0.5">
                                {{ $group->meetingDaysDisplay }}{{ $group->meetingDaysDisplay && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? $group->meeting_time_formatted . ' hs' : '' }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Badges --}}
                <div class="flex flex-wrap gap-1.5">
                    @if($group->isProgramClosed())
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-500">Finalizado</span>
                    @elseif($group->isProgramVigente())
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>En curso
                        </span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-yellow-100 text-yellow-700">Sin iniciar</span>
                    @endif

                    @php $m = $group->modality ?? 'presencial'; @endphp
                    @if($m === 'virtual')
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-violet-50 text-violet-700">Virtual</span>
                    @elseif($m === 'hibrido')
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-orange-50 text-orange-700">Híbrido</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-blue-50 text-blue-700">Presencial</span>
                    @endif

                    @php $gt = $group->group_type ?? 'descenso'; @endphp
                    @if($gt === 'mantenimiento')
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-50 text-amber-700">Mantenimiento</span>
                    @elseif($gt === 'mantenimiento_pleno')
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-purple-50 text-purple-700">Mant. Pleno</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-sky-50 text-sky-700">Descenso</span>
                    @endif
                </div>

                {{-- Botón Iniciar / Finalizar --}}
                @if($group->isProgramVigente())
                    <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST"
                          onsubmit="return confirm('¿Finalizar {{ $group->auto_sessions ? 'el programa' : 'el grupo' }}? Esta acción no se puede deshacer.')">
                        @csrf
                        <button type="submit"
                            class="w-full text-sm font-semibold px-4 py-2 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                            Finalizar {{ $group->auto_sessions ? 'programa' : 'grupo' }}
                        </button>
                    </form>
                @elseif($group->isProgramPending() && ! $group->auto_sessions)
                    <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full text-sm font-semibold px-4 py-2 rounded-lg transition border border-teal-400 text-teal-600 hover:bg-teal-50">
                            Iniciar grupo
                        </button>
                    </form>
                @endif
            </div>

            {{-- Recurrencia / fechas --}}
            @if($group->started_at || $group->auto_sessions || ($group->status === 'pending' && !$group->auto_sessions && ($group->meeting_day || $group->meeting_time)))
            <div class="px-4 py-2 flex flex-wrap gap-1.5 border-b border-gray-50 bg-gray-50/50">
                @if($group->started_at)
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $group->started_at->format('d/m/Y · H:i') }}@if($group->ended_at) → {{ $group->started_at->isSameDay($group->ended_at) ? $group->ended_at->format('H:i') : $group->ended_at->format('d/m/Y · H:i') }}@endif
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
                            {{ $group->status === 'pending' ? 'Inicio programado' : 'Próxima' }}: {{ $group->nextSessionAt->translatedFormat('D d/m · H:i') }}
                        </span>
                    @endif
                @endif
            </div>
            @endif

            {{-- Stats --}}
            <div class="px-4 py-3 grid grid-cols-3 gap-2 text-center border-b border-gray-50">
                <div>
                    <p class="text-xl font-bold text-teal-600">{{ $group->patients_count ?? $group->patients->count() }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pacientes</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-blue-600">{{ $group->attendances_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Visitas</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-green-600">{{ $group->weight_records_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pesos</p>
                </div>
            </div>

            {{-- QR + link --}}
            <div class="px-4 py-4 space-y-3">
                <div class="flex justify-center">
                    <div class="text-center">
                        <div class="inline-block p-2 border border-gray-100 rounded-lg shadow-sm">
                            {!! $group->qrSvg !!}
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">QR del grupo</p>
                    </div>
                </div>
                @php $joinUrl = route('group.join', $group->qr_token); @endphp
                <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                    <span class="text-xs text-gray-500 truncate flex-1 select-all">{{ $joinUrl }}</span>
                    <button type="button"
                        onclick="navigator.clipboard.writeText('{{ $joinUrl }}').then(() => { this.textContent = '✓'; setTimeout(() => this.textContent = 'Copiar', 1500) })"
                        class="shrink-0 text-xs font-medium text-teal-600 hover:text-teal-800 transition">
                        Copiar
                    </button>
                </div>
            </div>

        </div>

        <a href="{{ route('coordinator.groups.show', $group) }}"
            class="block w-full text-center text-sm font-semibold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 rounded-xl py-3 transition">
            Ver detalle del grupo →
        </a>

    @empty
        <div class="bg-white rounded-xl p-12 text-center text-gray-400">
            <p>No tenés grupos asignados aún.</p>
        </div>
    @endforelse

    @if($groups->hasPages())
        <div>{{ $groups->links() }}</div>
    @endif

</div>
@endsection
