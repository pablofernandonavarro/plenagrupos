@extends('layouts.app')
@section('title', 'Adherencia y datos')

@section('content')
<div class="space-y-6">
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Adherencia y completitud</h1>
            <p class="text-gray-500 text-sm mt-1">Última visita grupal, último peso, InBody y turnos médicos por paciente.</p>
        </div>
        <a href="{{ route('admin.appointment-requirements.index') }}" class="text-sm text-teal-600 hover:underline whitespace-nowrap">
            Configurar requisitos de turnos
        </a>
    </div>

    {{-- Export CSV --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 mb-3">Exportar datos (CSV, UTF-8 para Excel)</h2>
        <p class="text-xs text-gray-500 mb-4">Incluye todos los registros actuales. El archivo lleva fecha en el nombre.</p>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.exports.attendances') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-700 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Asistencias
            </a>
            <a href="{{ route('admin.exports.weights') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Pesos
            </a>
            <a href="{{ route('admin.exports.inbody') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-gray-800 text-sm font-medium hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                InBody
            </a>
            <a href="{{ route('admin.exports.group-patients') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-teal-200 text-teal-800 text-sm font-medium hover:bg-teal-50 transition" title="Canal QR/manual y UTM por alta">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Pacientes × grupo
            </a>
        </div>
        <details class="mt-4 text-xs text-gray-600 border-t border-gray-100 pt-4">
            <summary class="cursor-pointer font-medium text-gray-700">Cómo usar UTM y el QR</summary>
            <ul class="mt-2 space-y-1 list-disc list-inside text-gray-500">
                <li>En <strong>Grupo → QR</strong> podés copiar la URL base o añadir parámetros para campañas, p. ej. <code class="bg-gray-100 px-1 rounded">?utm_source=instagram&amp;utm_medium=story&amp;utm_campaign=marzo2026</code>.</li>
                <li>Generá un QR apuntando a esa URL completa: al escanear, se guardan el canal (<strong>qr</strong>) y los UTM en la primera vez que el paciente entra al grupo.</li>
                <li>Si el paciente lo da de alta un admin desde el panel, el canal queda <strong>manual</strong>.</li>
                <li>El <strong>estado del paciente</strong> (activo / pausa / egreso) se edita en <strong>Usuarios → Editar</strong>; los egresados no entran en cohortes de retención.</li>
            </ul>
        </details>
    </div>

    {{-- Filtros --}}
    <form method="get" action="{{ route('admin.adherence.index') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h2 class="font-semibold text-gray-800 mb-1">Umbrales de alerta</h2>
        <p class="text-xs text-gray-400 mb-4">Se marca en rojo cuando el paciente supera los días configurados sin registro.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            {{-- Visita --}}
            <div class="flex flex-col gap-1">
                <label for="alert_days_att" class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                    Sin visita grupal (días)
                </label>
                <input type="number" name="alert_days_att" id="alert_days_att"
                       value="{{ $alertDaysAtt }}" min="1" max="365"
                       class="rounded-lg border border-gray-200 text-sm py-2 px-3 w-full focus:ring-2 focus:ring-blue-300 focus:border-blue-400 outline-none">
                <span class="text-[11px] text-gray-400">Por defecto: 14 días</span>
            </div>

            {{-- Peso --}}
            <div class="flex flex-col gap-1">
                <label for="alert_days_weight" class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-orange-400"></span>
                    Sin registro de peso (días)
                </label>
                <input type="number" name="alert_days_weight" id="alert_days_weight"
                       value="{{ $alertDaysWeight }}" min="1" max="365"
                       class="rounded-lg border border-gray-200 text-sm py-2 px-3 w-full focus:ring-2 focus:ring-orange-300 focus:border-orange-400 outline-none">
                <span class="text-[11px] text-gray-400">Por defecto: 14 días</span>
            </div>

            {{-- InBody --}}
            <div class="flex flex-col gap-1">
                <label for="alert_days_inbody" class="text-xs font-medium text-gray-600 flex items-center gap-1.5">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-purple-400"></span>
                    Sin InBody (días)
                </label>
                <input type="number" name="alert_days_inbody" id="alert_days_inbody"
                       value="{{ $alertDaysInbody }}" min="1" max="365"
                       class="rounded-lg border border-gray-200 text-sm py-2 px-3 w-full focus:ring-2 focus:ring-purple-300 focus:border-purple-400 outline-none">
                <span class="text-[11px] text-gray-400">Por defecto: 30 días</span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 pt-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="solo_alertas" value="1" {{ $onlyAlerts ? 'checked' : '' }}
                       class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                Mostrar solo pacientes con alertas
            </label>
            <div class="flex items-center gap-2 ml-auto">
                @if(request()->hasAny(['alert_days_att', 'alert_days_weight', 'alert_days_inbody', 'solo_alertas']))
                    <a href="{{ route('admin.adherence.index') }}" class="text-sm text-gray-400 hover:text-gray-700 transition">Restablecer</a>
                @endif
                <button type="submit" class="px-5 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition">Aplicar filtros</button>
            </div>
        </div>
    </form>

    {{-- Leyenda --}}
    <p class="text-xs text-gray-500">
        Se marca "atrasado" cuando pasan más de <strong>{{ $alertDaysAtt }} días</strong> sin visita, <strong>{{ $alertDaysWeight }}</strong> sin registrar peso, o <strong>{{ $alertDaysInbody }}</strong> sin InBody — o cuando no se cumple el mínimo mensual de turnos según plan contratado (configurado en Requisitos de turnos).
    </p>

    {{-- Buscador --}}
    <form method="get" action="{{ route('admin.adherence.index') }}" class="flex gap-2">
        <input type="hidden" name="alert_days_att" value="{{ $alertDaysAtt }}">
        <input type="hidden" name="alert_days_weight" value="{{ $alertDaysWeight }}">
        <input type="hidden" name="alert_days_inbody" value="{{ $alertDaysInbody }}">
        @if($onlyAlerts)<input type="hidden" name="solo_alertas" value="1">@endif
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}"
                placeholder="Buscar paciente por nombre o email..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white">
        </div>
        <button type="submit"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-xl transition">
            Buscar
        </button>
        @if($search !== '')
            <a href="{{ route('admin.adherence.index', request()->except('search')) }}"
               class="px-4 py-2.5 border border-gray-200 text-gray-500 text-sm rounded-xl hover:bg-gray-50 transition">
                ✕
            </a>
        @endif
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3 font-medium">Paciente</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                    <th class="px-4 py-3 font-medium">Visita</th>
                    <th class="px-4 py-3 font-medium">Peso</th>
                    <th class="px-4 py-3 font-medium">InBody</th>
                    <th class="px-4 py-3 font-medium text-center">Turno médico</th>
                    <th class="px-4 py-3 font-medium text-center">Turno nutric.</th>
                    <th class="px-4 py-3 font-medium">Seguimiento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $row)
                    @php
                        $issueLabels = collect([
                            $row['attStale'] ? ($row['lastAtt'] ? 'visita' : 'nunca visitó') : null,
                            $row['weightStale'] ? ($row['lastWeight'] ? 'peso' : 'nunca se pesó') : null,
                            $row['inbodyStale'] ? ($row['lastInbody'] ? 'InBody' : 'nunca hizo InBody') : null,
                            $row['medicoStale'] ? 'turno médico' : null,
                            $row['nutriStale'] ? 'turno nutricionista' : null,
                            $row['combinedStale'] ? 'turno de control' : null,
                        ])->filter()->values();
                    @endphp
                    <tr class="{{ $row['needsAttention'] ? 'bg-amber-50/50' : 'hover:bg-gray-50/60' }}">
                        {{-- Paciente --}}
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">{{ $row['patient']->name }}</p>
                            <p class="text-xs text-gray-400">{{ $row['patient']->email }}</p>
                        </td>

                        {{-- Estado paciente --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php $st = $row['patient']->patient_status ?? 'active'; @endphp
                            <span class="text-xs font-medium px-2 py-0.5 rounded
                                @if($st === 'active') bg-green-50 text-green-800
                                @elseif($st === 'pause') bg-yellow-50 text-yellow-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $st === 'active' ? 'Activo' : ($st === 'pause' ? 'Pausa' : 'Egreso') }}
                            </span>
                        </td>

                        {{-- Visita --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($row['lastAtt'])
                                <p class="text-gray-700">{{ $row['lastAtt']->format('d/m/Y') }}</p>
                                <p class="text-xs {{ $row['attStale'] ? 'text-amber-700 font-medium' : 'text-gray-400' }}">hace {{ $row['daysAtt'] }} d</p>
                            @else
                                <p class="text-sm font-medium text-amber-700">Nunca</p>
                            @endif
                        </td>

                        {{-- Peso --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($row['lastWeight'])
                                <p class="text-gray-700">{{ $row['lastWeight']->format('d/m/Y') }}</p>
                                <p class="text-xs {{ $row['weightStale'] ? 'text-amber-700 font-medium' : 'text-gray-400' }}">hace {{ $row['daysW'] }} d</p>
                            @else
                                <p class="text-sm font-medium text-amber-700">Nunca</p>
                            @endif
                        </td>

                        {{-- InBody --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($row['lastInbody'])
                                <p class="text-gray-700">{{ $row['lastInbody']->format('d/m/Y') }}</p>
                                <p class="text-xs {{ $row['inbodyStale'] ? 'text-amber-700 font-medium' : 'text-gray-400' }}">hace {{ $row['daysIn'] }} d</p>
                            @else
                                <p class="text-sm font-medium text-amber-700">Nunca</p>
                            @endif
                        </td>

                        @php
                            $turnoStateBadge = fn ($state) => match ($state) {
                                'completed' => '<span class="text-xs px-1.5 py-0.5 rounded-full bg-green-50 text-green-700 font-medium">✓ Cumplido</span>',
                                'scheduled' => '<span class="text-xs px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-medium">📅 Agendado</span>',
                                'pending'   => '<span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">⏳ Por confirmar</span>',
                                default     => null,
                            };
                        @endphp
                        @if($row['combinedTurnos'])
                            {{-- Turno de control (médico o nutricionista, cupo compartido) --}}
                            <td class="px-4 py-3 text-center whitespace-nowrap" colspan="2">
                                <span class="{{ $row['combinedStale'] ? 'text-amber-700 font-semibold' : 'text-gray-600' }}">
                                    {{ $row['combinedCount'] }}/{{ $row['combinedRequired'] }}
                                </span>
                                @if($badge = $turnoStateBadge($row['combinedState']))
                                    <span class="ml-1.5">{!! $badge !!}</span>
                                @endif
                                <span class="text-[11px] text-gray-400 ml-1">control (médico o nutric.)</span>
                            </td>
                        @else
                        {{-- Turno médico --}}
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <span class="{{ $row['medicoStale'] ? 'text-amber-700 font-semibold' : 'text-gray-600' }}">
                                {{ $row['medicoCount'] }}/{{ $row['medicoRequired'] }}
                            </span>
                            @if($badge = $turnoStateBadge($row['medicoState']))
                                <br><span class="mt-0.5 inline-block">{!! $badge !!}</span>
                            @endif
                        </td>

                        {{-- Turno nutricionista --}}
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            <span class="{{ $row['nutriStale'] ? 'text-amber-700 font-semibold' : 'text-gray-600' }}">
                                {{ $row['nutriCount'] }}/{{ $row['nutriRequired'] }}
                            </span>
                            @if($badge = $turnoStateBadge($row['nutriState']))
                                <br><span class="mt-0.5 inline-block">{!! $badge !!}</span>
                            @endif
                        </td>
                        @endif

                        {{-- Seguimiento (resumen) --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if(! $row['needsAttention'])
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Al día
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 cursor-help"
                                    title="Pendiente: {{ $issueLabels->join(', ') }}">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    {{ $issueLabels->count() }} {{ $issueLabels->count() === 1 ? 'pendiente' : 'pendientes' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400">No hay pacientes que coincidan con el filtro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400">
        Mostrando {{ $rows->count() }} paciente(s).
        Umbrales activos: visita {{ $alertDaysAtt }}d · peso {{ $alertDaysWeight }}d · InBody {{ $alertDaysInbody }}d ·
        turnos médico/nutricionista según plan contratado.
    </p>
</div>
@endsection
