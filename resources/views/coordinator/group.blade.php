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
                    {{-- Misma lógica que el listado del dashboard (no solo $group->status: los recurrentes fuera de horario son "pending" pero el programa sigue vigente) --}}
                    @if($group->isProgramClosed())
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-500">Finalizado</span>
                    @elseif($group->status === 'active')
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full font-medium bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>En sesión
                        </span>
                    @elseif($group->isProgramVigente())
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-emerald-50 text-emerald-800 border border-emerald-100">Programa vigente</span>
                    @elseif($group->status === 'pending' && ! $group->auto_sessions)
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-yellow-100 text-yellow-700">Sin iniciar</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-yellow-100 text-yellow-700">Sin iniciar</span>
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
                            @if($group->nextSessionAt && ! $group->isProgramClosed())
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 font-medium">
                                    {{ $group->isProgramVigente() ? 'Próxima sesión' : 'Inicio programado' }}: {{ $group->nextSessionAt->translatedFormat('D d/m · H:i') }}
                                </span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @if($group->isProgramClosed())
            <span class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-400 shrink-0">Finalizado</span>
        @elseif($group->isProgramVigente() || ($group->isProgramPending() && ! $group->auto_sessions))
            {{-- Iniciar / Finalizar grupo al final de la página --}}
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
            <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $group->attendances()->distinct('user_id')->count('user_id') }}</p>
            <p class="text-xs text-gray-500 mt-1">Pacientes</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-green-600">{{ $avgWeight ? number_format($avgWeight, 1) : '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Peso prom.</p>
        </div>
    </div>

    @include('partials.group-historial')

    {{-- Asistencia con pesos --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center gap-2 flex-wrap">
            <div class="flex items-center gap-2 flex-wrap min-w-0">
                <h2 class="font-semibold text-gray-800">Asistentes</h2>
                <span id="live-session-badge" class="text-xs font-semibold text-teal-700 tabular-nums shrink-0">@if($todaySessionRecord)Sesión n.º {{ $todaySessionRecord->sequence_number }}@else<span class="text-gray-400 font-normal">—</span>@endif</span>
                @php $endedAtCheck = $group->getRawOriginal('ended_at'); $sesEndedNow = $endedAtCheck && \Carbon\Carbon::parse($endedAtCheck)->timezone('America/Argentina/Buenos_Aires')->isToday(); @endphp
                @if($sesEndedNow)
                    <span class="inline-flex items-center gap-1 text-xs text-gray-400 font-normal">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                        Sesión cerrada
                    </span>
                @endif
            </div>
            <span id="last-update" class="text-xs text-gray-400"></span>
        </div>

        <div id="live-list" class="divide-y divide-gray-50 min-h-[60px]">
            <p class="px-5 py-6 text-center text-gray-400 text-sm">Cargando...</p>
        </div>

        @if(false) {{-- static fallback no longer needed, JS handles all states --}}
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

    {{-- Pacientes del grupo --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Pacientes del grupo (<span id="patients-count">{{ $group->patients->count() }}</span>)</h2>
        </div>
        <div id="patients-list" class="divide-y divide-gray-50">
            @forelse($group->patients as $patient)
                <div class="px-5 py-3 flex items-center gap-3">
                    <x-avatar :user="$patient" size="sm" />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $patient->name }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            Alta: {{ \Carbon\Carbon::parse($patient->pivot->joined_at)->format('d/m/Y H:i') }}
                            · {{ $patient->pivot->join_source === 'qr' ? 'QR' : 'Manual' }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400 text-center">Sin asistencias registradas.</p>
            @endforelse
        </div>
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
                        <p class="text-2xl font-bold text-gray-700">{{ $group->attendances()->distinct('user_id')->count('user_id') }}</p>
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

    @if($group->isProgramVigente())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 sm:px-5 py-4">
                @php
                    $tz = 'America/Argentina/Buenos_Aires';
                    $endedAt = $group->getRawOriginal('ended_at');
                    $sessionEndedToday = $endedAt && \Carbon\Carbon::parse($endedAt)->timezone($tz)->isToday();
                @endphp
                @if($group->auto_sessions)
                    @if($sessionEndedToday)
                        {{-- Session was manually closed today — offer to reopen --}}
                        <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full min-h-[44px] text-sm font-semibold px-4 py-2.5 rounded-xl transition border border-teal-400 text-teal-600 hover:bg-teal-50">
                                Reabrir sesión de hoy
                            </button>
                        </form>
                        <p class="text-[11px] text-gray-400 text-center mt-2">La sesión de hoy está cerrada. El programa continúa la próxima clase.</p>
                    @elseif($group->status === 'active')
                        {{-- Session in progress — offer to close --}}
                        <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full min-h-[44px] text-sm font-semibold px-4 py-2.5 rounded-xl transition border border-orange-300 text-orange-600 hover:bg-orange-50">
                                Finalizar sesión de hoy
                            </button>
                        </form>
                        <p class="text-[11px] text-gray-400 text-center mt-2">Solo cierra la sesión de hoy. El programa recurrente no se ve afectado.</p>
                    @else
                        {{-- Session not started yet — offer to start early --}}
                        <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full min-h-[44px] text-sm font-semibold px-4 py-2.5 rounded-xl transition border border-teal-400 text-teal-600 hover:bg-teal-50">
                                Iniciar sesión de hoy
                            </button>
                        </form>
                        <p class="text-[11px] text-gray-400 text-center mt-2">Abre la sesión manualmente. El cron la cerrará al finalizar el horario programado.</p>
                    @endif
                @else
                    <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST"
                          onsubmit="return confirm('¿Finalizar el grupo permanentemente? Esta acción no se puede deshacer.')">
                        @csrf
                        <button type="submit"
                            class="w-full min-h-[44px] text-sm font-semibold px-4 py-2.5 rounded-xl transition border border-red-300 text-red-600 hover:bg-red-50">
                            Finalizar grupo
                        </button>
                    </form>
                    <p class="text-[11px] text-gray-400 text-center mt-2">Cierra el grupo de forma permanente.</p>
                @endif
            </div>
        </div>
    @elseif($group->isProgramPending() && ! $group->auto_sessions)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 sm:px-5 py-4">
                <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full min-h-[44px] text-sm font-semibold px-4 py-2.5 rounded-xl transition border border-teal-400 text-teal-600 hover:bg-teal-50">
                        Iniciar grupo
                    </button>
                </form>
            </div>
        </div>
    @endif

</div>

<script>
const liveUrl      = '{{ route('coordinator.groups.live', $group) }}';
const checkoutBase = '{{ url('coordinator/grupos/' . $group->id . '/asistencias') }}';
const csrfToken    = '{{ csrf_token() }}';
const groupClosed  = {{ $group->isProgramClosed() ? 'true' : 'false' }};
const listEl       = document.getElementById('live-list');
const countEl      = document.getElementById('stat-count');
const sessionBadge = document.getElementById('live-session-badge');
const updateEl     = document.getElementById('last-update');
const patientsList  = document.getElementById('patients-list');
const patientsCount = document.getElementById('patients-count');

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

function avatarHtml(a) {
    if (a.avatar_url) {
        return `<img src="${a.avatar_url}" alt="${a.name}"
            class="w-8 h-8 rounded-full object-cover shrink-0"
            onerror="this.style.display='none';this.nextElementSibling.style.cssText='display:flex;background-color:${a.color}'">
            <div class="w-8 h-8 rounded-full items-center justify-center shrink-0 font-semibold text-white text-xs" style="display:none">${a.initials}</div>`;
    }
    return `<div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 font-semibold text-white text-xs" style="background-color:${a.color}">${a.initials}</div>`;
}

async function checkout(attendanceId, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const res = await fetch(`${checkoutBase}/${attendanceId}/checkout`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        btn.closest('.checkout-cell').innerHTML = `<span class="text-gray-500 text-xs">${data.left_at}</span>`;
        fetchAttendances();
    } catch(e) { btn.disabled = false; btn.textContent = 'Marcar salida'; }
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
    const isPresent = !a.left_at;
    const checkoutBtn = `<button onclick="checkout(${a.attendance_id}, this)"
        class="text-xs text-teal-600 border border-teal-200 rounded px-2 py-0.5 hover:bg-teal-50 transition">
        Marcar salida
       </button>`;
    const statusBadge = isPresent
        ? `<span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-100 rounded-full px-2 py-0.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>En sesión</span>`
        : `<span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5">Salió ${a.left_at}</span>`;

    return `
    <div class="px-4 py-3 border-b border-gray-50 last:border-0 ${isPresent ? '' : 'opacity-60'}">
        <div class="flex items-center gap-2 mb-2">
            ${avatarHtml(a)}
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="font-medium text-gray-800 text-sm leading-tight">${a.name}</p>
                    ${statusBadge}
                </div>
                <p class="text-xs text-gray-400 mt-0.5">Entrada: ${a.attended_at}${a.session_number != null ? ` · Sesión n.º ${a.session_number}` : ''}${isPresent ? ' &nbsp;·&nbsp; <span class="checkout-cell inline">' + checkoutBtn + '</span>' : ''}</p>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2 text-xs">
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-semibold ${rw ? 'text-teal-600' : 'text-gray-300'}">${rw ? rw + ' kg' : '—'}</p>
                <p class="text-gray-400 mt-0.5">Peso</p>
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
    </div>`;
}


function renderPatients(patients) {
    patientsCount.textContent = patients.length;
    if (patients.length === 0) {
        patientsList.innerHTML = '<p class="px-5 py-4 text-sm text-gray-400 text-center">Sin asistencias registradas.</p>';
        return;
    }
    patientsList.innerHTML = patients.map(p => `
        <div class="px-5 py-3 flex items-center gap-3">
            ${avatarHtml(p)}
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-800">${p.name}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">
                    Alta: ${p.joined_at ?? '—'} · ${p.join_source === 'qr' ? 'QR' : 'Manual'}
                </p>
            </div>
        </div>`).join('');
}

async function fetchAttendances() {
    let data;
    try {
        const res = await fetch(liveUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        data = await res.json();
    } catch (e) { return; }

    // Update patients section (independent of attendance rendering)
    if (data.patients !== undefined) renderPatients(data.patients);

    countEl.textContent = data.count;
    if (sessionBadge) {
        sessionBadge.textContent = data.session_number != null ? 'Sesión n.º ' + data.session_number : '—';
    }

    try {
        if (data.attendances.length === 0) {
            listEl.innerHTML = '<p class="px-5 py-6 text-center text-gray-400 text-sm">Esperando pacientes...</p>';
        } else {
            listEl.innerHTML = data.attendances.map(renderRow).join('');
        }
    } catch (e) {}

    const now = new Date();
    updateEl.textContent = 'Act. ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0');
}

@php
    $tz2 = 'America/Argentina/Buenos_Aires';
    $endedAt2 = $group->getRawOriginal('ended_at');
    $sessionEndedTodayJs = $endedAt2 && \Carbon\Carbon::parse($endedAt2)->timezone($tz2)->isToday();
@endphp
const sessionEndedToday = {{ $sessionEndedTodayJs ? 'true' : 'false' }};

fetchAttendances();
if (!groupClosed && !sessionEndedToday) {
    setInterval(fetchAttendances, 4000);
}
</script>

@endsection
