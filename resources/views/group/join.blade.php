<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar visita — {{ $group->name }}</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="32x32">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-teal-50 to-green-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-sm text-center">

        <div class="inline-flex items-center justify-center w-16 h-16 bg-teal-600 rounded-2xl mb-6 shadow-lg">
            <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ $group->name }}</h1>
        <p class="text-gray-400 text-sm mb-6">{{ now()->format('d/m/Y') }}</p>

        @if($groupStatus === 'pending')
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <div class="text-3xl mb-2">⏳</div>
                <p class="text-yellow-800 font-semibold">El grupo aún no fue iniciado</p>
                <p class="text-yellow-600 text-sm mt-1">El coordinador debe iniciar la sesión antes de que puedas registrarte.</p>
            </div>

        @elseif($groupStatus === 'closed')
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <div class="text-3xl mb-2">🔒</div>
                <p class="text-red-700 font-semibold">Este grupo está finalizado.</p>
                <p class="text-red-500 text-sm mt-1">Contactá a tu coordinador.</p>
            </div>

        @elseif($alreadyCheckedIn)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                <div class="text-3xl mb-2">✓</div>
                <p class="text-amber-800 font-semibold">Ya registraste tu asistencia hoy</p>
                <p class="text-amber-600 text-sm mt-1">Tu visita de hoy a este grupo ya fue registrada.</p>
                <a href="{{ route('patient.dashboard') }}"
                   class="mt-4 inline-block text-sm text-teal-600 hover:underline">
                    Ir a mi perfil
                </a>
            </div>

        @else
            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-xl p-6">
                <p class="text-gray-600 text-sm mb-2">
                    Hola, <strong>{{ auth()->user()->name }}</strong>
                </p>
                <p class="text-gray-500 text-sm mb-6">
                    Al confirmar se registrará tu visita de hoy al grupo.
                </p>
                <form action="{{ route('group.join.post', $group->qr_token) }}" method="POST">
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
