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
                    @if($group->status === 'active')
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full font-medium bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>En curso
                        </span>
                    @elseif($group->status === 'pending')
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-yellow-100 text-yellow-700">Sin iniciar</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-500">Finalizado</span>
                    @endif
                </div>
                <div class="flex items-center gap-3 mt-1 flex-wrap">
                    @if($group->description)
                        <p class="text-sm text-gray-500">{{ $group->description }}</p>
                    @endif
                    @if($group->meeting_day || $group->meeting_time)
                        <span class="text-xs bg-teal-50 text-teal-700 border border-teal-200 px-2 py-0.5 rounded-full">
                            {{ $group->meeting_day }}{{ $group->meeting_day && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? $group->meeting_time_formatted . ' hs' : '' }}
                        </span>
                    @endif
                </div>
                @if($group->started_at)
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 font-medium">
                            <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $group->started_at->format('d/m/Y · H:i') }}@if($group->ended_at) → {{ $group->started_at->isSameDay($group->ended_at) ? $group->ended_at->format('H:i') : $group->ended_at->format('d/m/Y · H:i') }}@endif
                        </span>
                    </div>
                @endif
            </div>
        </div>
        @if($group->status === 'active')
        <form action="{{ route('admin.groups.toggle', $group) }}" method="POST" class="shrink-0"
              onsubmit="return confirm('¿Finalizar el grupo? Esta acción no se puede deshacer.')">
            @csrf
            <button type="submit"
                class="text-sm font-semibold px-4 py-2 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                Finalizar grupo
            </button>
        </form>
        @endif
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
                    <p class="text-3xl font-bold text-blue-600">{{ $group->patients->count() }}</p>
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
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $coord->name }}</p>
                            <p class="text-xs text-gray-400">{{ $coord->email }}</p>
                        </div>
                        <form action="{{ route('admin.groups.coordinators.remove', $group) }}" method="POST">
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

    {{-- Patients --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Pacientes del grupo ({{ $group->patients->count() }})</h2>
            <form action="{{ route('admin.groups.patients.add', $group) }}" method="POST" class="flex gap-2">
                @csrf
                <select name="user_id" class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">Agregar paciente...</option>
                    @foreach($allPatients->diff($group->patients) as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <button class="bg-teal-600 text-white text-sm px-3 py-1.5 rounded-lg hover:bg-teal-700">Agregar</button>
            </form>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($group->patients as $patient)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $patient->name }}</p>
                        <p class="text-xs text-gray-400">{{ $patient->email }}@if($patient->phone) · {{ $patient->phone }}@endif</p>
                    </div>
                    @if($group->active)
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

    {{-- Attendance & Weight History --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">
                Historial de visitas y pesos
                @if($group->active)
                    <span class="ml-2 inline-flex items-center gap-1 text-xs text-green-600 font-normal">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        En vivo
                    </span>
                @endif
            </h2>
            @if($group->active)
                <span id="last-update" class="text-xs text-gray-400"></span>
            @endif
        </div>

        <div id="attendance-table" class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-5 py-3 text-left">Paciente</th>
                        <th class="px-5 py-3 text-left">Hora</th>
                        <th class="px-5 py-3 text-right">Peso</th>
                        <th class="px-5 py-3 text-right">Peso ideal</th>
                        <th class="px-5 py-3 text-right">Dif.</th>
                    </tr>
                </thead>
                <tbody id="attendance-body" class="divide-y divide-gray-50">
                    @forelse($attendances as $att)
                        @php
                            $rw   = $att->weightRecord?->weight;
                            $iw   = $att->user->ideal_weight;
                            $diff = ($rw && $iw) ? round($rw - $iw, 2) : null;
                        @endphp
                        <tr>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $att->user->name }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $att->attended_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right font-semibold {{ $rw ? 'text-teal-600' : 'text-gray-300' }}">
                                {{ $rw ? $rw . ' kg' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right text-gray-400">
                                {{ $iw ? $iw . ' kg' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold">
                                @if($diff !== null)
                                    @if($diff > 0)
                                        <span class="text-red-500">↑ +{{ $diff }} kg</span>
                                    @elseif($diff < 0)
                                        <span class="text-green-600">↓ {{ $diff }} kg</span>
                                    @else
                                        <span class="text-gray-400">= ideal</span>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-row">
                            <td colspan="5" class="px-5 py-8 text-center text-gray-400">Sin visitas registradas aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@if($group->active)
<script>
const liveUrl  = '{{ route('admin.groups.live', $group) }}';
const tbody    = document.getElementById('attendance-body');
const updateEl = document.getElementById('last-update');
const statVisits = document.getElementById('stat-visits');
const statAvg    = document.getElementById('stat-avg');

async function fetchAttendances() {
    try {
        const res  = await fetch(liveUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();

        statVisits.textContent = data.count;
        statAvg.textContent    = data.avg_weight ? data.avg_weight + ' kg' : '—';

        if (data.attendances.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Sin visitas registradas aún.</td></tr>';
        } else {
            tbody.innerHTML = data.attendances.map(a => {
                const rw   = a.weight;
                const iw   = a.ideal_weight;
                const diff = (rw && iw) ? Math.round((rw - iw) * 100) / 100 : null;
                const diffHtml = diff !== null
                    ? (diff > 0
                        ? `<span class="text-red-500">↑ +${diff} kg</span>`
                        : diff < 0
                            ? `<span class="text-green-600">↓ ${diff} kg</span>`
                            : `<span class="text-gray-400">= ideal</span>`)
                    : '<span class="text-gray-300">—</span>';
                return `<tr>
                    <td class="px-5 py-3 font-medium text-gray-800">${a.name}</td>
                    <td class="px-5 py-3 text-gray-500">${a.attended_at}</td>
                    <td class="px-5 py-3 text-right font-semibold ${rw ? 'text-teal-600' : 'text-gray-300'}">${rw ? rw + ' kg' : '—'}</td>
                    <td class="px-5 py-3 text-right text-gray-400">${iw ? iw + ' kg' : '—'}</td>
                    <td class="px-5 py-3 text-right font-semibold">${diffHtml}</td>
                </tr>`;
            }).join('');
        }

        const now = new Date();
        updateEl.textContent = 'Act. ' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ':' + now.getSeconds().toString().padStart(2,'0');
    } catch(e) {}
}

fetchAttendances();
setInterval(fetchAttendances, 4000);
</script>
@endif

@endsection
