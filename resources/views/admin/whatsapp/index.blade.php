@extends('layouts.app')
@section('title', 'WhatsApp')

@section('content')
<div class="max-w-2xl space-y-5">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-800">WhatsApp</h1>
        <a href="{{ route('admin.whatsapp.templates.index') }}" class="text-sm text-teal-600 hover:underline">
            Plantillas de mensajes
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

        <div id="wa-disconnected" class="{{ $status === 'WORKING' ? 'hidden' : '' }} text-center space-y-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <p class="text-sm text-gray-500">WhatsApp no está vinculado todavía.</p>
            <form action="{{ route('admin.whatsapp.connect') }}" method="POST" id="wa-connect-form">
                @csrf
                <button type="submit" class="bg-teal-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-teal-700 transition">
                    Vincular WhatsApp
                </button>
            </form>
        </div>

        <div id="wa-qr" class="hidden text-center space-y-4">
            <p class="text-sm text-gray-600">Escaneá este código con WhatsApp (Dispositivos vinculados → Vincular dispositivo):</p>
            <div class="inline-block p-3 bg-white border-2 border-gray-100 rounded-xl shadow-inner">
                <img id="wa-qr-img" src="" alt="Código QR de WhatsApp" class="w-56 h-56">
            </div>
            <p class="text-xs text-gray-400">Se actualiza solo cuando escanees.</p>
        </div>

        <div id="wa-connected" class="{{ $status === 'WORKING' ? '' : 'hidden' }} text-center space-y-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-green-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-800">WhatsApp vinculado</p>
                <p id="wa-phone" class="text-xs text-gray-400 mt-0.5">{{ $me['pushName'] ?? '' }} @if(!empty($me['id'])) · {{ str($me['id'])->before('@') }} @endif</p>
            </div>
            <form action="{{ route('admin.whatsapp.disconnect') }}" method="POST"
                  onsubmit="return confirm('¿Desvincular WhatsApp? Vas a tener que volver a escanear el QR para reconectar.')">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm font-medium px-4 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition">
                    Desvincular
                </button>
            </form>
        </div>

    </div>
</div>

<script>
const statusUrl = '{{ route('admin.whatsapp.status') }}';
const qrUrl = '{{ route('admin.whatsapp.qr') }}';

const panels = {
    disconnected: document.getElementById('wa-disconnected'),
    qr: document.getElementById('wa-qr'),
    connected: document.getElementById('wa-connected'),
};
const qrImg = document.getElementById('wa-qr-img');
const waPhone = document.getElementById('wa-phone');

function showPanel(name) {
    Object.entries(panels).forEach(([key, el]) => el.classList.toggle('hidden', key !== name));
}

let polling = null;

async function refreshStatus() {
    let data;
    try {
        const res = await fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        data = await res.json();
    } catch (e) { return; }

    if (data.status === 'WORKING') {
        showPanel('connected');
        if (data.me) {
            waPhone.textContent = (data.me.pushName ?? '') + (data.me.id ? ' · ' + data.me.id.split('@')[0] : '');
        }
    } else if (data.status === 'SCAN_QR_CODE' || data.status === 'STARTING') {
        showPanel('qr');
        qrImg.src = qrUrl + '?t=' + Date.now();
    } else {
        showPanel('disconnected');
    }
}

refreshStatus();
polling = setInterval(refreshStatus, 4000);
</script>
@endsection
