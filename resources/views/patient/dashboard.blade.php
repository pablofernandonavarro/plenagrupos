@extends('layouts.app')
@section('title', 'Inicio')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Hola, {{ auth()->user()->name }}</h1>
            <p class="text-gray-500 text-sm mt-0.5">Tu progreso en el grupo terapéutico</p>
        </div>
        <x-avatar :user="auth()->user()" size="md" />
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
    @foreach($groups as $vg)
        @php
            $joinUrl        = $vg->modality === 'virtual' ? route('group.join', $vg->qr_token) : null;
            $enSesion       = $vg->isLiveSessionNow();
            $myToday        = $todayAttendances[$vg->id] ?? null;
            $myCheckedIn    = $myToday && !$myToday->left_at && $enSesion;
            $myNoCheckout   = $myToday && !$myToday->left_at && !$enSesion;
            $myLeft         = $myToday && $myToday->left_at;
        @endphp
        <div class="rounded-xl border overflow-hidden {{ $myCheckedIn ? 'border-green-400 shadow-green-100 shadow-md' : ($enSesion ? 'border-green-200' : 'border-gray-200') }}">
            <div class="px-4 py-3 flex items-start justify-between gap-2 {{ $enSesion ? 'bg-green-50' : 'bg-teal-50' }}">
                <div>
                    <p class="font-semibold text-gray-800 leading-snug">{{ $vg->name }}</p>
                    @if($vg->meetingDaysDisplay || $vg->meeting_time)
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $vg->meetingDaysDisplay }}{{ $vg->meetingDaysDisplay && $vg->meeting_time ? ' · ' : '' }}{{ $vg->meeting_time ? $vg->meeting_time_formatted . ' hs' : '' }}
                        </p>
                    @endif
                    {{-- Estado personal de hoy --}}
                    @if($myCheckedIn)
                        <p class="text-xs text-green-600 font-semibold mt-1">● Estás en sesión</p>
                    @elseif($myLeft)
                        <p class="text-xs text-gray-500 mt-1">✓ Asististe hoy · saliste {{ $myToday->left_at->format('H:i') }} hs</p>
                    @elseif($myNoCheckout)
                        <p class="text-xs text-gray-500 mt-1">✓ Asististe hoy · {{ $myToday->attended_at->format('H:i') }} hs</p>
                    @elseif($enSesion)
                        <p class="text-xs text-gray-400 mt-1">— No registraste entrada hoy</p>
                    @endif
                </div>
                @if($enSesion)
                    <span class="shrink-0 inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-green-500 text-white">
                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                        EN VIVO
                    </span>
                @endif
            </div>
            @if($joinUrl)
                <div class="px-4 py-3 border-t border-gray-100 bg-white space-y-1.5">
                    <p class="text-xs font-medium text-gray-500">Link de acceso virtual</p>
                    <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                        <a href="{{ $joinUrl }}" class="text-xs text-teal-600 truncate flex-1 underline underline-offset-2">{{ $joinUrl }}</a>
                        <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $joinUrl }}').then(() => { this.textContent = '✓'; setTimeout(() => this.textContent = 'Copiar', 1500) })"
                            class="shrink-0 text-xs font-medium text-teal-600 hover:text-teal-800 transition">
                            Copiar
                        </button>
                    </div>
                </div>
            @endif
        </div>

        @if($enrolledGroupIds->contains($vg->id))
        <form action="{{ route('patient.groups.leave', $vg) }}" method="POST"
              onsubmit="return confirm('¿Confirmás que querés salir del grupo «{{ $vg->name }}»?')">
            @csrf
            <button type="submit"
                class="w-full text-sm font-medium text-red-600 border border-red-200 bg-white rounded-xl px-4 py-3 hover:bg-red-50 transition">
                Salir del grupo «{{ $vg->name }}»
            </button>
        </form>
        @endif
    @endforeach

    {{-- Historial de sesiones --}}
    @if($sessionHistory->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Historial de sesiones</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($sessionHistory as $s)
                <div class="px-4 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $s->group_name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $s->date }} · {{ $s->time }} hs
                            @if($s->session_num) · Sesión {{ $s->session_num }} @endif
                        </p>
                    </div>
                    @if($s->minutes !== null)
                        <span class="shrink-0 text-sm font-semibold text-teal-600">{{ $s->minutes }} min</span>
                    @elseif($s->is_live)
                        <span class="shrink-0 text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-700">En curso</span>
                    @elseif($s->is_today)
                        <span class="shrink-0 text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-400">Sin cierre</span>
                    @else
                        <span class="shrink-0 text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-400">Sin cierre</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    @endif

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

@php
    $openAttendanceToday = $todayAttendances->first(fn($a) => !$a->left_at);
@endphp
@if($openAttendanceToday)
<script>
(function () {
    const statusUrl = '{{ route('patient.attendances.status') }}';
    // Initial snapshot: group_id -> left_at (null if still open)
    const initial = @json($todayAttendances->map(fn($a) => $a->left_at?->format('H:i')));

    async function poll() {
        try {
            const res = await fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const current = await res.json();
            // If any previously-open attendance now has a left_at, reload
            for (const [groupId, leftAt] of Object.entries(current)) {
                if (!initial[groupId] && leftAt) {
                    window.location.reload();
                    return;
                }
            }
        } catch(e) {}
    }

    setInterval(poll, 8000);
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
