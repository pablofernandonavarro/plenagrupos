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
                    <span class="text-xs px-2 py-1 rounded-full font-medium {{ $group->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ $group->active ? 'Abierto' : 'Cerrado' }}
                    </span>
                    @if($group->active)
                        <span class="flex items-center gap-1 text-xs text-green-600">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            En vivo
                        </span>
                    @endif
                </div>
                @if($group->meeting_day || $group->meeting_time)
                    <p class="text-sm text-teal-600 font-medium mt-0.5">
                        {{ $group->meeting_day }}{{ $group->meeting_day && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? substr($group->meeting_time, 0, 5) . ' hs' : '' }}
                    </p>
                @endif
            </div>
        </div>
        @if($group->active)
        <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST" class="shrink-0">
            @csrf
            <button type="submit"
                class="text-sm font-semibold px-4 py-2 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                Finalizar
            </button>
        </form>
        @else
            <span class="text-xs px-4 py-2 rounded-lg border border-gray-200 text-gray-400 shrink-0">Finalizado</span>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p id="stat-count" class="text-2xl sm:text-3xl font-bold text-teal-600">{{ $totalVisits }}</p>
            <p class="text-xs text-gray-500 mt-1">Asistentes hoy</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $group->patients->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Pacientes</p>
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
                            <th class="px-5 py-3 text-right">Mantenimiento</th>
                            <th class="px-5 py-3 text-right">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($attendances as $att)
                            @php
                                $pivot = $group->patients->firstWhere('id', $att->user_id)?->pivot;
                                $mw    = $pivot?->maintenance_weight;
                                $rw    = $att->weightRecord?->weight;
                                $diff  = ($mw && $rw) ? round($rw - $mw, 2) : null;
                            @endphp
                            <tr>
                                <td class="px-5 py-3 font-medium text-gray-800">{{ $att->user->name }}</td>
                                <td class="px-5 py-3 text-right font-semibold {{ $rw ? 'text-teal-600' : 'text-gray-300' }}">
                                    {{ $rw ? $rw . ' kg' : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right text-gray-500">
                                    {{ $mw ? $mw . ' kg' : '—' }}
                                </td>
                                <td class="px-5 py-3 text-right font-semibold
                                    {{ $diff === null ? 'text-gray-300' : ($diff > 0 ? 'text-red-500' : ($diff < 0 ? 'text-green-600' : 'text-gray-500')) }}">
                                    @if($diff !== null)
                                        {{ $diff > 0 ? '+' : '' }}{{ $diff }} kg
                                    @else
                                        —
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
                        $pivot = $group->patients->firstWhere('id', $att->user_id)?->pivot;
                        $mw    = $pivot?->maintenance_weight;
                        $rw    = $att->weightRecord?->weight;
                        $diff  = ($mw && $rw) ? round($rw - $mw, 2) : null;
                    @endphp
                    <div class="px-5 py-3">
                        <p class="font-medium text-gray-800 text-sm mb-2">{{ $att->user->name }}</p>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                <p class="font-semibold {{ $rw ? 'text-teal-600' : 'text-gray-300' }}">{{ $rw ? $rw . ' kg' : '—' }}</p>
                                <p class="text-gray-400 mt-0.5">Registrado</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                <p class="font-semibold text-gray-600">{{ $mw ? $mw . ' kg' : '—' }}</p>
                                <p class="text-gray-400 mt-0.5">Mant.</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                <p class="font-semibold {{ $diff === null ? 'text-gray-300' : ($diff > 0 ? 'text-red-500' : ($diff < 0 ? 'text-green-600' : 'text-gray-500')) }}">
                                    @if($diff !== null){{ $diff > 0 ? '+' : '' }}{{ $diff }} kg@else—@endif
                                </p>
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

    {{-- Pesos de mantenimiento por paciente --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Peso de mantenimiento</h2>
            <p class="text-xs text-gray-400 mt-0.5">Peso objetivo de cada paciente en este grupo.</p>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($group->patients as $patient)
                <div class="px-5 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $patient->name }}</p>
                        @if($patient->pivot->maintenance_weight)
                            <p class="text-xs text-teal-600">{{ $patient->pivot->maintenance_weight }} kg</p>
                        @else
                            <p class="text-xs text-gray-400">Sin peso asignado</p>
                        @endif
                    </div>
                    <form action="{{ route('coordinator.groups.maintenance', $group) }}" method="POST"
                          class="flex items-center gap-2 shrink-0">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $patient->id }}">
                        <input type="number" name="maintenance_weight"
                               value="{{ $patient->pivot->maintenance_weight }}"
                               step="0.1" min="1" max="300"
                               placeholder="kg"
                               class="w-20 px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-teal-500 outline-none">
                        <button type="submit"
                                class="text-xs bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                            Guardar
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin pacientes en este grupo.</p>
            @endforelse
        </div>
    </div>

</div>

@if($group->active)
<script>
const liveUrl  = '{{ route('coordinator.groups.live', $group) }}';
const listEl   = document.getElementById('live-list');
const countEl  = document.getElementById('stat-count');
const updateEl = document.getElementById('last-update');

const maintenanceWeights = {
    @foreach($group->patients as $p)
        {{ $p->id }}: {{ $p->pivot->maintenance_weight ?? 'null' }},
    @endforeach
};

function diff(rw, mw) {
    if (rw === null || mw === null) return { text: '—', color: 'text-gray-300' };
    const d = Math.round((rw - mw) * 100) / 100;
    const color = d > 0 ? 'text-red-500' : (d < 0 ? 'text-green-600' : 'text-gray-500');
    return { text: (d > 0 ? '+' : '') + d + ' kg', color };
}

function renderRow(a) {
    const mw   = maintenanceWeights[a.id] ?? null;
    const d    = diff(a.weight, mw);
    const rw   = a.weight;

    // Mobile card (visible on small screens only via inline style trick — we use a div approach)
    return `
    <div class="px-5 py-3 border-b border-gray-50 last:border-0">
        <p class="font-medium text-gray-800 text-sm mb-2">${a.name}</p>
        <div class="grid grid-cols-3 gap-2 text-xs sm:hidden">
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-semibold ${rw ? 'text-teal-600' : 'text-gray-300'}">${rw ? rw + ' kg' : '—'}</p>
                <p class="text-gray-400 mt-0.5">Registrado</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-semibold text-gray-600">${mw ? mw + ' kg' : '—'}</p>
                <p class="text-gray-400 mt-0.5">Mant.</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-2 text-center">
                <p class="font-semibold ${d.color}">${d.text}</p>
                <p class="text-gray-400 mt-0.5">Dif.</p>
            </div>
        </div>
        <div class="hidden sm:grid grid-cols-4 gap-2 text-sm items-center">
            <span></span>
            <span class="text-right font-semibold ${rw ? 'text-teal-600' : 'text-gray-300'}">${rw ? rw + ' kg' : '—'}</span>
            <span class="text-right text-gray-500">${mw ? mw + ' kg' : '—'}</span>
            <span class="text-right font-semibold ${d.color}">${d.text}</span>
        </div>
    </div>`;
}

function renderHeader() {
    return `<div class="hidden sm:grid grid-cols-4 gap-2 px-5 py-2 text-xs text-gray-400 uppercase tracking-wide bg-gray-50">
        <span>Paciente</span>
        <span class="text-right">Peso registrado</span>
        <span class="text-right">Mantenimiento</span>
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
