@extends('layouts.app')
@section('title', 'Mi Perfil')

@section('content')
<div class="max-w-md">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Mi Perfil</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">

        {{-- Current avatar --}}
        <div class="flex flex-col items-center gap-3 mb-6">
            <x-avatar :user="auth()->user()" size="lg" />
            <div class="text-center">
                <p class="font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-400">{{ auth()->user()->email }}</p>
                <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-medium">Coordinador</span>
            </div>
        </div>

        {{-- Upload form --}}
        <form action="{{ route('coordinator.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Foto de perfil</label>
                <input type="file" name="avatar" accept="image/*"
                    class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                <p class="text-xs text-gray-400 mt-1">JPG, PNG o GIF. Máx. 2 MB.</p>
                @error('avatar')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit"
                class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                Guardar foto
            </button>
        </form>
    </div>
</div>
@endsection
