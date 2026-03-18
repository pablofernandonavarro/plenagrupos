@extends('layouts.app')
@section('title', 'Editar ' . ucfirst($user->role))

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Editar usuario</h1>
            <p class="text-sm text-gray-500">{{ ucfirst($user->role) }} · {{ $user->email }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Opcional">
            </div>

            @if($user->role === 'patient')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Peso ideal (kg)</label>
                <input type="number" step="0.01" min="0" max="300" name="ideal_weight"
                    value="{{ old('ideal_weight', $user->ideal_weight) }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Ej: 70.50">
                @error('ideal_weight')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            @endif

            <div class="border-t pt-4">
                <p class="text-sm font-medium text-gray-700 mb-3">Cambiar contraseña <span class="text-gray-400 font-normal">(dejar vacío para mantener la actual)</span></p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Nueva contraseña</label>
                        <input type="password" name="password"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Mínimo 8 caracteres">
                        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Repetir contraseña">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 px-4 py-2.5 text-sm">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
