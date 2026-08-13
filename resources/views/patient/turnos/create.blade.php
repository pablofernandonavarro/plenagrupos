@extends('layouts.app')
@section('title', 'Sacar turno')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('patient.turnos.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Sacar turno</h1>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex gap-2 mb-4">
        <a href="{{ route('patient.turnos.create', ['specialty' => 'medico']) }}"
            class="flex-1 text-center px-3 py-2.5 rounded-lg text-sm font-medium border transition
            {{ $specialty === 'medico' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 hover:border-indigo-400' }}">
            Médico clínico
        </a>
        <a href="{{ route('patient.turnos.create', ['specialty' => 'nutricionista']) }}"
            class="flex-1 text-center px-3 py-2.5 rounded-lg text-sm font-medium border transition
            {{ $specialty === 'nutricionista' ? 'border-purple-600 bg-purple-600 text-white' : 'border-gray-300 hover:border-purple-400' }}">
            Nutricionista
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        @if($professionals->isEmpty())
            <p class="text-sm text-gray-400">No hay profesionales disponibles para esta especialidad todavía.</p>
        @else
        <form method="POST" action="{{ route('patient.turnos.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Profesional *</label>
                <select name="professional_id" id="professional-select" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white">
                    <option value="">— Elegir profesional —</option>
                    @foreach($professionals as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                <input type="date" id="date-input" min="{{ now()->toDateString() }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Horario disponible *</label>
                <div id="slots-container" class="flex flex-wrap gap-2 min-h-[2.5rem]">
                    <p class="text-xs text-gray-400">Elegí profesional y fecha para ver los horarios disponibles.</p>
                </div>
                <input type="hidden" name="starts_at" id="starts-at-input" required>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Confirmar turno
                </button>
                <a href="{{ route('patient.turnos.index') }}" class="text-gray-600 hover:text-gray-800 px-4 py-2.5 text-sm">Cancelar</a>
            </div>
        </form>
        @endif
    </div>
</div>

<script>
(function () {
    const professionalSelect = document.getElementById('professional-select');
    const dateInput = document.getElementById('date-input');
    const slotsContainer = document.getElementById('slots-container');
    const startsAtInput = document.getElementById('starts-at-input');
    const availableSlotsUrl = '{{ route('patient.turnos.available-slots') }}';
    if (!professionalSelect) return;

    function renderSlots() {
        slotsContainer.innerHTML = '';
        startsAtInput.value = '';
        const professionalId = professionalSelect.value;
        const date = dateInput.value;
        if (!professionalId || !date) {
            slotsContainer.innerHTML = '<p class="text-xs text-gray-400">Elegí profesional y fecha para ver los horarios disponibles.</p>';
            return;
        }
        slotsContainer.innerHTML = '<p class="text-xs text-gray-400">Cargando...</p>';
        fetch(`${availableSlotsUrl}?professional_id=${professionalId}&date=${date}`)
            .then(res => res.json())
            .then(slots => {
                slotsContainer.innerHTML = '';
                if (!slots.length) {
                    slotsContainer.innerHTML = '<p class="text-xs text-gray-400">Sin horarios disponibles ese día.</p>';
                    return;
                }
                slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = slot;
                    btn.className = 'slot-btn px-3 py-1.5 border border-gray-300 rounded-lg text-sm hover:border-teal-400 transition';
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('border-teal-600', 'bg-teal-600', 'text-white'));
                        btn.classList.add('border-teal-600', 'bg-teal-600', 'text-white');
                        startsAtInput.value = `${date} ${slot}:00`;
                    });
                    slotsContainer.appendChild(btn);
                });
            });
    }

    professionalSelect.addEventListener('change', renderSlots);
    dateInput.addEventListener('change', renderSlots);
})();
</script>
@endsection
