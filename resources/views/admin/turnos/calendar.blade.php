@extends('layouts.app')
@section('title', 'Calendario de turnos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-800">{{ auth()->user()->isAdmin() ? 'Calendario de turnos' : 'Mi agenda' }}</h1>
        @if(auth()->user()->isAdmin())
        <div class="flex gap-2">
            <a href="{{ route('admin.turnos.index') }}" class="border border-gray-200 text-gray-600 text-sm font-semibold px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                Ver listado
            </a>
            <a href="{{ route('admin.turnos.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Nuevo turno
            </a>
        </div>
        @endif
    </div>

    <div class="flex items-center gap-4 text-xs text-gray-500">
        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#2563eb"></span> Médico clínico</span>
        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#09cda6"></span> Nutricionista</span>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div id="calendar"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/es.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        initialView: 'timeGridWeek',
        height: 'auto',
        slotMinTime: '07:00:00',
        slotMaxTime: '21:00:00',
        events: function (info, successCallback, failureCallback) {
            fetch(`{{ route('admin.turnos.calendar.events') }}?start=${info.startStr}&end=${info.endStr}`)
                .then(res => res.json())
                .then(successCallback)
                .catch(failureCallback);
        },
    });
    calendar.render();
});
</script>
@endsection
