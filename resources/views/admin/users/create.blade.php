@extends('layouts.app')
@section('title', 'Crear Usuario')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Crear {{ ucfirst($role) }}</h1>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="role" value="{{ $role }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Foto de perfil</label>
                <input type="file" name="avatar" accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                @error('avatar')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Nombre completo">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="correo@ejemplo.com">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="tel" name="phone" value="{{ old('phone') }}"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Opcional">
            </div>
            @if($role === 'patient')
            {{-- Perfil clínico --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de nacimiento</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Género</label>
                    <select name="gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm bg-white">
                        <option value="">— Sin definir —</option>
                        <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Femenino</option>
                        <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Masculino</option>
                        <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Altura (cm)</label>
                    <input type="number" name="height_cm" min="50" max="250" value="{{ old('height_cm') }}"
                        placeholder="Ej: 165"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Objetivo personal</label>
                <textarea name="personal_goal" rows="2" maxlength="1000"
                    placeholder="¿Qué quiere lograr con el programa?"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm resize-none">{{ old('personal_goal') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan</label>
                <div class="flex gap-2">
                    @foreach(['descenso'=>'Descenso','mantenimiento'=>'Mantenimiento','mantenimiento_pleno'=>'Mant. Pleno'] as $val => $label)
                        <div class="relative flex-1">
                            <input type="radio" id="plan-{{ $val }}" name="plan" value="{{ $val }}"
                                class="sr-only peer"
                                {{ old('plan', 'descenso') === $val ? 'checked' : '' }}>
                            <label for="plan-{{ $val }}"
                                class="block text-center px-2 py-2 rounded-lg text-sm font-medium border border-gray-300
                                peer-checked:border-teal-600 peer-checked:bg-teal-600 peer-checked:text-white
                                hover:border-teal-400 transition select-none cursor-pointer">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Mínimo 8 caracteres">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña *</label>
                <input type="password" name="password_confirmation" required
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                    placeholder="Repite la contraseña">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Crear {{ ucfirst($role) }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-800 px-4 py-2.5 text-sm">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
