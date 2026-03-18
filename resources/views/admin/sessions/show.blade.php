@extends('layouts.app')
@section('title', $session->name)

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.sessions.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $session->name }}</h1>
                <p class="text-sm text-gray-500">{{ $session->group->name }} · {{ $session->session_date->format('d \d\e F, Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-sm font-medium {{ $session->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $session->status === 'active' ? 'Activa' : 'Cerrada' }}
            </span>
            <form action="{{ route('admin.sessions.toggle', $session) }}" method="POST">
                @csrf
                <button class="text-sm border border-gray-300 px-3 py-1.5 rounded-lg hover:bg-gray-50 transition">
                    {{ $session->status === 'active' ? 'Cerrar sesión' : 'Reabrir sesión' }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- QR Code --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
            <h2 class="font-semibold text-gray-800 mb-4">Código QR de Asistencia</h2>
            <div class="inline-block p-4 bg-white border-2 border-gray-100 rounded-xl shadow-inner">
                {!! $qrCode !!}
            </div>
            <p class="text-xs text-gray-400 mt-4">Los pacientes escanean este QR para registrar asistencia</p>
            <div class="mt-4 p-2 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 break-all">{{ $joinUrl }}</p>
            </div>
            <a href="{{ $joinUrl }}" target="_blank"
                class="mt-3 inline-block text-sm text-teal-600 hover:underline">
                Abrir enlace directo
            </a>
        </div>

        {{-- Stats --}}
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
                    <p class="text-3xl font-bold text-teal-600">{{ $session->attendances->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">Asistentes</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
                    <p class="text-3xl font-bold text-blue-600">{{ $session->weightRecords->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">Pesos registrados</p>
                </div>
                @if($avgWeight)
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center col-span-2">
                    <p class="text-3xl font-bold text-green-600">{{ number_format($avgWeight, 1) }} kg</p>
                    <p class="text-xs text-gray-500 mt-1">Peso promedio</p>
                </div>
                @endif
            </div>

            {{-- Attendees list --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="px-4 py-3 border-b border-gray-50">
                    <h3 class="text-sm font-semibold text-gray-700">Asistentes</h3>
                </div>
                <div class="divide-y divide-gray-50 max-h-56 overflow-y-auto">
                    @forelse($session->attendances as $attendance)
                        <div class="px-4 py-2 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $attendance->user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $attendance->checked_in_at->format('H:i') }}</p>
                            </div>
                            @php $wr = $session->weightRecords->firstWhere('user_id', $attendance->user_id); @endphp
                            @if($wr)
                                <span class="text-sm font-semibold text-teal-600">{{ $wr->weight }} kg</span>
                            @else
                                <span class="text-xs text-gray-400">Sin peso</span>
                            @endif
                        </div>
                    @empty
                        <p class="px-4 py-6 text-center text-gray-400 text-sm">Sin asistentes aún.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
