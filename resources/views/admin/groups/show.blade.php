@extends('layouts.app')
@section('title', $group->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <a href="{{ route('admin.groups.index') }}" class="mt-1 text-gray-400 hover:text-gray-600 shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $group->name }}</h1>
                    @if($group->isProgramClosed())
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-500">Finalizado</span>
                    @elseif($group->isLiveSessionNow())
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
                <div class="flex items-center gap-3 mt-1 flex-wrap">
                    @if($group->description)
                        <p class="text-sm text-gray-500">{{ $group->description }}</p>
                    @endif
                    @if($group->meetingDaysDisplay || $group->meeting_time || $group->session_duration_minutes)
                        <span class="text-xs bg-teal-50 text-teal-700 border border-teal-200 px-2 py-0.5 rounded-full">
                            {{ $group->meetingDaysDisplay }}{{ $group->meetingDaysDisplay && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? $group->meeting_time_formatted . ' hs' : '' }}{{ ($group->meetingDaysDisplay || $group->meeting_time) && $group->session_duration_minutes ? ' · ' : '' }}{{ $group->session_duration_minutes ? $group->session_duration_minutes . ' min' : '' }}
                        </span>
                    @endif
                </div>
                @if(($group->started_at && !$group->auto_sessions) || $group->auto_sessions)
                    <div class="flex flex-wrap gap-2 mt-2">
                        @if($group->started_at && !$group->auto_sessions)
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
                            @if($group->nextSessionAt && ! $group->isProgramClosed() && ! $group->isLiveSessionNow())
                                <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 font-medium">
                                    {{ $group->isProgramVigente() ? 'Próxima sesión' : 'Inicio programado' }}: {{ $group->nextSessionAt->translatedFormat('D d/m/Y · H:i') }}
                                </span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>
        <div class="flex gap-2 shrink-0 flex-wrap">
            <a href="{{ route('admin.groups.edit', $group) }}"
                class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                Editar
            </a>
            @if($group->auto_sessions && $group->isProgramVigente() && ($sessionEndedToday || $group->status === 'active'))
            <form action="{{ route('admin.groups.close-session', $group) }}" method="POST">
                @csrf
                <button type="submit"
                    class="text-sm font-medium px-4 py-2 rounded-lg transition border {{ $sessionEndedToday ? 'border-teal-300 text-teal-600 hover:bg-teal-50' : 'border-amber-300 text-amber-600 hover:bg-amber-50' }}">
                    {{ $sessionEndedToday ? 'Reabrir sesión de hoy' : 'Finalizar sesión de hoy' }}
                </button>
            </form>
            @endif
            @if($group->isProgramVigente())
            @php
                $activePatientsCount = $group->patients->count();
                $finalizeWarning = "¿Finalizar " . ($group->auto_sessions ? 'el programa' : 'el grupo') . " «{$group->name}»?\n\n"
                    . "Esto NO borra el grupo ni su historial (asistencias y pesos quedan intactos), pero:\n"
                    . "• Va a sacar a los {$activePatientsCount} paciente(s) actualmente inscriptos.\n"
                    . ($group->auto_sessions ? "• Corta la recurrencia: no se van a generar más sesiones futuras.\n" : '')
                    . "• El código QR deja de servir para nuevos check-ins.\n"
                    . "• Vas a poder reactivarlo después, pero los pacientes sacados no vuelven solos: tienen que volver a escanear el QR o los tenés que re-agregar vos.\n\n"
                    . "¿Confirmás?";
            @endphp
            <form action="{{ route('admin.groups.toggle', $group) }}" method="POST"
                  onsubmit="return confirm({{ \Illuminate\Support\Js::from($finalizeWarning) }})">
                @csrf
                <button type="submit"
                    class="text-sm font-semibold px-4 py-2 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                    Finalizar {{ $group->auto_sessions ? 'programa' : 'grupo' }}
                </button>
            </form>
            @endif
            @if($group->isProgramClosed())
            <form action="{{ route('admin.groups.reactivate', $group) }}" method="POST"
                  onsubmit="return confirm('¿Reactivar {{ $group->auto_sessions ? 'el programa' : 'el grupo' }} «{{ addslashes($group->name) }}»?\n\nLos pacientes que fueron sacados al finalizarlo NO vuelven solos: tienen que volver a escanear el QR o los tenés que re-agregar vos.')">
                @csrf
                <button type="submit"
                    class="text-sm font-semibold px-4 py-2 rounded-lg transition border border-teal-300 text-teal-600 hover:bg-teal-50">
                    Reactivar {{ $group->auto_sessions ? 'programa' : 'grupo' }}
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- QR Code --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
            <h2 class="font-semibold text-gray-800 mb-4">Código QR del grupo</h2>
            <div class="inline-block p-3 bg-white border-2 border-gray-100 rounded-xl shadow-inner">
                {!! $qrCode !!}
            </div>
            <p class="text-xs text-gray-400 mt-3">Los pacientes escanean este QR al llegar</p>
            <div class="mt-3 p-2 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-400 break-all">{{ $joinUrl }}</p>
            </div>
            <p class="text-xs text-gray-500 mt-2 text-left leading-relaxed">
                Para <strong>campañas</strong>, añadí a la URL parámetros UTM y generá el QR con esa dirección completa, por ejemplo:<br>
                <code class="text-[10px] break-all bg-white border border-gray-100 rounded px-1 py-0.5 block mt-1">{{ $joinUrl }}?utm_source=facebook&amp;utm_medium=qr_sala&amp;utm_campaign=2026-03</code>
            </p>
            <a href="{{ $joinUrl }}" target="_blank" class="mt-2 inline-block text-xs text-teal-600 hover:underline">
                Abrir enlace directo
            </a>
        </div>

        {{-- Stats --}}
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
                    <p id="stat-visits" class="text-3xl font-bold text-teal-600">{{ $totalVisits }}</p>
                    <p class="text-xs text-gray-500 mt-1">Visitas hoy</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
                    <p class="text-3xl font-bold text-blue-600">{{ $group->attendances()->distinct('user_id')->count('user_id') }}</p>
                    <p class="text-xs text-gray-500 mt-1">Pacientes</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center col-span-2">
                    <p id="stat-avg" class="text-3xl font-bold text-green-600">{{ $avgWeight ? number_format($avgWeight, 1) . ' kg' : '—' }}</p>
                    <p class="text-xs text-gray-500 mt-1">Peso promedio hoy</p>
                </div>
            </div>
        </div>

        {{-- Coordinators --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800 text-sm">Coordinadores</h2>
            </div>
            <div class="p-4 space-y-2">
                @forelse($group->coordinators as $coord)
                    <div class="flex justify-between items-center py-1">
                        <div class="flex items-center gap-2">
                            <x-avatar :user="$coord" size="sm" />
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $coord->name }}</p>
                                <p class="text-xs text-gray-400">{{ $coord->email }}</p>
                            </div>
                        </div>
                        <form action="{{ route('admin.groups.coordinators.remove', $group) }}" method="POST"
                              onsubmit="return confirm('¿Quitar a {{ $coord->name }} como coordinador de este grupo?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="user_id" value="{{ $coord->id }}">
                            <button class="text-xs text-red-400 hover:text-red-600">✕</button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">Sin coordinadores.</p>
                @endforelse
                <form action="{{ route('admin.groups.coordinators.add', $group) }}" method="POST" class="flex gap-2 pt-2 border-t">
                    @csrf
                    <select name="user_id" class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1.5 focus:ring-1 focus:ring-teal-500 outline-none">
                        <option value="">Agregar...</option>
                        @foreach($allCoordinators->diff($group->coordinators) as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <button class="bg-teal-600 text-white text-xs px-2 py-1.5 rounded-lg hover:bg-teal-700">+</button>
                </form>
            </div>
        </div>
    </div>

    @include('partials.group-historial')

    {{-- Patients --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">{{ $group->name }} (<span id="patients-count">{{ $group->patients->where('belonging_group_id', $group->id)->count() }}</span>)</h2>
            <form action="{{ route('admin.groups.patients.add', $group) }}" method="POST" class="flex gap-2" id="add-patient-form">
                @csrf
                <div class="relative">
                    <input type="text" id="patient-search" autocomplete="off" placeholder="Agregar paciente..."
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 w-56 focus:ring-2 focus:ring-teal-500 outline-none">
                    <input type="hidden" name="user_id" id="patient-user-id">
                    <div id="patient-options" class="hidden absolute z-10 mt-1 w-72 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"></div>
                </div>
                <button class="bg-teal-600 text-white text-sm px-3 py-1.5 rounded-lg hover:bg-teal-700 shrink-0">Agregar</button>
            </form>
        </div>
        <div id="patients-list" class="divide-y divide-gray-50">
            @forelse($group->patientsAll->where('belonging_group_id', $group->id) as $patient)
                @php $piv = $patient->pivot; $left = $piv->left_at; @endphp
                <div class="px-5 py-3 flex justify-between items-center gap-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <x-avatar :user="$patient" size="sm" />
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-medium text-gray-800">{{ $patient->name }}</p>
                                @if($left)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Salió</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400">{{ $patient->email }}@if($patient->phone) · {{ $patient->phone }}@endif</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">
                                Alta: {{ \Carbon\Carbon::parse($piv->joined_at)->format('d/m/Y H:i') }}
                                · <span class="text-gray-600">{{ $piv->join_source === 'qr' ? 'QR' : 'Manual' }}</span>
                                @if($piv->utm_source)
                                    · UTM: {{ $piv->utm_source }}{{ $piv->utm_campaign ? ' / '.$piv->utm_campaign : '' }}
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($group->active && !$left)
                    <form action="{{ route('admin.groups.patients.remove', $group) }}" method="POST">
                        @csrf @method('DELETE')
                        <input type="hidden" name="user_id" value="{{ $patient->id }}">
                        <button class="text-xs text-red-400 hover:text-red-600">Remover</button>
                    </form>
                    @endif
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400 text-center">Sin pacientes. Los pacientes se agregan automáticamente al escanear el QR.</p>
            @endforelse
        </div>
    </div>

    @php
        $todayAttendances = $attendances->filter(fn($a) => $a->attended_at->isToday());
        $pastAttendances  = $attendances->filter(fn($a) => !$a->attended_at->isToday());
    @endphp

    {{-- Presentes hoy --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800 flex items-center gap-2 flex-wrap">
                Presentes hoy
                <span id="live-session-badge" class="text-xs font-semibold text-teal-700 tabular-nums">@if($todaySessionRecord)Sesión n.º {{ $todaySessionRecord->sequence_number }}@else<span class="text-gray-400 font-normal">—</span>@endif</span>
                @if($group->isLiveSessionNow())
                <span id="live-badge" class="inline-flex items-center gap-1 text-xs text-green-600 font-normal">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                    En vivo
                </span>
                @elseif($sessionEndedToday)
                <span class="inline-flex items-center gap-1 text-xs text-gray-400 font-normal">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                    Sesión cerrada
                </span>
                @endif
            </h2>
            <span id="last-update" class="text-xs text-gray-400"></span>
        </div>
        <div id="attendance-table" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-center">Sesión</th>
                        <th class="px-5 py-3 text-left">Entrada</th>
                        <th class="px-5 py-3 text-left">Salida</th>
                        <th class="px-5 py-3 text-right">Peso</th>
                        <th class="px-5 py-3 text-right">Dif.</th>
                    </tr>
                </thead>
                <tbody id="attendance-body" class="divide-y divide-gray-50">
                    @forelse($todayAttendances as $att)
                        @php
                            $rw = $att->weightRecord?->weight;
                            $enMant = $att->user->estaEnMantenimiento();
                            if ($enMant) {
                                $piso = $att->user->peso_piso; $techo = $att->user->peso_techo;
                                $diff = null;
                                if ($rw && $techo && $rw > $techo) $diff = round($rw - $techo, 2);
                                elseif ($rw && $piso && $rw < $piso) $diff = round($rw - $piso, 2);
                            } else {
                                $iw = $att->user->ideal_weight;
                                $diff = ($rw && $iw) ? round($rw - $iw, 2) : null;
                            }
                        @endphp
                        <tr>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <x-avatar :user="$att->user" size="sm" />
                                    <span class="font-medium text-gray-800">{{ $att->user->name }}</span>
                                    @if($att->user->belonging_group_id === $group->id)
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 font-medium">Pertenece</span>
                                    @else
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-medium">Visita</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600 tabular-nums text-xs">
                                @if($att->groupSession)
                                    n.º {{ $att->groupSession->sequence_number }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $att->attended_at->format('H:i') }}</td>
                            <td class="px-5 py-3">
                                @if($att->left_at)
                                    <span class="text-gray-500">{{ $att->left_at->format('H:i') }}</span>
                                @else
                                    <button onclick="checkout({{ $att->id }}, this)"
                                        class="text-xs text-teal-600 border border-teal-200 rounded px-2 py-0.5 hover:bg-teal-50 transition">
                                        Marcar salida
                                    </button>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $rw ? 'text-teal-600' : 'text-gray-300' }}">
                                {{ $rw ? $rw . ' kg' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold">
                                @if($diff !== null)
                                    @if($diff > 0)<span class="text-red-500">↑ +{{ $diff }} kg</span>
                                    @elseif($diff < 0)<span class="text-green-600">↓ {{ $diff }} kg</span>
                                    @else<span class="text-gray-400">= ideal</span>@endif
                                @else<span class="text-gray-300">—</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="6" class="px-5 py-8 text-center text-gray-400">Nadie se ha registrado hoy todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Historial de visitas anteriores --}}
    @if($pastAttendances->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Historial de visitas anteriores</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-left">Fecha y hora</th>
                        <th class="px-5 py-3 text-center">Sesión</th>
                        <th class="px-5 py-3 text-right">Peso</th>
                        <th class="px-5 py-3 text-right">Objetivo / Rango</th>
                        <th class="px-5 py-3 text-right">Dif.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($pastAttendances as $att)
                        @php
                            $rw = $att->weightRecord?->weight;
                            $enMant = $att->user->estaEnMantenimiento();
                            if ($enMant) {
                                $piso = $att->user->peso_piso; $techo = $att->user->peso_techo;
                                $targetLabel = ($piso || $techo) ? ($piso ?? '?').'–'.($techo ?? '?').' kg' : null;
                                $diff = null;
                                if ($rw && $techo && $rw > $techo) $diff = round($rw - $techo, 2);
                                elseif ($rw && $piso && $rw < $piso) $diff = round($rw - $piso, 2);
                            } else {
                                $iw = $att->user->ideal_weight;
                                $targetLabel = $iw ? $iw.' kg' : null;
                                $diff = ($rw && $iw) ? round($rw - $iw, 2) : null;
                            }
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <x-avatar :user="$att->user" size="sm" />
                                    <span class="font-medium text-gray-800">{{ $att->user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $att->attended_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-center text-gray-600 tabular-nums text-xs">
                                @if($att->groupSession)
                                    n.º {{ $att->groupSession->sequence_number }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $rw ? 'text-teal-600' : 'text-gray-300' }}">
                                {{ $rw ? $rw . ' kg' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right text-gray-400">{{ $targetLabel ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold">
                                @if($diff !== null)
                                    @if($diff > 0)<span class="text-red-500">↑ +{{ $diff }} kg</span>
                                    @elseif($diff < 0)<span class="text-green-600">↓ {{ $diff }} kg</span>
                                    @else<span class="text-gray-400">= ideal</span>@endif
                                @else<span class="text-gray-300">—</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

<script>
const liveUrl        = '{{ route('admin.groups.live', $group) }}';
const checkoutBase   = '{{ url('admin/groups/' . $group->id . '/attendances') }}';
const removeUrl      = '{{ route('admin.groups.patients.remove', $group) }}';
const csrfToken      = '{{ csrf_token() }}';
const groupClosed    = {{ $group->status === 'closed' ? 'true' : 'false' }};
const canRemove      = {{ $group->isProgramVigente() ? 'true' : 'false' }};
const tbody          = document.getElementById('attendance-body');
const sessionBadge   = document.getElementById('live-session-badge');
const updateEl       = document.getElementById('last-update');
const statVisits     = document.getElementById('stat-visits');
const statAvg        = document.getElementById('stat-avg');
const patientsList   = document.getElementById('patients-list');
const patientsCount  = document.getElementById('patients-count');

function avatarHtml(a) {
    if (a.avatar_url) {
        return `<img src="${a.avatar_url}" alt="${a.name}"
            class="w-8 h-8 rounded-full object-cover shrink-0"
            onerror="this.style.display='none';this.nextElementSibling.style.cssText='display:flex;background-color:${a.color}'">
            <div class="w-8 h-8 rounded-full items-center justify-center shrink-0 font-semibold text-white text-xs" style="display:none">${a.initials}</div>`;
    }
    return `<div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 font-semibold text-white text-xs" style="background-color:${a.color}">${a.initials}</div>`;
}

function renderPatients(allPatients) {
    const patients = allPatients.filter(p => p.is_belonging);
    patientsCount.textContent = patients.filter(p => !p.left_at).length;
    if (patients.length === 0) {
        patientsList.innerHTML = '<p class="px-5 py-4 text-sm text-gray-400 text-center">Sin pacientes. Los pacientes se agregan automáticamente al escanear el QR.</p>';
        return;
    }
    patientsList.innerHTML = patients.map(p => {
        const removeBtn = canRemove
            ? `<form action="${removeUrl}" method="POST" style="display:inline">
                <input type="hidden" name="_token" value="${csrfToken}">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="user_id" value="${p.id}">
                <button type="submit" class="text-xs text-red-400 hover:text-red-600">Remover</button>
               </form>`
            : '';
        const utm = p.utm_source ? ` · UTM: ${p.utm_source}${p.utm_campaign ? ' / '+p.utm_campaign : ''}` : '';
        const leftBadge = p.left_at
            ? '<span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Salió</span>'
            : '';
        return `<div class="px-5 py-3 flex justify-between items-center gap-2">
            <div class="flex items-center gap-3 min-w-0">
                ${avatarHtml(p)}
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800">${p.name} ${leftBadge}</p>
                    <p class="text-xs text-gray-400">${p.email ?? ''}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">
                        Alta: ${p.joined_at ?? '—'}
                        · <span class="text-gray-600">${p.join_source === 'qr' ? 'QR' : 'Manual'}</span>${utm}
                    </p>
                </div>
            </div>
            ${removeBtn}
        </div>`;
    }).join('');
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
        btn.closest('td').innerHTML = `<span class="text-gray-500">${data.left_at}</span>`;
        fetchAttendances();
    } catch(e) { btn.disabled = false; btn.textContent = 'Marcar salida'; }
}

async function fetchAttendances() {
    let data;
    try {
        const res = await fetch(liveUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        data = await res.json();
    } catch(e) { return; }

    // Update patients section (independent of attendance rendering)
    if (data.patients !== undefined) renderPatients(data.patients);

    // Update stats
    statVisits.textContent = data.count;
    statAvg.textContent    = data.avg_weight ? data.avg_weight + ' kg' : '—';
    if (sessionBadge) {
        sessionBadge.textContent = data.session_number != null ? 'Sesión n.º ' + data.session_number : '—';
    }

    // Update attendance table
    try {
        if (data.attendances.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">Sin visitas registradas aún.</td></tr>';
        } else {
            tbody.innerHTML = data.attendances.map(a => {
                const rw = a.weight;
                let diff = null;
                if (a.en_mantenimiento) {
                    if (rw && a.peso_techo && rw > a.peso_techo) diff = Math.round((rw - a.peso_techo) * 100) / 100;
                    else if (rw && a.peso_piso && rw < a.peso_piso) diff = Math.round((rw - a.peso_piso) * 100) / 100;
                } else if (rw && a.ideal_weight) {
                    diff = Math.round((rw - a.ideal_weight) * 100) / 100;
                }
                const diffHtml = diff !== null
                    ? (diff > 0
                        ? `<span class="text-red-500">↑ +${diff} kg</span>`
                        : diff < 0
                            ? `<span class="text-green-600">↓ ${diff} kg</span>`
                            : `<span class="text-gray-400">= ideal</span>`)
                    : '<span class="text-gray-300">—</span>';
                const isPresent = !a.left_at;
                const leftHtml = isPresent
                    ? `<button onclick="checkout(${a.attendance_id}, this)"
                        class="text-xs text-teal-600 border border-teal-200 rounded px-2 py-0.5 hover:bg-teal-50 transition">
                        Marcar salida
                       </button>`
                    : `<span class="text-gray-500">${a.left_at}</span>`;
                const statusBadge = isPresent
                    ? `<span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-100 rounded-full px-2 py-0.5"><span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>En sesión</span>`
                    : `<span class="text-xs text-gray-400 bg-gray-100 rounded-full px-2 py-0.5">Salió ${a.left_at}</span>`;
                const sessCell = a.session_number != null
                    ? `n.º ${a.session_number}`
                    : '<span class="text-gray-300">—</span>';
                const belongingBadge = a.is_belonging
                    ? '<span class="text-[10px] px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 font-medium">Pertenece</span>'
                    : '<span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 font-medium">Visita</span>';
                return `<tr class="${isPresent ? '' : 'opacity-60'}">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            ${avatarHtml(a)}
                            <div>
                                <span class="font-medium text-gray-800">${a.name}</span> ${belongingBadge}
                                <div class="mt-0.5">${statusBadge}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center text-gray-600 tabular-nums text-xs">${sessCell}</td>
                    <td class="px-5 py-3 text-gray-500">${a.attended_at}</td>
                    <td class="px-5 py-3">${leftHtml}</td>
                    <td class="px-5 py-3 text-right font-semibold ${rw ? 'text-teal-600' : 'text-gray-300'}">${rw ? rw + ' kg' : '—'}</td>
                    <td class="px-5 py-3 text-right font-semibold">${diffHtml}</td>
                </tr>`;
            }).join('');
        }
    } catch(e) {}

    const now = new Date();
    updateEl.textContent = 'Act. ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0');
}

const sessionEndedToday = {{ $sessionEndedToday ? 'true' : 'false' }};

fetchAttendances();
if (!groupClosed && !sessionEndedToday) {
    setInterval(fetchAttendances, 4000);
}

// Buscador del combo "Agregar paciente..."
const availablePatients = @json($allPatients->diff($group->patients)->values()->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'email' => $p->email]));
const patientSearch  = document.getElementById('patient-search');
const patientUserId  = document.getElementById('patient-user-id');
const patientOptions = document.getElementById('patient-options');
const addPatientForm = document.getElementById('add-patient-form');

function escapeHtml(str) {
    return (str ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function normalizeSearch(str) {
    return (str ?? '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
}

function renderPatientOptions(list) {
    if (list.length === 0) {
        patientOptions.innerHTML = '<p class="px-3 py-2 text-xs text-gray-400">Sin resultados.</p>';
    } else {
        patientOptions.innerHTML = list.slice(0, 30).map(p => `
            <button type="button" data-id="${p.id}" data-name="${escapeHtml(p.name)}"
                class="patient-option w-full text-left px-3 py-2 hover:bg-teal-50 text-sm">
                <p class="font-medium text-gray-800">${escapeHtml(p.name)}</p>
                <p class="text-xs text-gray-400">${escapeHtml(p.email)}</p>
            </button>
        `).join('');
        if (list.length > 30) {
            patientOptions.innerHTML += `<p class="px-3 py-1.5 text-[10px] text-gray-400 border-t">Mostrando 30 de ${list.length}. Seguí escribiendo para acotar.</p>`;
        }
    }
    patientOptions.classList.remove('hidden');
}

patientSearch.addEventListener('input', () => {
    patientUserId.value = '';
    const q = normalizeSearch(patientSearch.value);
    const filtered = q === ''
        ? availablePatients
        : availablePatients.filter(p => normalizeSearch(p.name).includes(q) || normalizeSearch(p.email).includes(q));
    renderPatientOptions(filtered);
});

patientSearch.addEventListener('focus', () => renderPatientOptions(
    normalizeSearch(patientSearch.value) === ''
        ? availablePatients
        : availablePatients.filter(p => normalizeSearch(p.name).includes(normalizeSearch(patientSearch.value)) || normalizeSearch(p.email).includes(normalizeSearch(patientSearch.value)))
));

patientOptions.addEventListener('click', (e) => {
    const btn = e.target.closest('.patient-option');
    if (!btn) return;
    patientUserId.value = btn.dataset.id;
    patientSearch.value = btn.dataset.name;
    patientOptions.classList.add('hidden');
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('#add-patient-form')) patientOptions.classList.add('hidden');
});

addPatientForm.addEventListener('submit', (e) => {
    if (!patientUserId.value) {
        e.preventDefault();
        alert('Elegí un paciente de la lista antes de agregar.');
    }
});
</script>

@endsection
