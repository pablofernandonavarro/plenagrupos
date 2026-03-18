@extends('layouts.app')
@section('title', 'Usuarios')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.create', ['role' => 'coordinator']) }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Coordinador
            </a>
            <a href="{{ route('admin.users.create', ['role' => 'patient']) }}" class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
                + Paciente
            </a>
        </div>
    </div>

    {{-- Coordinators --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <h2 class="font-semibold text-gray-800">Coordinadores ({{ $coordinators->count() }})</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($coordinators as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin coordinadores.</p>
            @endforelse
        </div>
    </div>

    {{-- Patients --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500"></span>
            <h2 class="font-semibold text-gray-800">Pacientes ({{ $patients->count() }})</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($patients as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                        <p class="text-xs text-gray-400">{{ $user->email }} @if($user->phone)· {{ $user->phone }}@endif</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-sm text-teal-600 hover:underline">Editar</a>
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-4 text-sm text-gray-400">Sin pacientes.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
