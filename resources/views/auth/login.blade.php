<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Plena Grupos</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="32x32">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-teal-50 to-green-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-teal-600 rounded-2xl mb-4 shadow-lg">
                <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Plena Grupos</h1>
            <p class="text-gray-500 text-sm mt-1">Grupo Terapéutico para el Descenso de Peso</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Iniciar sesión</h2>

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}{{ request()->has('redirect') ? '?redirect='.urlencode(request('redirect')) : '' }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition text-sm"
                        placeholder="correo@ejemplo.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition text-sm"
                        placeholder="••••••••">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="rounded text-teal-600">
                    <label for="remember" class="ml-2 text-sm text-gray-600">Recordarme</label>
                </div>
                <button type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                    Ingresar
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}{{ request()->has('redirect') ? '?redirect='.urlencode(request('redirect')) : '' }}"
                    class="text-teal-600 font-medium hover:underline">Regístrate</a>
            </p>
        </div>
    </div>

</body>
</html>
