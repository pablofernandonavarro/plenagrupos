@extends('layouts.app')
@section('title', 'Papelera de grupos')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Papelera de grupos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Grupos eliminados. Se purgan automáticamente a los 30 días.</p>
        </div>
        <a href="{{ route('admin.groups.index') }}" class="text-sm text-teal-600 hover:underline">
            ← Volver a grupos
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="divide-y divide-gray-50">
            @forelse($trashedGroups as $group)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $group->name }}</p>
                        <p class="text-xs text-gray-400">
                            Eliminado: {{ $group->deleted_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('admin.groups.restore', $group) }}" method="POST">
                            @csrf
                            <button class="text-sm text-teal-600 hover:underline">Restaurar</button>
                        </form>
                        <form action="{{ route('admin.groups.force-delete', $group) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar definitivamente el grupo «{{ addslashes($group->name) }}»? Esta acción no se puede deshacer.')">
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
