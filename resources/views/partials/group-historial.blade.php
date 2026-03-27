{{-- Requiere: $group, $groupSessions, $historyDates, $historialDate, $historialStats, $historialAttendances, $historialMembershipEvents, $historialFormAction --}}
<details class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" @if($historialDate) open @endif>
    <summary class="px-5 py-4 cursor-pointer list-none flex items-center justify-between gap-3 text-left font-semibold text-gray-800 hover:bg-gray-50/80 transition [&::-webkit-details-marker]:hidden">
        <span class="flex items-center gap-2 min-w-0">
            <svg class="w-5 h-5 shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Historial del grupo</span>
        </span>
        <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>
    <div class="px-5 pb-5 border-t border-gray-100 space-y-4">

        {{-- Índice de sesiones --}}
        @if($groupSessions->isNotEmpty())
            @php
                $today = \Carbon\Carbon::today()->toDateString();
                $totalSessions = $groupSessions->count();
                $showToggle = $totalSessions > 5;
            @endphp
            <div class="pt-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Sesiones del programa</p>
                    @if($showToggle)
                        <button type="button" id="sessions-toggle"
                            onclick="toggleSessions()"
                            class="text-xs text-teal-600 hover:text-teal-700 font-medium transition">
                            Ver todas ({{ $totalSessions }})
                        </button>
                    @endif
                </div>
                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full text-sm min-w-[22rem]">
                        <thead class="bg-gray-50 text-xs text-gray-400 uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">N.º</th>
                                <th class="px-3 py-2 text-left font-medium">Fecha</th>
                                <th class="px-3 py-2 text-center font-medium">Asistentes</th>
                                <th class="px-3 py-2 text-left font-medium">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($groupSessions as $i => $sess)
                                @php
                                    $sessDate = $sess->session_date->toDateString();
                                    $isToday  = $sessDate === $today;
                                    $isPast   = $sessDate < $today;
                                    $url = $historialFormAction . '?' . http_build_query(['historial' => $sessDate]);
                                    $hidden = $showToggle && $i >= 5;
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition cursor-pointer session-row{{ $hidden ? ' hidden' : '' }}"
                                    onclick="window.location='{{ $url }}'">
                                    <td class="px-3 py-2.5 font-semibold text-gray-700 tabular-nums">{{ $sess->sequence_number }}</td>
                                    <td class="px-3 py-2.5 text-gray-600">
                                        {{ $sess->session_date->locale('es')->translatedFormat('D d/m/Y') }}
                                    </td>
                                    <td class="px-3 py-2.5 text-center font-semibold {{ $sess->attendances_count > 0 ? 'text-teal-600' : 'text-gray-300' }}">
                                        {{ $sess->attendances_count }}
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if($isToday)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>En curso
                                            </span>
                                        @elseif($isPast)
                                            <span class="text-xs text-gray-400 font-medium">Realizada</span>
                                        @else
                                            <span class="text-xs text-purple-500 font-medium">Programada</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($showToggle)
            <script>
            let sessionsExpanded = false;
            function toggleSessions() {
                sessionsExpanded = !sessionsExpanded;
                document.querySelectorAll('.session-row.hidden, .session-row[data-hidden]').forEach(r => r.classList.toggle('hidden'));
                document.querySelectorAll('.session-row').forEach((r, i) => {
                    if (i >= 5) r.classList.toggle('hidden', !sessionsExpanded);
                });
                const btn = document.getElementById('sessions-toggle');
                btn.textContent = sessionsExpanded ? 'Ver menos' : 'Ver todas ({{ $totalSessions }})';
            }
            </script>
            @endif
        @endif

        @if($historyDates->isEmpty())
            <p class="text-sm text-gray-500 pt-1">Todavía no hay asistencias registradas para armar un historial.</p>
        @else
            <form method="get" action="{{ $historialFormAction }}" class="pt-1 space-y-2">
                <label for="historial-fecha" class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Ver día</label>
                <select id="historial-fecha" name="historial"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-400 outline-none bg-white"
                    onchange="this.form.submit()">
                    <option value="">Elegí una fecha…</option>
                    @foreach($historyDates as $d)
                        @php
                            $sessNum = $groupSessions->first(fn($s) => $s->session_date->toDateString() === $d)?->sequence_number;
                        @endphp
                        <option value="{{ $d }}" @selected($historialDate === $d)>
                            {{ $sessNum ? 'Sesión n.º '.$sessNum.' · ' : '' }}{{ \Carbon\Carbon::parse($d)->locale('es')->translatedFormat('l j \d\e F Y') }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400">Mostramos asistencias y movimientos de pacientes (altas/bajas) de ese día.</p>
            </form>

            @if($historialDate && $historialAttendances !== null && $historialStats !== null)
                @php
                    $hd = \Carbon\Carbon::parse($historialDate)->locale('es');
                @endphp
                <div class="rounded-lg border border-teal-100 bg-teal-50/40 px-4 py-3">
                    <p class="text-sm font-semibold text-teal-900 capitalize">{{ $hd->translatedFormat('l j \d\e F Y') }}</p>
                    @if($historialAttendances->first()?->groupSession)
                        <p class="text-xs font-semibold text-teal-800 mt-1">Sesión n.º {{ $historialAttendances->first()->groupSession->sequence_number }}</p>
                    @endif
                    <p class="text-xs text-teal-700/80 mt-0.5">{{ $historialAttendances->count() }} {{ $historialAttendances->count() === 1 ? 'asistencia' : 'asistencias' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Distribución de pesos (ese día)</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div class="rounded-lg border border-green-100 bg-green-50 p-3 text-center">
                            <p class="text-lg font-bold text-green-600">{{ $historialStats['inRange'] }}</p>
                            <p class="text-[10px] text-green-700 mt-0.5">En rango</p>
                        </div>
                        <div class="rounded-lg border border-red-100 bg-red-50 p-3 text-center">
                            <p class="text-lg font-bold text-red-500">{{ $historialStats['above'] }}</p>
                            <p class="text-[10px] text-red-600 mt-0.5">Por encima</p>
                        </div>
                        <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-center">
                            <p class="text-lg font-bold text-blue-500">{{ $historialStats['below'] }}</p>
                            <p class="text-[10px] text-blue-600 mt-0.5">Por debajo</p>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-center">
                            <p class="text-lg font-bold text-gray-500">{{ $historialStats['noWeight'] }}</p>
                            <p class="text-[10px] text-gray-500 mt-0.5">Sin peso</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-100">
                    <table class="w-full text-sm text-left min-w-[32rem]">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2 font-medium">Paciente</th>
                                <th class="px-3 py-2 font-medium">Ingreso</th>
                                <th class="px-3 py-2 font-medium">Salida</th>
                                <th class="px-3 py-2 font-medium">Peso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($historialAttendances as $a)
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-3 py-2.5 font-medium text-gray-800">{{ $a->user->name }}</td>
                                    <td class="px-3 py-2.5 text-gray-600 tabular-nums">{{ $a->attended_at->format('H:i') }}</td>
                                    <td class="px-3 py-2.5 text-gray-600 tabular-nums">{{ $a->left_at?->format('H:i') ?? '—' }}</td>
                                    <td class="px-3 py-2.5 text-gray-700 tabular-nums">{{ $a->weightRecord?->weight !== null ? number_format((float) $a->weightRecord->weight, 1) . ' kg' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($historialMembershipEvents !== null && $historialMembershipEvents->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Altas y bajas (ese día)</p>
                        <ul class="space-y-2 text-sm">
                            @foreach($historialMembershipEvents as $log)
                                @php
                                    $joinedThisDay = $log->joined_at && $log->joined_at->isSameDay(\Carbon\Carbon::parse($historialDate));
                                    $leftThisDay = $log->left_at && $log->left_at->isSameDay(\Carbon\Carbon::parse($historialDate));
                                @endphp
                                <li class="flex flex-wrap items-baseline gap-x-2 gap-y-1 rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2">
                                    <span class="font-medium text-gray-800">{{ $log->user->name }}</span>
                                    @if($joinedThisDay)
                                        <span class="text-xs text-emerald-700">Alta {{ $log->joined_at->format('H:i') }} · {{ $log->join_source === 'qr' ? 'QR' : 'Manual' }}</span>
                                    @endif
                                    @if($leftThisDay)
                                        <span class="text-xs text-gray-600">Baja {{ $log->left_at->format('H:i') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @elseif($historialDate === null)
                <p class="text-sm text-gray-500">Seleccioná una fecha para ver el detalle.</p>
            @endif
        @endif
    </div>
</details>
