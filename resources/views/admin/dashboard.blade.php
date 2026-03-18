@extends('layouts.app')
@section('title', 'Dashboard Admin')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">Panel de Administración</h1>
        <p class="text-gray-500 text-sm mt-1">Bienvenido, {{ auth()->user()->name }}</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Grupos</p>
            <p class="text-3xl font-bold text-teal-600 mt-1">{{ $stats['groups'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Coordinadores</p>
            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['coordinators'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Pacientes</p>
            <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['patients'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Visitas hoy</p>
            <p class="text-3xl font-bold text-purple-600 mt-1">{{ $stats['visits_today'] }}</p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('admin.groups.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white rounded-xl p-5 flex items-center gap-3 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="font-semibold">Nuevo Grupo</span>
        </a>
        <a href="{{ route('admin.users.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white rounded-xl p-5 flex items-center gap-3 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span class="font-semibold">Nuevo Usuario</span>
        </a>
    </div>

    {{-- Groups overview --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Grupos activos</h2>
            <a href="{{ route('admin.groups.index') }}" class="text-sm text-teal-600 hover:underline">Ver todos</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($groups as $group)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $group->name }}</p>
                        <p class="text-xs text-gray-400">{{ $group->patients->count() }} pacientes</p>
                    </div>
                    <a href="{{ route('admin.groups.show', $group) }}" class="text-xs text-teal-600 hover:underline">Ver QR y datos</a>
                </div>
            @empty
                <p class="px-5 py-6 text-center text-gray-400 text-sm">No hay grupos aún.</p>
            @endforelse
        </div>
    </div>

    {{-- Recent attendances --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Últimas visitas</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentAttendances as $att)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $att->user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $att->group->name }} · {{ $att->attended_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($att->weightRecord)
                        <span class="text-sm font-semibold text-teal-600">{{ $att->weightRecord->weight }} kg</span>
                    @else
                        <span class="text-xs text-gray-300">Sin peso</span>
                    @endif
                </div>
            @empty
                <p class="px-5 py-6 text-center text-gray-400 text-sm">Sin visitas aún.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
