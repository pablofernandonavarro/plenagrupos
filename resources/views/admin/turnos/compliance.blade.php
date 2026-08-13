@extends('layouts.app')
@section('title', 'Cumplimiento de turnos')

@section('content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.turnos.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Cumplimiento de turnos — {{ now()->translatedFormat('F Y') }}</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-left text-gray-500" style="background:#f8fafc">
                    <th class="px-5 py-3 font-medium">Paciente</th>
                    <th class="px-5 py-3 font-medium">Médico clínico</th>
                    <th class="px-5 py-3 font-medium">Nutricionista</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($patients as $row)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$row->user" size="sm" />
                                <span class="font-medium text-gray-800">{{ $row->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            @if($row->medico_done)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700 font-medium">✓ Cumplido</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            @if($row->nutricionista_done)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-700 font-medium">✓ Cumplido</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-4 text-gray-400">Sin pacientes activos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
