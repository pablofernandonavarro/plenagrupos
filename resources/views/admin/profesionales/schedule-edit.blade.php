@extends('layouts.app')
@section('title', 'Horario de ' . $professional->name)

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.professionals.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $professional->name }}</h1>
            <p class="text-sm text-gray-400">{{ ucfirst($professional->role) }} · {{ $professional->email }}</p>
        </div>
    </div>

    {{-- Horario semanal --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100" style="background:#f8fafc">
            <p class="text-sm font-semibold text-gray-700">Horario de atención</p>
        </div>
        <form method="POST" action="{{ route('admin.professionals.schedule.update', $professional) }}" class="p-5 space-y-4">
            @csrf
            @method('PUT')

            <div id="schedule-blocks" class="space-y-3">
                @forelse($schedules as $i => $schedule)
                    <div class="schedule-row flex flex-wrap items-center gap-2 bg-gray-50 rounded-lg p-3">
                        <select name="blocks[{{ $i }}][day_of_week]" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                            @foreach($days as $day)
                                <option value="{{ $day }}" {{ $schedule->day_of_week === $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                        <input type="time" name="blocks[{{ $i }}][start_time]" value="{{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}" required
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <span class="text-xs text-gray-400">a</span>
                        <input type="time" name="blocks[{{ $i }}][end_time]" value="{{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }}" required
                            class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <div class="flex items-center gap-1">
                            <input type="number" name="blocks[{{ $i }}][slot_duration_minutes]" min="5" max="240" step="5"
                                value="{{ $schedule->slot_duration_minutes }}" required
                                class="w-16 px-2 py-2 border border-gray-300 rounded-lg text-sm text-center">
                            <span class="text-xs text-gray-500">min/turno</span>
                        </div>
                        <button type="button" onclick="this.closest('.schedule-row').remove()" class="ml-auto text-sm text-red-400 hover:text-red-600">Quitar</button>
                    </div>
                @empty
                @endforelse
            </div>

            <button type="button" id="add-block-btn" class="text-sm text-teal-600 hover:underline">+ Agregar bloque</button>

            <template id="schedule-row-template">
                <div class="schedule-row flex flex-wrap items-center gap-2 bg-gray-50 rounded-lg p-3">
                    <select name="blocks[__INDEX__][day_of_week]" class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                        @foreach($days as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                    <input type="time" name="blocks[__INDEX__][start_time]" value="09:00" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <span class="text-xs text-gray-400">a</span>
                    <input type="time" name="blocks[__INDEX__][end_time]" value="13:00" required class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <div class="flex items-center gap-1">
                        <input type="number" name="blocks[__INDEX__][slot_duration_minutes]" min="5" max="240" step="5" value="30" required
                            class="w-16 px-2 py-2 border border-gray-300 rounded-lg text-sm text-center">
                        <span class="text-xs text-gray-500">min/turno</span>
                    </div>
                    <button type="button" onclick="this.closest('.schedule-row').remove()" class="ml-auto text-sm text-red-400 hover:text-red-600">Quitar</button>
                </div>
            </template>

            <div class="pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Guardar horario
                </button>
            </div>
        </form>
    </div>

    {{-- Ausencias / vacaciones --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100" style="background:#f8fafc">
            <p class="text-sm font-semibold text-gray-700">Ausencias y vacaciones</p>
        </div>

        <div class="divide-y divide-gray-50">
            @forelse($unavailabilities as $u)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-800">
                            {{ $u->start_date->format('d/m/Y') }}
                            @if(! $u->start_date->equalTo($u->end_date)) — {{ $u->end_date->format('d/m/Y') }} @endif
                            @if($u->start_time)
                                <span class="text-xs text-gray-400">({{ \Illuminate\Support\Carbon::parse($u->start_time)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($u->end_time)->format('H:i') }})</span>
                            @else
                                <span class="text-xs px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">Día completo</span>
                            @endif
                        </p>
                        @if($u->reason)<p class="text-xs text-gray-400">{{ $u->reason }}</p>@endif
                    </div>
                    <form action="{{ route('admin.professionals.unavailabilities.destroy', $u) }}" method="POST" onsubmit="return confirm('¿Eliminar esta ausencia?')">
                        @csrf @method('DELETE')
                        <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                    </form>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin ausencias registradas.</p>
            @endforelse
        </div>

        <form method="POST" action="{{ route('admin.professionals.unavailabilities.store', $professional) }}" class="p-5 border-t border-gray-100 space-y-3">
            @csrf
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                    <input type="date" name="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                    <input type="date" name="end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Desde (hora, opcional)</label>
                    <input type="time" name="start_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hasta (hora, opcional)</label>
                    <input type="time" name="end_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>
            <p class="text-xs text-gray-400">Dejá las horas vacías para bloquear el/los día(s) completo(s).</p>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Motivo (opcional)</label>
                <input type="text" name="reason" maxlength="255" placeholder="Ej: Vacaciones"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Agregar ausencia
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    const container = document.getElementById('schedule-blocks');
    const template = document.getElementById('schedule-row-template');
    let index = {{ $schedules->count() }};

    document.getElementById('add-block-btn').addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', index++);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        container.appendChild(wrapper.firstElementChild);
    });
})();
</script>
@endsection
