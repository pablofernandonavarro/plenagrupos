@extends('layouts.app')
@section('title', 'Papelera de usuarios')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Papelera de usuarios</h1>
            <p class="text-sm text-gray-500 mt-0.5">Usuarios eliminados. Se purgan automáticamente a los 30 días.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-teal-600 hover:underline">
            ← Volver a usuarios
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="divide-y divide-gray-50">
            @forelse($trashedUsers as $user)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <x-avatar :user="$user" size="sm" />
                        <div>
                            <p class="font-medium text-gray-800 text-sm">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ $user->email }} · {{ ucfirst($user->role) }}
                                · Eliminado: {{ $user->deleted_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('admin.users.restore', $user) }}" method="POST">
                            @csrf
                            <button class="text-sm text-teal-600 hover:underline">Restaurar</button>
                        </form>
                        <form action="{{ route('admin.users.force-delete', $user) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar definitivamente a {{ $user->name }}? Esta acción no se puede deshacer.')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">Eliminar definitivamente</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">
                    <p>La papelera está vacía.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
