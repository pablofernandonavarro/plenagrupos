@extends('layouts.app')
@section('title', 'Mi Perfil')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">Hola, {{ auth()->user()->name }}</h1>
        <p class="text-gray-500 text-sm mt-1">Tu progreso en el grupo terapéutico</p>
    </div>

    {{-- Scan QR button --}}
    <button id="btn-scan"
        class="w-full flex items-center justify-center gap-3 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-bold py-4 rounded-2xl shadow-md transition text-base">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 4v1m0 14v1M4 12h1m14 0h1M5.636 5.636l.707.707M17.657 17.657l.707.707M5.636 18.364l.707-.707M17.657 6.343l.707-.707M9 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0z"/>
        </svg>
        Escanear QR del grupo
    </button>

    {{-- Scanner modal --}}
    <div id="scanner-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/70" id="modal-backdrop"></div>
        <div class="relative z-10 flex flex-col h-full max-w-lg mx-auto">
            <div class="bg-white px-5 py-4 flex justify-between items-center">
                <p class="font-semibold text-gray-800">Apuntá la cámara al QR</p>
                <button id="btn-close" class="text-gray-400 hover:text-gray-700 p-1">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="reader" class="flex-1 bg-black"></div>
            <div id="scan-status" class="bg-white px-5 py-3 text-sm text-gray-500 text-center min-h-11"></div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-teal-600">{{ $latestWeight ? $latestWeight . ' kg' : '—' }}</p>
            <p class="text-xs text-gray-500 mt-1">Último peso</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold {{ $totalLoss > 0 ? 'text-green-600' : ($totalLoss < 0 ? 'text-red-500' : 'text-gray-400') }}">
                @if($totalLoss !== null)
                    {{ $totalLoss > 0 ? '-' : '+' }}{{ abs($totalLoss) }} kg
                @else
                    —
                @endif
            </p>
            <p class="text-xs text-gray-500 mt-1">Progreso</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $weightRecords->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Visitas</p>
        </div>
    </div>

    {{-- Groups info --}}
    @if($groups->isNotEmpty())
        <div class="bg-teal-50 border border-teal-200 rounded-xl p-4">
            <p class="text-sm font-medium text-teal-800">
                Estás en {{ $groups->count() === 1 ? 'el grupo' : 'los grupos' }}:
                <span class="font-semibold">{{ $groups->pluck('name')->join(', ') }}</span>
            </p>
        </div>
    @endif

    {{-- Maintenance range --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Mi rango de mantenimiento</h2>
            <p class="text-xs text-gray-400 mt-0.5">Peso mínimo (piso) y máximo (techo) que querés mantener.</p>
        </div>
        <div class="px-5 py-4">
            @if(session('success'))
                <p class="text-green-600 text-sm mb-3">{{ session('success') }}</p>
            @endif
            <form action="{{ route('patient.profile.update') }}" method="POST">
                @csrf
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Piso (kg)</label>
                        <input type="number" step="0.01" min="0" max="300" name="peso_piso"
                            value="{{ old('peso_piso', auth()->user()->peso_piso) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Ej: 68.00">
                        @error('peso_piso')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Techo (kg)</label>
                        <input type="number" step="0.01" min="0" max="300" name="peso_techo"
                            value="{{ old('peso_techo', auth()->user()->peso_techo) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 outline-none text-sm"
                            placeholder="Ej: 72.00">
                        @error('peso_techo')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                    Guardar rango
                </button>
            </form>
        </div>
    </div>

    {{-- Weight history --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Historial de pesos</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($weightRecords as $record)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $record->group->name }}</p>
                        <p class="text-xs text-gray-400">{{ $record->recorded_at->format('d/m/Y H:i') }}</p>
                        @if($record->notes)
                            <p class="text-xs text-gray-500 mt-0.5 italic">{{ $record->notes }}</p>
                        @endif
                    </div>
                    <span class="text-xl font-bold text-teal-600">{{ $record->weight }} kg</span>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-gray-400">
                    <p>Aún no tenés pesos registrados.</p>
                    <p class="text-sm mt-1">Usá el botón de arriba para escanear el QR al llegar.</p>
                </div>
            @endforelse
        </div>
    </div>

</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const modal    = document.getElementById('scanner-modal');
const btnScan  = document.getElementById('btn-scan');
const btnClose = document.getElementById('btn-close');
const backdrop = document.getElementById('modal-backdrop');
const statusEl = document.getElementById('scan-status');
const appBase  = '{{ rtrim(url('/'), '/') }}';

let scanner = null;

function resetReader() {
    document.getElementById('reader').innerHTML = '';
}

function closeScanner() {
    modal.classList.add('hidden');
    const s = scanner;
    scanner = null;
    if (s) {
        try { s.stop().catch(() => {}).finally(resetReader); }
        catch (e) { resetReader(); }
    } else {
        resetReader();
    }
}

function openScanner() {
    modal.classList.remove('hidden');
    statusEl.textContent = 'Iniciando cámara...';

    scanner = new Html5Qrcode('reader');
    scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 240, height: 240 } },
        (decodedText) => {
            try {
                const url = new URL(decodedText);
                if (!url.pathname.match(/^\/grupo\//)) {
                    statusEl.textContent = 'QR no válido para esta aplicación.';
                    return;
                }
                statusEl.textContent = '¡QR detectado! Redirigiendo...';
                closeScanner();
                // Always redirect to the current app's host with the same path
                window.location.href = window.location.origin + url.pathname;
            } catch (e) {
                statusEl.textContent = 'QR no válido para esta aplicación.';
            }
        },
        () => {}
    ).then(() => {
        statusEl.textContent = 'Buscando código QR...';
    }).catch(() => {
        try { scanner.stop().catch(() => {}).finally(resetReader); } catch(e) { resetReader(); }
        scanner = null;
        statusEl.innerHTML = '<span class="text-red-500">No se pudo acceder a la cámara.</span> Requiere HTTPS en producción.';
    });
}

btnScan.addEventListener('click', openScanner);
btnClose.addEventListener('click', closeScanner);
backdrop.addEventListener('click', closeScanner);
</script>
@endsection
