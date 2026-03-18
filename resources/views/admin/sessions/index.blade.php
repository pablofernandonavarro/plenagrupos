@extends('layouts.app')
@section('title', 'Sesiones')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Sesiones</h1>
        <a href="{{ route('admin.sessions.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition">
            + Nueva sesión
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="divide-y divide-gray-50">
            @forelse($sessions as $session)
                <div class="px-5 py-4 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800">{{ $session->name }}</p>
                        <p class="text-sm text-gray-500">{{ $session->group->name }} · {{ $session->session_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $session->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $session->status === 'active' ? 'Activa' : 'Cerrada' }}
                        </span>
                        <a href="{{ route('admin.sessions.show', $session) }}" class="text-sm text-teal-600 hover:underline">Ver QR</a>
                        <form action="{{ route('admin.sessions.destroy', $session) }}" method="POST" onsubmit="return confirm('¿Eliminar sesión?')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="px-5 py-12 text-center text-gray-400">No hay sesiones creadas.</p>
            @endforelse
        </div>
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
@endsection
