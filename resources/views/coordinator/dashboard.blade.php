@extends('layouts.app')
@section('title', 'Mis Grupos')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Mis Grupos</h1>
        <p class="text-gray-500 text-sm mt-1">Bienvenido, {{ auth()->user()->name }}</p>
    </div>

    {{-- Search & filter --}}
    <form method="GET" action="{{ route('coordinator.dashboard') }}" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Buscar grupo..."
                class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 outline-none">
        </div>
        <div class="flex gap-2">
            @foreach([''=>'Todos', 'active'=>'Activos', 'closed'=>'Finalizados'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                    class="px-4 py-2.5 rounded-lg text-sm font-medium border transition
                        {{ request('status', '') === $val
                            ? 'bg-teal-600 text-white border-teal-600'
                            : 'bg-white text-gray-600 border-gray-300 hover:border-teal-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    @forelse($groups as $group)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div>
                        <h2 class="font-semibold text-gray-800">{{ $group->name }}</h2>
                        @if($group->meeting_day || $group->meeting_time)
                            <p class="text-xs text-teal-600 font-medium mt-0.5">
                                {{ $group->meeting_day }}{{ $group->meeting_day && $group->meeting_time ? ' · ' : '' }}{{ $group->meeting_time ? substr($group->meeting_time, 0, 5) . ' hs' : '' }}
                            </p>
                        @endif
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full font-medium {{ $group->active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                        {{ $group->active ? 'Abierto' : 'Cerrado' }}
                    </span>
                </div>
                <div class="flex gap-2">
                    @if($group->active)
                    <form action="{{ route('coordinator.groups.toggle', $group) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="text-sm font-semibold px-4 py-1.5 rounded-lg transition border border-red-300 text-red-600 hover:bg-red-50">
                            Finalizar grupo
                        </button>
                    </form>
                    @else
                        <span class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-400">Finalizado</span>
                    @endif
                    <a href="{{ route('coordinator.groups.show', $group) }}"
                        class="text-sm bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-lg transition">
                        Ver detalle
                    </a>
                </div>
            </div>

            <div class="p-5 grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-2xl font-bold text-teal-600">{{ $group->patients_count ?? $group->patients->count() }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pacientes</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-600">{{ $group->attendances_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Total visitas</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600">{{ $group->weight_records_count ?? 0 }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Pesos registrados</p>
                </div>
            </div>

            {{-- QR --}}
            <div class="px-5 pb-5 flex justify-center">
                <div class="text-center">
                    <div class="inline-block p-2 border border-gray-100 rounded-lg shadow-sm">
                        {!! $group->qrSvg !!}
                    </div>
                    <p class="text-xs text-gray-400 mt-2">QR del grupo</p>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl p-12 text-center text-gray-400">
            <p>No tenés grupos asignados aún.</p>
        </div>
    @endforelse
</div>
@endsection
