@extends('layouts.app')
@section('title', 'Editar Grupo')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.groups.show', $group) }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Editar Grupo</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.groups.update', $group) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del grupo *</label>
                <input type="text" name="name" value="{{ old('name', $group->name) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none text-sm"
                    placeholder="Ej: Grupo Lunes Mañana">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Modalidad</label>
                <div class="flex gap-2">
                    @foreach(['presencial'=>'Presencial','virtual'=>'Virtual','hibrido'=>'Híbrido'] as $val => $label)
                        <label class="relative cursor-pointer flex-1">
                            <input type="radio" name="modality" value="{{ $val }}" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer peer"
                                {{ old('modality', $group->modality ?? 'presencial') === $val ? 'checked' : '' }}>
                            <span class="block text-center px-3 py-2 rounded-lg text-sm font-medium border border-gray-300
                                peer-checked:border-teal-600 peer-checked:bg-teal-600 peer-checked:text-white
                                hover:border-teal-400 transition select-none">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" rows="2"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none text-sm"
                    placeholder="Descripción opcional...">{{ old('description', $group->description) }}</textarea>
            </div>

            {{-- Recurrence --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Repetición</label>
                <select name="recurrence_type" id="rec-type"
                    onchange="updateRecUI()"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm bg-white">
                    <option value="none"    {{ old('recurrence_type', $group->recurrence_type ?? 'none')==='none'    ? 'selected':'' }}>Sin repetición</option>
                    <option value="daily"   {{ old('recurrence_type', $group->recurrence_type ?? 'none')==='daily'   ? 'selected':'' }}>Todos los días</option>
                    <option value="weekly"  {{ old('recurrence_type', $group->recurrence_type ?? 'none')==='weekly'  ? 'selected':'' }}>Todas las semanas</option>
                    <option value="monthly" {{ old('recurrence_type', $group->recurrence_type ?? 'none')==='monthly' ? 'selected':'' }}>Todos los meses</option>
                    <option value="yearly"  {{ old('recurrence_type', $group->recurrence_type ?? 'none')==='yearly'  ? 'selected':'' }}>Todos los años</option>
                </select>
            </div>

            {{-- Meeting days (weekly only) --}}
            <div id="rec-day" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Días de reunión</label>
                @php
                    $defaultDays = $group->meeting_days ?? ($group->meeting_day ? [$group->meeting_day] : []);
                    $selectedDays = old('meeting_days', $defaultDays);
                @endphp
                <div class="flex flex-wrap gap-2">
                    @foreach(['Lunes'=>'Lun','Martes'=>'Mar','Miércoles'=>'Mié','Jueves'=>'Jue','Viernes'=>'Vie','Sábado'=>'Sáb','Domingo'=>'Dom'] as $day => $abbr)
                        <label class="relative cursor-pointer">
                            <input type="checkbox" name="meeting_days[]" value="{{ $day }}"
                                class="absolute inset-0 opacity-0 w-full h-full cursor-pointer peer"
                                {{ in_array($day, $selectedDays) ? 'checked' : '' }}>
                            <span class="block px-3 py-2 rounded-lg text-sm font-medium border border-gray-300
                                peer-checked:bg-teal-600 peer-checked:text-white peer-checked:border-teal-600
                                hover:border-teal-400 transition select-none">{{ $abbr }}</span>
                        </label>
                    @endforeach
                </div>
                @error('meeting_days')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Time + interval (hidden when none) --}}
            <div id="rec-options" class="hidden space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora de inicio</label>
                        <input type="time" name="meeting_time" value="{{ old('meeting_time', $group->meeting_time) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Repetir cada</label>
                        <div class="flex items-center gap-2">
                            <input type="number" name="recurrence_interval" min="1" max="365"
                                value="{{ old('recurrence_interval', $group->recurrence_interval ?? 1) }}"
                                class="w-20 px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                            <span id="rec-unit" class="text-sm text-gray-500">semanas</span>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Finaliza (opcional)</label>
                    <input type="date" name="recurrence_end_date"
                        value="{{ old('recurrence_end_date', $group->recurrence_end_date?->format('Y-m-d')) }}"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                    @error('recurrence_end_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <script>
            const recUnits = { daily: 'días', weekly: 'semanas', monthly: 'meses', yearly: 'años' };
            function updateRecUI() {
                const t = document.getElementById('rec-type').value;
                document.getElementById('rec-day').classList.toggle('hidden', t !== 'weekly');
                document.getElementById('rec-options').classList.toggle('hidden', t === 'none');
                const u = document.getElementById('rec-unit');
                if (u && recUnits[t]) u.textContent = recUnits[t];
            }
            updateRecUI();
            </script>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Coordinadores</label>
                @if($coordinators->isNotEmpty())
                    @php $assignedIds = old('coordinator_ids', $group->coordinators->pluck('id')->toArray()); @endphp
                    <div class="space-y-1">
                        @foreach($coordinators as $coordinator)
                            <label class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="coordinator_ids[]" value="{{ $coordinator->id }}"
                                    {{ in_array($coordinator->id, $assignedIds) ? 'checked' : '' }}
                                    class="rounded text-teal-600">
                                <span class="text-sm text-gray-700">{{ $coordinator->name }}</span>
                                <span class="text-xs text-gray-400">{{ $coordinator->email }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400">No hay coordinadores creados aún.</p>
                @endif
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.groups.show', $group) }}" class="text-gray-600 hover:text-gray-800 px-4 py-2.5 text-sm">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
