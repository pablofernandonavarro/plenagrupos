@extends('layouts.app')
@section('title', 'Plantillas de WhatsApp')

@section('content')
<div class="max-w-2xl space-y-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.whatsapp.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Plantillas de WhatsApp</h1>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700">
        Variables disponibles: <code class="bg-white/60 px-1 rounded">{paciente}</code> <code class="bg-white/60 px-1 rounded">{profesional}</code>
        <code class="bg-white/60 px-1 rounded">{especialidad}</code> <code class="bg-white/60 px-1 rounded">{fecha}</code> <code class="bg-white/60 px-1 rounded">{hora}</code>
        <code class="bg-white/60 px-1 rounded">{link_confirmar}</code> <code class="bg-white/60 px-1 rounded">{link_cancelar}</code>.
        Desactivá una plantilla para que ese WhatsApp deje de enviarse.
    </div>

    <form method="POST" action="{{ route('admin.whatsapp.templates.save') }}" class="space-y-4">
        @csrf

        @php
            $labels = [
                'appointment_booked' => 'Turno reservado',
                'appointment_reminder' => 'Recordatorio (un día antes)',
                'appointment_cancelled' => 'Turno cancelado',
            ];
        @endphp

        @foreach($labels as $key => $label)
            @php $template = $templates->get($key); @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between" style="background:#f8fafc">
                    <p class="text-sm font-semibold text-gray-700">{{ $label }}</p>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" name="active[{{ $key }}]" value="1" class="sr-only peer"
                                {{ ($template?->active ?? true) ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-gray-200 rounded-full peer-checked:bg-teal-500 transition"></div>
                            <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition peer-checked:translate-x-4"></div>
                        </div>
                        <span class="text-xs text-gray-600">Activa</span>
                    </label>
                </div>
                <div class="p-5">
                    <textarea name="body[{{ $key }}]" rows="4" maxlength="1000" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none">{{ old("body.{$key}", $template?->body) }}</textarea>
                </div>
            </div>
        @endforeach

        <div class="pt-2">
            <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                Guardar plantillas
            </button>
        </div>
    </form>
</div>
@endsection
