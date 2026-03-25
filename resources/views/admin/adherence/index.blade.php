@extends('layouts.app')
@section('title', 'Adherencia y datos')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Adherencia y completitud</h1>
        <p class="text-gray-500 text-sm mt-1">Última visita grupal, último peso e InBody por paciente. Las alertas marcan si hace más de N días sin visita o sin registro de peso.</p>
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

    <form method="get" action="{{ route('admin.adherence.index') }}" class="flex flex-wrap items-end gap-4 bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <div>
            <label for="alert_days" class="block text-xs text-gray-500 mb-1">Alerta si sin visita o sin peso hace más de (días)</label>
            <input type="number" name="alert_days" id="alert_days" value="{{ $alertDays }}" min="1" max="365"
                   class="rounded-lg border border-gray-200 text-sm py-2 px-3 w-28">
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
            <input type="checkbox" name="solo_alertas" value="1" {{ $onlyAlerts ? 'checked' : '' }}
                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
            Solo filas con alerta
        </label>
        <button type="submit" class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:bg-teal-700 transition">Aplicar</button>
        @if(request()->hasAny(['alert_days', 'solo_alertas']))
            <a href="{{ route('admin.adherence.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Restablecer</a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-4 py-3 font-medium">Paciente</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                    <th class="px-4 py-3 font-medium">Última visita</th>
                    <th class="px-4 py-3 font-medium text-right">Días sin visitar</th>
                    <th class="px-4 py-3 font-medium">Último peso</th>
                    <th class="px-4 py-3 font-medium text-right">Días sin pesar</th>
                    <th class="px-4 py-3 font-medium">Último InBody</th>
                    <th class="px-4 py-3 font-medium text-right">Días</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($rows as $row)
                    @php
                        $att = $row['lastAtt'];
                        $w = $row['lastWeight'];
                        $in = $row['lastInbody'];
                    @endphp
                    <tr class="{{ $row['needsAttention'] ? 'bg-amber-50/80' : 'hover:bg-gray-50/80' }}">
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800">{{ $row['patient']->name }}</p>
                            <p class="text-xs text-gray-400">{{ $row['patient']->email }}</p>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @php $st = $row['patient']->patient_status ?? 'active'; @endphp
                            <span class="text-xs font-medium px-2 py-0.5 rounded
                                @if($st === 'active') bg-green-50 text-green-800
                                @elseif($st === 'pause') bg-yellow-50 text-yellow-800
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $st === 'active' ? 'Activo' : ($st === 'pause' ? 'Pausa' : 'Egreso') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $att ? $att->format('d/m/Y H:i') : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($row['daysAtt'] !== null)
                                <span class="{{ $row['daysAtt'] > $alertDays ? 'text-red-600 font-semibold' : 'text-gray-700' }}">{{ $row['daysAtt'] }}</span>
                            @else
                                <span class="text-red-600 font-medium">Nunca</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $w ? $w->format('d/m/Y H:i') : '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($row['daysW'] !== null)
                                <span class="{{ $row['daysW'] > $alertDays ? 'text-red-600 font-semibold' : 'text-gray-700' }}">{{ $row['daysW'] }}</span>
                            @else
                                <span class="text-red-600 font-medium">Nunca</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $in ? $in->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $row['daysIn'] !== null ? $row['daysIn'] : '—' }}</td>
                        <td class="px-4 py-3">
                            @if($row['needsAttention'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-900">Revisar</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-800">Al día</span>
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

    <p class="text-xs text-gray-400">Mostrando {{ $rows->count() }} paciente(s). «Revisar» = sin visita en más de {{ $alertDays }} días o sin peso registrado en más de {{ $alertDays }} días (o nunca).</p>
</div>
@endsection
