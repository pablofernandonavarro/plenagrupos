@extends('layouts.app')
@section('title', $group->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <a href="{{ route('coordinator.dashboard') }}" class="mt-1 text-gray-400 hover:text-gray-600 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $group->name }}</h1>
                    @if($group->status === 'active')
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full font-medium bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>En curso
                        </span>
                    @elseif($group->status === 'pending')
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-yellow-100 text-yellow-700">Sin iniciar</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-500">Finalizado</span>
                    @endif
                    @if($group->active)
                        <span class="flex items-center gap-1 text-xs text-green-600">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            En vivo
                        </span>
                    @endif
                </div>
                @if($group->meetingDaysDisplay || $group->meeting_time)
                    <p class="text-sm text-teal-600 font-medium mt-0.5">
                        {{ $group->meetingDaysDisplay }}{{ $group->meetingDaysDisplay && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? $group->meeting_time_formatted . ' hs' : '' }}
                    </p>
                @endif
                @if($group->started_at || $group->auto_sessions)
                    <div class="flex flex-wrap gap-2 mt-2">
                        @if($group->started_at)
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                                <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $group->started_at->format('d/m/Y · H:i') }}@if($group->ended_at) → {{ $group->started_at->isSameDay($group->ended_at) ? $group->ended_at->format('H:i') : $group->ended_at->format('d/m/Y · H:i') }}@endif
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
                @endif
            </div>
        </div>
        @if($group->isProgramClosed())
            <span class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-400 shrink-0">Finalizado</span>
        @elseif($group->isProgramVigente())
            <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST" class="shrink-0"
                  onsubmit="return confirm('¿Finalizar {{ $group->auto_sessions ? 'el programa' : 'el grupo' }}? Esta acción no se puede deshacer.')">
                @csrf
                <button type="submit"
                    class="text-sm font-semibold px-4 py-2 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                    Finalizar
                </button>
            </form>
        @elseif($group->isProgramPending() && ! $group->auto_sessions)
            <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST" class="shrink-0">
                @csrf
                <button type="submit"
                    class="text-sm font-semibold px-4 py-2 rounded-lg transition border border-teal-400 text-teal-600 hover:bg-teal-50">
                    Iniciar grupo
                </button>
            </form>
        @else
            <span class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-400 shrink-0">Finalizado</span>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p id="stat-count" class="text-2xl sm:text-3xl font-bold text-teal-600">{{ $todayVisits }}</p>
            <p class="text-xs text-gray-500 mt-1">Presentes hoy</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $group->patients->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Inscriptos</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ $avgWeight ? number_format($avgWeight, 1) : '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Peso prom.</p>
        </div>
    </div>

    {{-- Asistencia con pesos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Asistentes</h2>
            @if($group->active)
                <span id="last-update" class="text-xs text-gray-400"></span>
            @endif
        </div>

        @if($group->active)
            <div id="live-list" class="divide-y divide-gray-50 min-h-[60px]">
                <p class="px-5 py-6 text-center text-gray-400 text-sm">Esperando pacientes...</p>
            </div>
        @else
            {{-- Mobile: card list. Desktop: table --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-5 py-3 text-left">Paciente</th>
                            <th class="px-5 py-3 text-right">Peso registrado</th>
                            <th class="px-5 py-3 text-right">Rango</th>
                            <th class="px-5 py-3 text-right">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($attendances as $att)
                            @php
                                $rw    = $att->weightRecord?->weight;
                                $piso  = $att->user->peso_piso;
                                $techo = $att->user->peso_techo;
                                $status = null;
                                $diffVal = null;
                                if ($rw && $techo && $rw > $techo) {
                                    $status = 'above';
                                    $diffVal = round($rw - $techo, 2);
                                } elseif ($rw && $piso && $rw < $piso) {
                                    $status = 'below';
                                    $diffVal = round($rw - $piso, 2);
                                } elseif ($rw && ($piso || $techo)) {
                                    $status = 'ok';
                                }
                            @endphp
                            <tr>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <x-avatar :user="$att->user" size="sm" />
                                        <span class="font-medium text-gray-800">{{ $att->user->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold {{ $rw ? 'text-teal-600' : 'text-gray-300' }}">
                                    {{ $rw ? $rw . ' kg' : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-500 text-xs">
                                    @if($piso || $techo)
                                        {{ $piso ? $piso . ' kg' : '?' }} – {{ $techo ? $techo . ' kg' : '?' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-semibold">
                                    @if($status === 'above')
                                        <span class="text-red-500">↑ +{{ $diffVal }} kg</span>
                                    @elseif($status === 'below')
                                        <span class="text-blue-500">↓ {{ $diffVal }} kg</span>
                                    @elseif($status === 'ok')
                                        <span class="text-green-600">✓ en rango</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-400">Sin visitas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile card list --}}
            <div class="sm:hidden divide-y divide-gray-50">
                @forelse($attendances as $att)
                    @php
                        $rw    = $att->weightRecord?->weight;
                        $piso  = $att->user->peso_piso;
                        $techo = $att->user->peso_techo;
                        $status = null;
                        $diffVal = null;
                        if ($rw && $techo && $rw > $techo) {
                            $status = 'above';
                            $diffVal = round($rw - $techo, 2);
                        } elseif ($rw && $piso && $rw < $piso) {
                            $status = 'below';
                            $diffVal = round($rw - $piso, 2);
                        } elseif ($rw && ($piso || $techo)) {
                            $status = 'ok';
                        }
                    @endphp
                    <div class="px-5 py-3">
                        <div class="flex items-center gap-2 mb-2">
                            <x-avatar :user="$att->user" size="sm" />
                            <p class="font-medium text-gray-800 text-sm">{{ $att->user->name }}</p>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                <p class="font-semibold {{ $rw ? 'text-teal-600' : 'text-gray-300' }}">{{ $rw ? $rw . ' kg' : '—' }}</p>
                                <p class="text-gray-400 mt-0.5">Registrado</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                <p class="font-semibold text-gray-600 text-xs leading-tight">
                                    @if($piso || $techo){{ $piso ?? '?' }} – {{ $techo ?? '?' }}@else—@endif
                                </p>
                                <p class="text-gray-400 mt-0.5">Rango</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                @if($status === 'above')
                                    <p class="font-semibold text-red-500">↑ +{{ $diffVal }}</p>
                                @elseif($status === 'below')
                                    <p class="font-semibold text-blue-500">↓ {{ $diffVal }}</p>
                                @elseif($status === 'ok')
                                    <p class="font-semibold text-green-600">✓</p>
                                @else
                                    <p class="font-semibold text-gray-300">—</p>
                                @endif
                                <p class="text-gray-400 mt-0.5">Dif.</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-gray-400 text-sm">Sin visitas registradas.</p>
                @endforelse
            </div>

            <div class="px-5 py-3 border-t border-gray-50">{{ $attendances->links() }}</div>
        @endif
    </div>

    {{-- Estadísticas del grupo --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Estadísticas del grupo</h2>
            <p class="text-xs text-gray-400 mt-0.5">Resumen de hoy y totales históricos.</p>
        </div>
        <div class="p-5 space-y-5">

            {{-- Hoy: distribución por rango --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Hoy — distribución de pesos</p>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-center">
                        <p class="text-2xl font-bold text-green-600">{{ $stats['inRange'] }}</p>
                        <p class="text-xs text-green-700 mt-1">✓ En rango</p>
                    </div>
                    <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-center">
                        <p class="text-2xl font-bold text-red-500">{{ $stats['above'] }}</p>
                        <p class="text-xs text-red-600 mt-1">↑ Por encima</p>
                    </div>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-center">
                        <p class="text-2xl font-bold text-blue-500">{{ $stats['below'] }}</p>
                        <p class="text-xs text-blue-600 mt-1">↓ Por debajo</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 text-center">
                        <p class="text-2xl font-bold text-gray-400">{{ $stats['noWeight'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">Sin peso hoy</p>
                    </div>
                </div>
            </div>

            {{-- Totales históricos --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Histórico</p>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-gray-100 p-4 text-center">
                        <p class="text-2xl font-bold text-gray-700">{{ $group->patients->count() }}</p>
                        <p class="text-xs text-gray-500 mt-1">Pacientes</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 p-4 text-center">
                        <p class="text-2xl font-bold text-teal-600">{{ $totalVisits }}</p>
                        <p class="text-xs text-gray-500 mt-1">Visitas totales</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 p-4 text-center">
                        <p class="text-2xl font-bold text-teal-600">{{ $avgWeight ? number_format($avgWeight, 1) : '—' }}</p>
                        <p class="text-xs text-gray-500 mt-1">Peso prom.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

@if($group->active)
<script>
const liveUrl  = '{{ route('coordinator.groups.live', $group) }}';
const listEl   = document.getElementById('live-list');
const countEl  = document.getElementById('stat-count');
const updateEl = document.getElementById('last-update');

const patientRanges = {
    @foreach($group->patients as $p)
        {{ $p->id }}: { piso: {{ $p->peso_piso ?? 'null' }}, techo: {{ $p->peso_techo ?? 'null' }} },
    @endforeach
};

function calcStatus(rw, piso, techo) {
    if (!rw) return { text: '—', color: 'text-gray-300', icon: '' };
    if (techo !== null && rw > techo) {
        const d = Math.round((rw - techo) * 100) / 100;
        return { text: '+' + d + ' kg', color: 'text-red-500', icon: '↑' };
    }
    if (piso !== null && rw < piso) {
        const d = Math.round((rw - piso) * 100) / 100;
        return { text: d + ' kg', color: 'text-blue-500', icon: '↓' };
    }
    if (piso !== null || techo !== null) {
        return { text: 'en rango', color: 'text-green-600', icon: '✓' };
    }
    return { text: '—', color: 'text-gray-300', icon: '' };
}

function renderRow(a) {
    const range = patientRanges[a.id] ?? { piso: null, techo: null };
    const rw    = a.weight;
    const piso  = range.piso;
    const techo = range.techo;
    const s     = calcStatus(rw, piso, techo);
    const rangeText = (piso !== null || techo !== null)
        ? (piso ?? '?') + ' – ' + (techo ?? '?') + ' kg'
        : '—';

    return `
    <div class="px-5 py-3 border-b border-gray-50 last:border-0">
        <p class="font-medium text-gray-800 text-sm mb-2">${a.name}</p>
        <div class="grid grid-cols-3 gap-2 text-xs sm:hidden">
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-semibold ${rw ? 'text-teal-600' : 'text-gray-300'}">${rw ? rw + ' kg' : '—'}</p>
                <p class="text-gray-400 mt-0.5">Registrado</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-semibold text-gray-600 text-xs leading-tight">${rangeText}</p>
                <p class="text-gray-400 mt-0.5">Rango</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-semibold ${s.color}">${s.icon} ${s.text}</p>
                <p class="text-gray-400 mt-0.5">Dif.</p>
            </div>
        </div>
        <div class="hidden sm:grid grid-cols-4 gap-2 text-sm items-center">
            <span></span>
            <span class="text-right font-semibold ${rw ? 'text-teal-600' : 'text-gray-300'}">${rw ? rw + ' kg' : '—'}</span>
            <span class="text-right text-gray-500 text-xs">${rangeText}</span>
            <span class="text-right font-semibold ${s.color}">${s.icon} ${s.text}</span>
        </div>
    </div>`;
}

function renderHeader() {
    return `<div class="hidden sm:grid grid-cols-4 gap-2 px-5 py-2 text-xs text-gray-400 uppercase tracking-wide bg-gray-50">
        <span>Paciente</span>
        <span class="text-right">Peso registrado</span>
        <span class="text-right">Rango</span>
        <span class="text-right">Diferencia</span>
    </div>`;
}

async function fetchAttendances() {
    try {
        const res  = await fetch(liveUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        countEl.textContent = data.count;

        if (data.attendances.length === 0) {
            listEl.innerHTML = '<p class="px-5 py-6 text-center text-gray-400 text-sm">Esperando pacientes...</p>';
        } else {
            listEl.innerHTML = renderHeader() + data.attendances.map(renderRow).join('');
        }

        const now = new Date();
        updateEl.textContent = 'Act. ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0');
    } catch (e) {}
}

fetchAttendances();
setInterval(fetchAttendances, 4000);
</script>
@endif

@endsection
