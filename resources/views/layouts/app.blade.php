<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Plen@') — Grupo Terapéutico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        plena: {
                            50:  '#f0fefb',
                            100: '#ccfaf2',
                            200: '#99f4e6',
                            300: '#5de8d4',
                            400: '#2dd4bf',
                            500: '#09cda6',
                            600: '#07b394',
                            700: '#068a72',
                            800: '#086b59',
                            900: '#085749',
                        },
                        brand: {
                            dark:  '#252440',
                            dark2: '#150480',
                            cream: '#ECD49A',
                            sage:  '#42615B',
                        }
                    },
                    fontFamily: {
                        sans:     ['Lato', 'sans-serif'],
                        heading:  ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Lato', sans-serif; }
        h1, h2, h3, h4, h5, h6, .font-bold, .font-semibold { font-family: 'Poppins', sans-serif; }

        /* Map teal utilities to brand color */
        .bg-teal-600  { background-color: #09cda6 !important; }
        .bg-teal-700  { background-color: #07b394 !important; }
        .hover\:bg-teal-700:hover { background-color: #07b394 !important; }
        .text-teal-600 { color: #09cda6 !important; }
        .text-teal-700 { color: #07b394 !important; }
        .border-teal-500 { border-color: #09cda6 !important; }
        .ring-teal-500  { --tw-ring-color: #09cda6 !important; }
        .focus\:ring-teal-500:focus { --tw-ring-color: #09cda6 !important; }
        .bg-teal-50 { background-color: #f0fefb !important; }
        .text-teal-800 { color: #086b59 !important; }
        .border-teal-200 { border-color: #99f4e6 !important; }
        .border-teal-400 { border-color: #2dd4bf !important; }
        .hover\:border-teal-400:hover { border-color: #2dd4bf !important; }
        .hover\:text-teal-600:hover { color: #09cda6 !important; }
        .text-teal-600 { color: #09cda6 !important; }
        .bg-teal-100 { background-color: #ccfaf2 !important; }
        .text-teal-900 { color: #085749 !important; }
        .divide-teal-100 > * + * { border-color: #ccfaf2 !important; }
        .focus\:ring-2:focus { box-shadow: 0 0 0 2px #09cda6 !important; }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4,0,0.6,1) infinite; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Navigation --}}
    <nav style="background-color: #252440;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                {{-- Logo --}}
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background-color: #09cda6;">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-white text-lg" style="font-family: 'Poppins', sans-serif; letter-spacing: -0.01em;">
                            Plen<span style="color: #09cda6;">@</span>
                        </span>
                    </a>
                </div>

                @auth
                {{-- Desktop nav links --}}
                <div class="hidden sm:flex items-center gap-4">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-300 transition" style="hover: color:#09cda6"
                           onmouseover="this.style.color='#09cda6'" onmouseout="this.style.color='#d1d5db'">Dashboard</a>
                        <a href="{{ route('admin.groups.index') }}" class="text-sm text-gray-300 transition"
                           onmouseover="this.style.color='#09cda6'" onmouseout="this.style.color='#d1d5db'">Grupos</a>
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-300 transition"
                           onmouseover="this.style.color='#09cda6'" onmouseout="this.style.color='#d1d5db'">Usuarios</a>
                    @elseif(auth()->user()->isCoordinator())
                        <a href="{{ route('coordinator.dashboard') }}" class="text-sm text-gray-300 transition"
                           onmouseover="this.style.color='#09cda6'" onmouseout="this.style.color='#d1d5db'">Grupos</a>
                        <a href="{{ route('coordinator.patients.index') }}" class="text-sm text-gray-300 transition"
                           onmouseover="this.style.color='#09cda6'" onmouseout="this.style.color='#d1d5db'">Pacientes</a>
                    @else
                        <a href="{{ route('patient.dashboard') }}" class="text-sm text-gray-300 transition"
                           onmouseover="this.style.color='#09cda6'" onmouseout="this.style.color='#d1d5db'">Mi perfil</a>
                    @endif

                    <div class="flex items-center gap-2 border-l border-white/20 pl-4 ml-2">
                        <span class="text-sm font-medium text-white">{{ auth()->user()->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if(auth()->user()->isAdmin()) bg-purple-500/30 text-purple-200
                            @elseif(auth()->user()->isCoordinator()) bg-blue-500/30 text-blue-200
                            @else bg-white/20 text-white/90 @endif">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-white/50 hover:text-red-400 ml-2 transition">Salir</button>
                        </form>
                    </div>
                </div>

                {{-- Mobile: hamburger --}}
                <div class="sm:hidden flex items-center">
                    <button id="mobile-menu-btn" type="button"
                        class="p-2 rounded-lg text-white/70 hover:text-white focus:outline-none transition"
                        aria-label="Abrir menú">
                        <svg id="icon-open" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg id="icon-close" class="w-6 h-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                @endauth
            </div>
        </div>

        @auth
        {{-- Mobile dropdown menu --}}
        <div id="mobile-menu" class="sm:hidden hidden border-t border-white/10" style="background-color: #1e1d35;">
            <div class="px-4 py-3 space-y-1">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-300 hover:bg-white/10 hover:text-white transition">Dashboard</a>
                    <a href="{{ route('admin.groups.index') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-300 hover:bg-white/10 hover:text-white transition">Grupos</a>
                    <a href="{{ route('admin.users.index') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-300 hover:bg-white/10 hover:text-white transition">Usuarios</a>
                @elseif(auth()->user()->isCoordinator())
                    <a href="{{ route('coordinator.dashboard') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-300 hover:bg-white/10 hover:text-white transition">Grupos</a>
                    <a href="{{ route('coordinator.patients.index') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-300 hover:bg-white/10 hover:text-white transition">Pacientes</a>
                @else
                    <a href="{{ route('patient.dashboard') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-300 hover:bg-white/10 hover:text-white transition">Mi perfil</a>
                @endif

                <div class="border-t border-white/10 pt-3 mt-2 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            @if(auth()->user()->isAdmin()) bg-purple-500/30 text-purple-200
                            @elseif(auth()->user()->isCoordinator()) bg-blue-500/30 text-blue-200
                            @else bg-white/20 text-white/90 @endif">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-400 hover:text-red-300 px-3 py-2 transition">Salir</button>
                    </form>
                </div>
            </div>
        </div>
        @endauth
    </nav>

    {{-- Flash Messages --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        @foreach(['success' => 'green', 'error' => 'red', 'info' => 'blue', 'warning' => 'yellow'] as $type => $color)
            @if(session($type))
                <div class="mb-4 p-4 rounded-lg bg-{{ $color }}-50 border border-{{ $color }}-200 text-{{ $color }}-800 text-sm">
                    {{ session($type) }}
                </div>
            @endif
        @endforeach
    </div>

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <footer class="mt-16 py-6 text-center text-xs text-gray-400 border-t border-gray-100">
        <span style="font-family: 'Poppins', sans-serif; font-weight: 600; color: #252440;">Plen<span style="color: #09cda6;">@</span></span>
        &nbsp;&mdash;&nbsp;Grupo Terapéutico para el Descenso de Peso &copy; {{ date('Y') }}
    </footer>

    <script>
        const btn  = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        const iconOpen  = document.getElementById('icon-open');
        const iconClose = document.getElementById('icon-close');
        if (btn) {
            btn.addEventListener('click', () => {
                const isHidden = menu.classList.toggle('hidden');
                iconOpen.classList.toggle('hidden', !isHidden);
                iconClose.classList.toggle('hidden', isHidden);
            });
        }
    </script>

</body>
</html>
