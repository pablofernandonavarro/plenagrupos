@extends('layouts.app')
@section('title', 'Mis Grupos')

@php
    $statusLabels = [
        '' => 'Todos',
        'live' => 'En curso',
        'active' => 'Vigentes',
        'pending' => 'Sin iniciar',
        'closed' => 'Finalizados',
    ];
    $curStatus = request('status', '');
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Mis grupos</h1>
            <p class="text-gray-500 text-sm mt-0.5">Hola, {{ auth()->user()->name }}</p>
        </div>
        @if(($assignedGroupsCount ?? 0) > 0)
            <p class="text-xs text-gray-500 sm:text-right">
                <span class="font-medium text-gray-700">{{ $assignedGroupsCount }}</span> grupo(s) asignado(s)
            </p>
        @endif
    </div>

    {{-- Búsqueda y filtros --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5 space-y-4" aria-labelledby="coord-filter-heading">
        <h2 id="coord-filter-heading" class="sr-only">Buscar y filtrar grupos</h2>

        <form method="GET" action="{{ route('coordinator.dashboard') }}" id="coord-dashboard-filters" class="space-y-4">
            <input type="hidden" name="status" id="coordinator-dashboard-status" value="{{ $curStatus }}">

            <div class="space-y-1.5">
                <label for="coord-search-input" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Buscar por nombre</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input id="coord-search-input" type="search" name="search" value="{{ request('search') }}"
                        placeholder="Escribí parte del nombre del grupo…"
                        autocomplete="off"
                        enterkeyhint="search"
                        class="w-full pl-9 pr-12 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-400 outline-none transition">
                    <button type="submit" title="Buscar" aria-label="Buscar grupos"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-lg text-gray-500 hover:text-teal-600 hover:bg-teal-50 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide block">Estado del programa</span>
                <p class="text-xs text-gray-500 leading-snug">
                    <strong class="text-gray-600">En curso</strong> = grupo con sesión en vivo ahora. <strong class="text-gray-600">Vigentes</strong> = programas no finalizados (como en admin).
                </p>
                <div class="flex flex-wrap gap-1.5 p-1 bg-gray-100 rounded-xl border border-gray-200/80" role="tablist" aria-label="Filtrar por estado del programa">
                    @foreach($statusLabels as $val => $label)
                        {{-- onclick con comillas simples en el atributo: si usáramos " y Js::from, las comillas JSON cerrarían el atributo y el status no se envía --}}
                        <button type="button"
                            id="coord-filter-{{ $val === '' ? 'all' : $val }}"
                            onclick='document.getElementById("coordinator-dashboard-status").value=@json($val); this.closest("form").submit();'
                            role="tab"
                            aria-selected="{{ $curStatus === $val ? 'true' : 'false' }}"
                            aria-pressed="{{ $curStatus === $val ? 'true' : 'false' }}"
                            class="flex-1 min-w-[5.5rem] sm:flex-none sm:min-w-0 px-3 py-2 rounded-lg text-sm font-medium border transition text-center
                                {{ $curStatus === $val
                                    ? 'bg-white text-teal-700 border-teal-200 shadow-sm'
                                    : 'border-transparent text-gray-600 hover:bg-white/70 hover:text-gray-800' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if(request()->filled('search') || $curStatus !== '')
                <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-gray-100">
                    <span class="text-xs text-gray-500">Filtros activos:</span>
                    @if($curStatus !== '')
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-md bg-teal-50 text-teal-800 border border-teal-100">
                            {{ $statusLabels[$curStatus] ?? $curStatus }}
                        </span>
                    @endif
                    @if(request()->filled('search'))
                        <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-md bg-gray-100 text-gray-700 border border-gray-200 max-w-full truncate" title="{{ request('search') }}">
                            «{{ request('search') }}»
                        </span>
                    @endif
                    <a href="{{ route('coordinator.dashboard') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-800 ml-auto">Limpiar todo</a>
                </div>
            @endif
        </form>
    </section>

    {{-- Lista --}}
    @forelse($groups as $group)
        <article class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            {{-- Cabecera --}}
            <div class="px-4 sm:px-5 pt-4 pb-3 border-b border-gray-100 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold text-gray-900 text-base leading-snug">{{ $group->name }}</h2>
                        @if($group->meetingDaysDisplay || $group->meeting_time)
                            <p class="text-xs text-teal-600 font-medium mt-1">
                                {{ $group->meetingDaysDisplay }}{{ $group->meetingDaysDisplay && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? $group->meeting_time_formatted . ' hs' : '' }}
                            </p>
                        @endif
                    </div>
                    <a href="{{ route('coordinator.groups.show', $group) }}"
                        class="shrink-0 text-xs font-semibold text-teal-600 hover:text-teal-800 whitespace-nowrap py-1">
                        Ver detalle
                    </a>
                </div>

                {{-- Badges --}}
                <div class="flex flex-wrap gap-1.5">
                    @if($group->isProgramClosed())
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-500">Finalizado</span>
                    @elseif($group->status === 'active')
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" aria-hidden="true"></span>En sesión
                        </span>
                    @elseif($group->isProgramVigente())
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">Programa vigente</span>
                    @elseif($group->status === 'pending' && ! $group->auto_sessions)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-yellow-100 text-yellow-700">Sin iniciar</span>
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

                {{-- Acciones primarias --}}
                @if($group->isProgramVigente())
                    <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST"
                          onsubmit="return confirm('¿Finalizar {{ $group->auto_sessions ? 'el programa' : 'el grupo' }}? Esta acción no se puede deshacer.')">
                        @csrf
                        <button type="submit"
                            class="w-full sm:w-auto min-h-[44px] text-sm font-semibold px-4 py-2.5 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                            Finalizar {{ $group->auto_sessions ? 'programa' : 'grupo' }}
                        </button>
                    </form>
                @elseif($group->isProgramPending() && ! $group->auto_sessions)
                    <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full sm:w-auto min-h-[44px] text-sm font-semibold px-4 py-2.5 rounded-lg transition border border-teal-400 text-teal-600 hover:bg-teal-50">
                            Iniciar grupo
                        </button>
                    </form>
                @endif
            </div>

            {{-- Recurrencia / fechas --}}
            @if($group->started_at || $group->auto_sessions || ($group->isProgramPending() && ! $group->auto_sessions && ($group->meetingDaysDisplay || $group->meeting_time)))
            <div class="px-4 sm:px-5 py-2.5 flex flex-wrap gap-1.5 border-b border-gray-50 bg-gray-50/50">
                @if($group->started_at)
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $group->started_at->format('d/m/Y · H:i') }}@if($group->ended_at) → {{ $group->started_at->isSameDay($group->ended_at) ? $group->ended_at->format('H:i') : $group->ended_at->format('d/m/Y · H:i') }}@endif
                    </span>
                @elseif($group->isProgramPending() && ! $group->auto_sessions && ($group->meetingDaysDisplay || $group->meeting_time))
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $group->meetingDaysDisplay }}{{ $group->meetingDaysDisplay && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time_formatted ? $group->meeting_time_formatted . ' hs' : '' }}
                    </span>
                @endif
                @if($group->auto_sessions)
                    <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-600 border border-purple-100 font-medium">
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        {{ $group->recurrenceLabel }}
                    </span>
                    @if($group->nextSessionAt && ! $group->isProgramClosed())
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 font-medium">
                            {{ $group->isProgramVigente() ? 'Próxima sesión' : 'Inicio programado' }}: {{ $group->nextSessionAt->translatedFormat('D d/m · H:i') }}
                        </span>
                    @endif
                @endif
            </div>
            @endif

            {{-- Stats --}}
            <div class="px-4 sm:px-5 py-3 grid grid-cols-3 gap-2 text-center border-b border-gray-50">
                <div>
                    <p class="text-xl font-bold text-teal-600 tabular-nums">{{ $group->patients_count ?? $group->patients->count() }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pacientes</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-blue-600 tabular-nums">{{ $group->attendances_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Visitas</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-green-600 tabular-nums">{{ $group->weight_records_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pesos</p>
                </div>
            </div>

            {{-- QR + enlace pacientes --}}
            @php $joinUrl = route('group.join', $group->qr_token); @endphp
            <div class="px-4 sm:px-5 py-4 space-y-3">
                <p class="text-xs font-medium text-gray-600">Registro de asistencia (QR)</p>
                <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                    <div class="flex justify-center sm:justify-start shrink-0">
                        <div class="inline-block p-2 border border-gray-100 rounded-lg shadow-sm bg-white max-w-[200px]">
                            {!! $group->qrSvg !!}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0 space-y-2">
                        <p class="text-xs text-gray-500 leading-relaxed">Los pacientes escanean este código o abren el enlace para marcar asistencia.</p>
                        <div class="flex items-stretch gap-2 bg-gray-50 border border-gray-200 rounded-lg overflow-hidden">
                            <span class="text-xs text-gray-600 px-3 py-2.5 truncate flex-1 min-w-0 select-all" title="{{ $joinUrl }}">{{ $joinUrl }}</span>
                            <button type="button"
                                data-copy-url="{{ e($joinUrl) }}"
                                class="coord-copy-btn shrink-0 px-3 py-2 text-xs font-semibold text-white bg-teal-600 hover:bg-teal-700 transition border-l border-teal-500">
                                Copiar
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 sr-only" role="status" aria-live="polite" id="coord-copy-status-{{ $group->id }}"></p>
                    </div>
                </div>
            </div>

            {{-- Pie de tarjeta: CTA principal --}}
            <div class="px-4 sm:px-5 py-3 bg-teal-50/50 border-t border-teal-100/80">
                <a href="{{ route('coordinator.groups.show', $group) }}"
                    class="flex items-center justify-center gap-2 w-full min-h-[44px] text-sm font-semibold text-teal-800 bg-white hover:bg-teal-50/80 border border-teal-200 rounded-xl py-2.5 px-4 transition shadow-sm">
                    Abrir panel del grupo
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </article>

    @empty
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 sm:p-12 text-center">
            <div class="max-w-sm mx-auto space-y-3">
                <div class="w-12 h-12 mx-auto rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                @if(($assignedGroupsCount ?? 0) === 0)
                    <p class="text-gray-600 text-sm">Todavía no tenés grupos asignados.</p>
                    <p class="text-xs text-gray-400">Cuando un administrador te asigne grupos, aparecerán acá.</p>
                @elseif(($totalAfterSearch ?? 0) === 0 && ($search ?? '') !== '')
                    <p class="text-gray-600 text-sm">No encontramos grupos con ese nombre.</p>
                    <a href="{{ route('coordinator.dashboard', array_filter(request()->except('search'))) }}" class="inline-flex items-center justify-center text-sm font-semibold text-teal-600 hover:text-teal-800">Quitar búsqueda</a>
                @else
                    <p class="text-gray-600 text-sm">Ningún grupo coincide con el filtro actual.</p>
                    <a href="{{ route('coordinator.dashboard', array_filter(request()->except('status'))) }}" class="inline-flex items-center justify-center text-sm font-semibold text-teal-600 hover:text-teal-800">Ver todos los estados</a>
                @endif
            </div>
        </div>
    @endforelse

    @if($groups->hasPages())
        <nav class="flex justify-center pt-2" aria-label="Paginación de grupos">
            <div class="text-sm text-gray-600">{{ $groups->links() }}</div>
        </nav>
    @elseif($groups->total() > 0)
        <p class="text-center text-xs text-gray-400">
            Mostrando {{ $groups->total() }} {{ $groups->total() === 1 ? 'grupo' : 'grupos' }}
        </p>
    @endif

</div>

<script>
document.querySelectorAll('.coord-copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var url = this.getAttribute('data-copy-url');
        if (!url) return;
        var card = this.closest('article');
        var statusEl = card ? card.querySelector('[role="status"]') : null;
        navigator.clipboard.writeText(url).then(function() {
            if (statusEl) { statusEl.textContent = 'Enlace copiado'; }
            var prev = btn.textContent;
            btn.textContent = 'Listo';
            btn.classList.add('bg-teal-700');
            setTimeout(function() {
                btn.textContent = prev;
                btn.classList.remove('bg-teal-700');
                if (statusEl) statusEl.textContent = '';
            }, 2000);
        });
    });
});
</script>
@endsection
