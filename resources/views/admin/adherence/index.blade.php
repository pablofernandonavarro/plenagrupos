@extends('layouts.app')
@section('title', 'Adherencia y datos')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Adherencia y completitud</h1>
        <p class="text-gray-500 text-sm mt-1">Última visita grupal, último peso e InBody por paciente.</p>
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
    <div class="flex flex-wrap gap-3 text-xs text-gray-500">
        <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-400"></span> Visita &gt; {{ $alertDaysAtt }}d</span>
        <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-orange-400"></span> Peso &gt; {{ $alertDaysWeight }}d</span>
        <span class="inline-flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full bg-purple-400"></span> InBody &gt; {{ $alertDaysInbody }}d</span>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3 font-medium">Paciente</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                    <th class="px-4 py-3 font-medium">Última visita</th>
                    <th class="px-4 py-3 font-medium text-right">Días</th>
                    <th class="px-4 py-3 font-medium">Último peso</th>
                    <th class="px-4 py-3 font-medium text-right">Días</th>
                    <th class="px-4 py-3 font-medium">Último InBody</th>
                    <th class="px-4 py-3 font-medium text-right">Días</th>
                    <th class="px-4 py-3 font-medium">Alertas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $row)
                    <tr class="{{ $row['needsAttention'] ? 'bg-amber-50/60' : 'hover:bg-gray-50/60' }}">
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

                        {{-- Última visita --}}
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                            {{ $row['lastAtt'] ? $row['lastAtt']->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($row['daysAtt'] !== null)
                                <span class="font-medium {{ $row['attStale'] ? 'text-blue-600' : 'text-gray-600' }}">
                                    {{ $row['daysAtt'] }}
                                </span>
                            @else
                                <span class="font-medium text-blue-600">Nunca</span>
                            @endif
                        </td>

                        {{-- Último peso --}}
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                            {{ $row['lastWeight'] ? $row['lastWeight']->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($row['daysW'] !== null)
                                <span class="font-medium {{ $row['weightStale'] ? 'text-orange-600' : 'text-gray-600' }}">
                                    {{ $row['daysW'] }}
                                </span>
                            @else
                                <span class="font-medium text-orange-600">Nunca</span>
                            @endif
                        </td>

                        {{-- Último InBody --}}
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                            {{ $row['lastInbody'] ? $row['lastInbody']->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($row['daysIn'] !== null)
                                <span class="font-medium {{ $row['inbodyStale'] ? 'text-purple-600' : 'text-gray-600' }}">
                                    {{ $row['daysIn'] }}
                                </span>
                            @else
                                <span class="font-medium text-purple-600">Nunca</span>
                            @endif
                        </td>

                        {{-- Alertas --}}
                        <td class="px-4 py-3">
                            @if(! $row['needsAttention'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Al día
                                </span>
                            @else
                                <div class="flex flex-col gap-1">
                                    @if($row['attStale'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 inline-block"></span>
                                            Sin visita
                                        </span>
                                    @endif
                                    @if($row['weightStale'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-orange-50 text-orange-700 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400 inline-block"></span>
                                            Sin peso
                                        </span>
                                    @endif
                                    @if($row['inbodyStale'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-purple-50 text-purple-700 whitespace-nowrap">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-400 inline-block"></span>
                                            Sin InBody
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">No hay pacientes que coincidan con el filtro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400">
        Mostrando {{ $rows->count() }} paciente(s).
        Umbrales activos: visita {{ $alertDaysAtt }}d · peso {{ $alertDaysWeight }}d · InBody {{ $alertDaysInbody }}d.
    </p>
</div>
@endsection
