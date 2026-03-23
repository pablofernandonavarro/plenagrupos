@extends('layouts.app')
@section('title', 'Mi Perfil')

@section('content')
<div class="space-y-6">

    <div class="flex items-center gap-3">
        <x-avatar :user="auth()->user()" size="lg" />
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Hola, {{ auth()->user()->name }}</h1>
            <p class="text-gray-500 text-sm mt-0.5">Tu progreso en el grupo terapéutico</p>
        </div>
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
                @else —
                @endif
            </p>
            <p class="text-xs text-gray-500 mt-1">Progreso</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <p class="text-2xl sm:text-3xl font-bold text-blue-600">{{ $weightRecords->count() }}</p>
            <p class="text-xs text-gray-500 mt-1">Visitas</p>
        </div>
    </div>

    {{-- Trend banner --}}
    @if($weightRecords->count() >= 2)
    @php
        $absT = abs($trend);
        if ($trend < -0.5)      { $tc='text-blue-600';  $ti='↓↓'; $tt='Pérdida acelerada — ¡excelente!';            $ts='bg-blue-50 border-blue-200'; }
        elseif ($trend < -0.05) { $tc='text-green-600'; $ti='↓';  $tt='Vas bajando — ritmo saludable';              $ts='bg-green-50 border-green-200'; }
        elseif ($trend < 0.05)  { $tc='text-gray-500';  $ti='→';  $tt='Peso estable';                               $ts='bg-gray-50 border-gray-200'; }
        elseif ($trend < 0.3)   { $tc='text-yellow-600';$ti='↑';  $tt='Leve aumento — revisá tus hábitos';          $ts='bg-yellow-50 border-yellow-200'; }
        else                    { $tc='text-red-500';   $ti='↑↑'; $tt='Aumento sostenido — consultá al coordinador'; $ts='bg-red-50 border-red-200'; }
    @endphp
    <div class="rounded-xl border px-4 py-3 flex items-center gap-3 {{ $ts }}">
        <span class="text-2xl font-bold {{ $tc }} shrink-0">{{ $ti }}</span>
        <div>
            <p class="text-sm font-semibold text-gray-800">Tendencia</p>
            <p class="text-xs {{ $tc }}">{{ $tt }}</p>
        </div>
        @if($inRange !== null)
            <span class="ml-auto shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full
                {{ $inRange ? 'bg-green-100 text-green-700' : (isset($latestWeight) && $techo && $latestWeight > $techo ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700') }}">
                {{ $inRange ? '✓ En rango' : (isset($latestWeight) && isset($techo) && $latestWeight > $techo ? '↑ Sobre techo' : '↓ Bajo piso') }}
            </span>
        @endif
    </div>
    @endif

    {{-- Progress toward ideal --}}
    @if($progressPct !== null)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-5 py-4">
        <div class="flex justify-between items-center mb-2">
            <p class="text-sm font-semibold text-gray-700">Progreso hacia tu peso ideal</p>
            <span class="text-sm font-bold text-teal-600">{{ $progressPct }}%</span>
        </div>
        <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full bg-teal-500 rounded-full transition-all" style="width: {{ $progressPct }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-400 mt-1.5">
            <span>Inicial: {{ $weightRecords->last()?->weight }} kg</span>
            <span>Ideal: {{ auth()->user()->ideal_weight }} kg</span>
        </div>
    </div>
    @endif

    {{-- Weight evolution chart --}}
    @if($weightRecords->count() >= 2)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Evolución de peso</h2>
            <p class="text-xs text-gray-400 mt-0.5">Tu recorrido sesión a sesión</p>
        </div>
        <div class="px-4 py-4" style="position:relative; height:220px;">
            <canvas id="weightChart"></canvas>
        </div>
    </div>
    @elseif($weightRecords->count() === 1)
    <div class="bg-teal-50 border border-teal-200 rounded-xl px-5 py-4 text-sm text-teal-700">
        Registrá un peso más para ver tu gráfico de evolución.
    </div>
    @endif

    {{-- Groups info --}}
    @if($groups->isNotEmpty())
        <div class="bg-teal-50 border border-teal-200 rounded-xl p-4 space-y-3">
            <p class="text-sm font-medium text-teal-800">
                Estás en {{ $groups->count() === 1 ? 'el grupo' : 'los grupos' }}:
                <span class="font-semibold">{{ $groups->pluck('name')->join(', ') }}</span>
            </p>
            @foreach($groups->where('modality', 'virtual') as $vg)
                @php $joinUrl = route('group.join', $vg->qr_token); @endphp
                <div>
                    <p class="text-xs font-medium text-teal-700 mb-1.5">
                        <svg class="inline w-3.5 h-3.5 mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.723v6.554a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
                        Link de acceso virtual — {{ $vg->name }}
                    </p>
                    <div class="flex items-center gap-2 bg-white border border-teal-200 rounded-lg px-3 py-2">
                        <a href="{{ $joinUrl }}" class="text-xs text-teal-600 truncate flex-1 underline underline-offset-2">{{ $joinUrl }}</a>
                        <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $joinUrl }}').then(() => { this.textContent = '✓'; setTimeout(() => this.textContent = 'Copiar', 1500) })"
                            class="shrink-0 text-xs font-medium text-teal-600 hover:text-teal-800 transition">
                            Copiar
                        </button>
                    </div>
                </div>
            @endforeach
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
            <form action="{{ route('patient.profile.update') }}" method="POST" enctype="multipart/form-data">
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
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Foto de perfil</label>
                    <div class="flex items-center gap-3">
                        <x-avatar :user="auth()->user()" size="md" />
                        <div class="flex-1">
                            <input type="file" name="avatar" id="avatar-input" accept="image/*" class="hidden"
                                onchange="document.getElementById('avatar-label').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado'">
                            <label for="avatar-input"
                                class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-600 bg-white hover:bg-gray-50 active:bg-gray-100 text-center cursor-pointer transition">
                                Seleccionar foto
                            </label>
                            <p id="avatar-label" class="text-xs text-gray-400 mt-1 truncate text-center">Ningún archivo seleccionado</p>
                        </div>
                    </div>
                    @error('avatar')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition text-sm">
                    Guardar
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
                        <p class="text-sm font-medium text-gray-800">{{ $record->group?->name ?? '(Grupo eliminado)' }}</p>
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

@if($weightRecords->count() >= 2)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    const cd = @json($chartData);
    const datasets = [{
        label: 'Peso',
        data: cd.weights,
        borderColor: '#0d9488',
        backgroundColor: 'rgba(13,148,136,0.08)',
        borderWidth: 2.5,
        pointRadius: cd.weights.length > 20 ? 2 : 4,
        pointHoverRadius: 6,
        fill: true,
        tension: 0.3,
    }];
    if (cd.piso)  datasets.push({ label: 'Piso ('  + cd.piso  + ' kg)', data: cd.labels.map(() => cd.piso),
        borderColor: '#16a34a', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, fill: false, tension: 0 });
    if (cd.techo) datasets.push({ label: 'Techo (' + cd.techo + ' kg)', data: cd.labels.map(() => cd.techo),
        borderColor: '#ef4444', borderWidth: 1.5, borderDash: [6,4], pointRadius: 0, fill: false, tension: 0 });

    new Chart(document.getElementById('weightChart').getContext('2d'), {
        type: 'line',
        data: { labels: cd.labels, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: !!(cd.piso || cd.techo), position: 'bottom',
                    labels: { font: { size: 11 }, boxWidth: 20, padding: 10 } },
                tooltip: { callbacks: {
                    label: ctx => ctx.datasetIndex === 0
                        ? ' ' + ctx.parsed.y.toFixed(2) + ' kg'
                        : ctx.dataset.label
                }}
            },
            scales: {
                x: { ticks: { font:{size:11}, color:'#9ca3af', maxRotation:45, autoSkip:true, maxTicksLimit:8 }, grid:{display:false} },
                y: { ticks: { font:{size:11}, color:'#9ca3af', callback: v => v+' kg' }, grid:{color:'#f3f4f6'}, grace:'8%' }
            }
        }
    });
})();
</script>
@endif

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
