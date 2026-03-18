<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Plena Grupos') — Grupo Terapéutico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#f0fdf4', 100:'#dcfce7', 500:'#22c55e', 600:'#16a34a', 700:'#15803d' },
                        plena: { 500:'#0d9488', 600:'#0f766e', 700:'#115e59' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Navigation --}}
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                {{-- Logo --}}
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-teal-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="font-bold text-gray-800 text-lg">Plena Grupos</span>
                    </a>
                </div>

                @auth
                {{-- Desktop nav links --}}
                <div class="hidden sm:flex items-center gap-4">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-teal-600">Dashboard</a>
                        <a href="{{ route('admin.groups.index') }}" class="text-sm text-gray-600 hover:text-teal-600">Grupos</a>
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-teal-600">Usuarios</a>
                    @elseif(auth()->user()->isCoordinator())
                        <a href="{{ route('coordinator.dashboard') }}" class="text-sm text-gray-600 hover:text-teal-600">Dashboard</a>
                    @else
                        <a href="{{ route('patient.dashboard') }}" class="text-sm text-gray-600 hover:text-teal-600">Mi perfil</a>
                    @endif

                    <div class="flex items-center gap-2 border-l pl-4 ml-2">
                        <span class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            @if(auth()->user()->isAdmin()) bg-purple-100 text-purple-700
                            @elseif(auth()->user()->isCoordinator()) bg-blue-100 text-blue-700
                            @else bg-green-100 text-green-700 @endif">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-red-600 ml-2">Salir</button>
                        </form>
                    </div>
                </div>

                {{-- Mobile: hamburger --}}
                <div class="sm:hidden flex items-center">
                    <button id="mobile-menu-btn" type="button"
                        class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none"
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
        <div id="mobile-menu" class="sm:hidden hidden border-t border-gray-100 bg-white">
            <div class="px-4 py-3 space-y-1">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Dashboard</a>
                    <a href="{{ route('admin.groups.index') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Grupos</a>
                    <a href="{{ route('admin.users.index') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Usuarios</a>
                @elseif(auth()->user()->isCoordinator())
                    <a href="{{ route('coordinator.dashboard') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Dashboard</a>
                @else
                    <a href="{{ route('patient.dashboard') }}" class="block py-2 px-3 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Mi perfil</a>
                @endif

                <div class="border-t border-gray-100 pt-3 mt-2 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            @if(auth()->user()->isAdmin()) bg-purple-100 text-purple-700
                            @elseif(auth()->user()->isCoordinator()) bg-blue-100 text-blue-700
                            @else bg-green-100 text-green-700 @endif">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-700 px-3 py-2">Salir</button>
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

    <footer class="mt-16 py-6 text-center text-xs text-gray-400">
        Plena Grupos &copy; {{ date('Y') }} — Grupo Terapéutico para el Descenso de Peso
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
