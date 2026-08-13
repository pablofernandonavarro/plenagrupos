@extends('layouts.app')
@section('title', 'Usuarios')

@section('content')
<div class="space-y-6">
    {{-- Search --}}
    <form method="GET" action="{{ route('admin.users.index') }}" id="search-form" class="flex gap-2">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                placeholder="Buscar por nombre o email..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none bg-white">
        </div>
        <button type="submit"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-xl transition">
            Buscar
        </button>
        @if(request('search'))
            <a href="{{ route('admin.users.index') }}"
               class="px-4 py-2.5 border border-gray-200 text-gray-500 text-sm rounded-xl hover:bg-gray-50 transition">
                ✕
            </a>
        @endif
    </form>

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
        <div class="flex gap-2 items-center">
            <a href="{{ route('admin.users.trashed') }}" class="text-sm text-gray-400 hover:text-gray-600 underline">
                Ver papelera
            </a>
            <a href="{{ route('admin.users.create', ['role' => 'coordinator']) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Coordinador
            </a>
            <a href="{{ route('admin.users.create', ['role' => 'patient']) }}" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Paciente
            </a>
            <a href="{{ route('admin.users.create', ['role' => 'medico']) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Médico
            </a>
            <a href="{{ route('admin.users.create', ['role' => 'nutricionista']) }}" class="bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Nutricionista
            </a>
        </div>
    </div>

    {{-- Patients --}}
    <details class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" open>
        <summary class="px-5 py-4 cursor-pointer list-none flex items-center justify-between gap-3 hover:bg-gray-50/80 transition [&::-webkit-details-marker]:hidden">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <h2 class="font-semibold text-gray-800">Pacientes ({{ $patients->count() }})</h2>
            </span>
            <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="divide-y divide-gray-50 border-t border-gray-100">
            @forelse($patients as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$user" size="sm" />
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                                @if($user->plan === 'mantenimiento')
                                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Mantenimiento</span>
                                @elseif($user->plan === 'mantenimiento_pleno')
                                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-purple-50 text-purple-700 font-medium">Mant. Pleno</span>
                                @elseif($user->plan === 'descenso')
                                    <span class="text-xs px-1.5 py-0.5 rounded-full bg-sky-50 text-sky-700 font-medium">Descenso</span>
                                @else
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                        class="text-xs px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 font-medium hover:bg-red-100 transition">
                                        ⚠ Sin plan · asignar
                                    </a>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($user->phone)
                            <button type="button" onclick="sendWhatsapp({{ $user->id }}, '{{ addslashes($user->phone) }}', '{{ addslashes($user->name) }}')"
                                class="text-sm text-green-600 hover:underline">Enviar WhatsApp</button>
                        @endif
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Mover este usuario a la papelera?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin pacientes.</p>
            @endforelse
        </div>
    </details>

    {{-- Coordinators --}}
    <details class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" open>
        <summary class="px-5 py-4 cursor-pointer list-none flex items-center justify-between gap-3 hover:bg-gray-50/80 transition [&::-webkit-details-marker]:hidden">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                <h2 class="font-semibold text-gray-800">Coordinadores ({{ $coordinators->count() }})</h2>
            </span>
            <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="divide-y divide-gray-50 border-t border-gray-100">
            @forelse($coordinators as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$user" size="sm" />
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($user->phone)
                            <button type="button" onclick="sendWhatsapp({{ $user->id }}, '{{ addslashes($user->phone) }}', '{{ addslashes($user->name) }}')"
                                class="text-sm text-green-600 hover:underline">Enviar WhatsApp</button>
                        @endif
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Mover este usuario a la papelera?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin coordinadores.</p>
            @endforelse
        </div>
    </details>

    {{-- Médicos --}}
    <details class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <summary class="px-5 py-4 cursor-pointer list-none flex items-center justify-between gap-3 hover:bg-gray-50/80 transition [&::-webkit-details-marker]:hidden">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <h2 class="font-semibold text-gray-800">Médicos ({{ $medicos->count() }})</h2>
            </span>
            <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="divide-y divide-gray-50 border-t border-gray-100">
            @forelse($medicos as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$user" size="sm" />
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.professionals.schedule.edit', $user) }}" class="text-sm text-indigo-600 hover:underline">Horarios</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Mover este usuario a la papelera?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin médicos.</p>
            @endforelse
        </div>
    </details>

    {{-- Nutricionistas --}}
    <details class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <summary class="px-5 py-4 cursor-pointer list-none flex items-center justify-between gap-3 hover:bg-gray-50/80 transition [&::-webkit-details-marker]:hidden">
            <span class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                <h2 class="font-semibold text-gray-800">Nutricionistas ({{ $nutricionistas->count() }})</h2>
            </span>
            <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="divide-y divide-gray-50 border-t border-gray-100">
            @forelse($nutricionistas as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$user" size="sm" />
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.professionals.schedule.edit', $user) }}" class="text-sm text-indigo-600 hover:underline">Horarios</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Mover este usuario a la papelera?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin nutricionistas.</p>
            @endforelse
        </div>
    </details>
</div>

<script>
const whatsappSendUrl = '{{ route('admin.whatsapp.send') }}';
const whatsappCsrf = '{{ csrf_token() }}';

async function sendWhatsapp(userId, phone, name) {
    const confirmedPhone = prompt(`Número de WhatsApp para ${name} (con código de país, ej. 5491122334455):`, phone);
    if (!confirmedPhone) return;

    const text = prompt(`Mensaje para ${name}:`, 'Hola, te escribimos desde Plena Grupos.');
    if (!text) return;

    try {
        const res = await fetch(whatsappSendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': whatsappCsrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ phone: confirmedPhone, text }),
        });
        const data = await res.json();
        if (!res.ok) {
            const validationError = data.errors?.phone?.[0] ?? data.errors?.text?.[0];
            alert('No se pudo enviar: ' + (validationError ?? data.error ?? data.message ?? 'error desconocido'));
            return;
        }
        alert('Mensaje enviado.');
    } catch (e) {
        alert('No se pudo enviar el mensaje (error de red).');
    }
}
</script>
@endsection
