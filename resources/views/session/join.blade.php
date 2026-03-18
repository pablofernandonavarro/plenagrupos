<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unirse a Sesión — Plena Grupos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-teal-50 to-green-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm text-center">

        <div class="inline-flex items-center justify-center w-16 h-16 bg-teal-600 rounded-2xl mb-6 shadow-lg">
            <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ $session->name }}</h1>
        <p class="text-gray-500 text-sm mb-2">{{ $session->group->name }}</p>
        <p class="text-gray-400 text-xs mb-6">{{ $session->session_date->format('d/m/Y') }}</p>

        @if($sessionClosed ?? false)
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <p class="text-red-700 font-medium">Esta sesión está cerrada.</p>
                <p class="text-red-500 text-sm mt-1">Contacta a tu coordinador para más información.</p>
            </div>

        @elseif($alreadyJoined)
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-4">
                <p class="text-green-700 font-semibold text-lg">¡Ya estás registrado!</p>
                <p class="text-green-600 text-sm mt-1">Tu asistencia fue confirmada.</p>
            </div>
            <a href="{{ route('patient.weight.create', ['session' => $session->id]) }}"
                class="block w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-3 rounded-xl transition">
                Registrar mi peso
            </a>
            <a href="{{ route('patient.dashboard') }}" class="block mt-3 text-sm text-gray-500 hover:text-gray-700">
                Ir a mi perfil
            </a>

        @else
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-4">
                <p class="text-gray-600 text-sm mb-6">
                    Al confirmar, tu asistencia quedará registrada en esta sesión grupal.
                </p>
                <form action="{{ route('session.join.post', $session->qr_token) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 rounded-xl transition text-base">
                        Confirmar asistencia
                    </button>
                </form>
            </div>
        @endif

    </div>

</body>
</html>
