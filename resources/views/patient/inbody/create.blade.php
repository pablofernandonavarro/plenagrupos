@extends('layouts.app')
@section('title', 'Mis InBody')

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('patient.profile') }}" class="text-gray-400 hover:text-gray-600 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Mis InBody</h1>
            <p class="text-sm text-gray-400">Subí tu reporte y la IA extrae los datos automáticamente</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    {{-- Upload & extract --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-5 space-y-4">
        <div>
            <h2 class="font-semibold text-gray-800">1. Subir imagen del InBody</h2>
            <p class="text-xs text-gray-400 mt-0.5">La IA extrae los datos automáticamente. Podés revisarlos antes de guardar.</p>
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Foto del reporte InBody</label>
            <p class="text-xs text-gray-400 -mt-1">Con la hoja que muestra la Puntuación InBody alcanza — no hace falta subir el resto de las hojas.</p>

            <div id="photo-empty">
                <label for="inbody-photo"
                    class="w-full flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-200 hover:border-teal-400 text-gray-400 hover:text-teal-600 rounded-xl py-6 text-sm font-medium transition cursor-pointer">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Seleccionar foto
                </label>
                <input type="file" accept="image/*" id="inbody-photo" class="hidden" onchange="onPhotoChange(this)">
            </div>

            <div id="photo-filled" class="hidden flex items-center gap-3 border border-gray-200 rounded-xl p-2 bg-gray-50">
                <img id="photo-thumb" class="w-14 h-14 object-cover rounded-lg border border-gray-200 shrink-0" alt="Vista previa">
                <p id="photo-name" class="flex-1 min-w-0 text-sm text-gray-700 truncate"></p>
                <button type="button" onclick="clearPhoto()" class="text-gray-400 hover:text-red-500 transition shrink-0 p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <button id="btn-extract" type="button" onclick="extractData()"
            class="w-full flex items-center justify-center gap-2 bg-teal-600 hover:bg-teal-700 text-white font-semibold px-5 py-3 rounded-xl transition text-sm disabled:opacity-50">
            <svg id="extract-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span id="extract-label">Extraer datos con IA</span>
        </button>

        <div id="extract-error" class="hidden bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-600"></div>
    </div>

    {{-- Confirmation form --}}
    <form id="inbody-form" method="POST" action="{{ route('patient.inbody.store') }}"
          enctype="multipart/form-data" class="hidden space-y-4">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-5 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">2. Revisá y confirmá los datos</h2>
                <span class="text-xs bg-teal-50 text-teal-600 px-2.5 py-1 rounded-full font-medium">Extraído por IA</span>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fecha del estudio</label>
                <input type="date" name="test_date" id="f_test_date" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>

            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Composición corporal</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach([
                        ['weight',               'Peso (kg)',            'f_weight'],
                        ['skeletal_muscle_mass', 'Masa muscular (kg)',   'f_smm'],
                        ['body_fat_mass',        'Masa grasa (kg)',      'f_bfm'],
                        ['body_fat_percentage',  'Grasa corporal (%)',   'f_bfp'],
                    ] as [$name, $label, $id])
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                        <input type="number" step="any" name="{{ $name }}" id="{{ $id }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Métricas adicionales</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach([
                        ['bmi',                 'IMC',                    'f_bmi'],
                        ['basal_metabolic_rate','Metabolismo basal (kcal)','f_bmr'],
                        ['visceral_fat_level',  'Grasa visceral',         'f_vfl'],
                        ['total_body_water',    'Agua corporal (kg)',      'f_tbw'],
                        ['proteins',            'Proteínas (kg)',          'f_prot'],
                        ['minerals',            'Minerales (kg)',          'f_min'],
                        ['inbody_score',        'Puntaje InBody',         'f_score'],
                        ['obesity_degree',      'Grado de obesidad (%)',  'f_obes'],
                    ] as [$name, $label, $id])
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                        <input type="number" step="any" name="{{ $name }}" id="{{ $id }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Notas (opcional)</label>
                <textarea name="notes" rows="2" placeholder="Observaciones..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Imágenes del reporte (se guardarán)</label>
                <input type="file" name="images[]" id="imageStore" accept="image/*"
                    class="block w-full text-sm text-gray-600
                           file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                           file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-600
                           hover:file:bg-gray-100 cursor-pointer">
                <p id="store-count" class="text-xs text-gray-400 mt-1">Pre-seleccionada automáticamente · podés cambiarla</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-semibold px-5 py-3 rounded-xl transition text-sm">
                Guardar registro InBody
            </button>
            <button type="button" onclick="document.getElementById('inbody-form').classList.add('hidden')"
               class="px-5 py-3 border border-gray-200 text-gray-500 text-sm rounded-xl hover:bg-gray-50 transition">
                Cancelar
            </button>
        </div>
    </form>

    {{-- History --}}
    @if($records->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Mis registros InBody</h2>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($records as $r)
            <div class="px-5 py-4 flex items-start justify-between gap-3">
                <div class="space-y-1 flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-gray-800">{{ $r->test_date->format('d/m/Y') }}</p>
                        @if($r->inbody_score)
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                {{ $r->inbody_score >= 80 ? 'bg-green-100 text-green-700' : ($r->inbody_score >= 60 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600') }}">
                                Score {{ $r->inbody_score }}
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
                        @if($r->weight)            <span>Peso: <strong class="text-gray-700">{{ $r->weight }} kg</strong></span>@endif
                        @if($r->body_fat_percentage)<span>Grasa: <strong class="text-gray-700">{{ $r->body_fat_percentage }}%</strong></span>@endif
                        @if($r->skeletal_muscle_mass)<span>Músculo: <strong class="text-gray-700">{{ $r->skeletal_muscle_mass }} kg</strong></span>@endif
                        @if($r->visceral_fat_level)<span>Visceral: <strong class="text-gray-700">{{ $r->visceral_fat_level }}</strong></span>@endif
                        @if($r->bmi)               <span>IMC: <strong class="text-gray-700">{{ $r->bmi }}</strong></span>@endif
                    </div>
                    @if($r->notes)
                        <p class="text-xs text-gray-400 italic">{{ $r->notes }}</p>
                    @endif
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    @if($r->image_path)
                    <a href="{{ Storage::url($r->image_path) }}" target="_blank"
                       class="text-gray-400 hover:text-teal-600 transition" title="Ver imagen">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </a>
                    @endif
                    <a href="{{ route('patient.inbody.edit', $r) }}"
                       class="text-gray-400 hover:text-blue-600 transition" title="Editar">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <form action="{{ route('patient.inbody.destroy', $r) }}" method="POST" class="inline"
                          onsubmit="return confirm('¿Estás seguro de eliminar este registro InBody?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="Eliminar">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

<script>
const extractUrl = '{{ route("patient.inbody.extract") }}';
const csrfToken  = '{{ csrf_token() }}';

async function onPhotoChange(input) {
    const file = input.files[0];
    if (!file) return;

    const compressed = await replaceInputWithCompressed(input, file, { maxDim: 1600, quality: 0.8 });

    const reader = new FileReader();
    reader.onload = e => { document.getElementById('photo-thumb').src = e.target.result; };
    reader.readAsDataURL(compressed);

    document.getElementById('photo-name').textContent = compressed.name;
    document.getElementById('photo-empty').classList.add('hidden');
    document.getElementById('photo-filled').classList.remove('hidden');
}

function clearPhoto() {
    document.getElementById('inbody-photo').value = '';
    document.getElementById('photo-filled').classList.add('hidden');
    document.getElementById('photo-empty').classList.remove('hidden');
}

function collectFiles() {
    const input = document.getElementById('inbody-photo');
    return input.files.length ? [input.files[0]] : [];
}

async function extractData() {
    const files = collectFiles();
    if (!files.length) {
        alert('Seleccioná una foto primero.');
        return;
    }

    const btn    = document.getElementById('btn-extract');
    const label  = document.getElementById('extract-label');
    const errDiv = document.getElementById('extract-error');

    btn.disabled = true;
    label.textContent = 'Extrayendo datos...';
    errDiv.classList.add('hidden');

    const formData = new FormData();
    formData.append('_token', csrfToken);
    files.forEach(f => formData.append('images[]', f));

    try {
        const res = await fetch(extractUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData,
        });

        let data;
        const contentType = res.headers.get('content-type') ?? '';
        if (contentType.includes('application/json')) {
            data = await res.json();
        } else {
            const text = await res.text();
            errDiv.innerHTML = `<strong>Error del servidor (${res.status}):</strong><br><pre class="text-xs mt-1 whitespace-pre-wrap">${text.slice(0, 500)}</pre>`;
            errDiv.classList.remove('hidden');
            return;
        }

        if (!res.ok) {
            errDiv.textContent = data.error ?? `Error ${res.status} al procesar las imágenes.`;
            errDiv.classList.remove('hidden');
            return;
        }

        const map = {
            test_date:            'f_test_date',
            weight:               'f_weight',
            skeletal_muscle_mass: 'f_smm',
            body_fat_mass:        'f_bfm',
            body_fat_percentage:  'f_bfp',
            bmi:                  'f_bmi',
            basal_metabolic_rate: 'f_bmr',
            visceral_fat_level:   'f_vfl',
            total_body_water:     'f_tbw',
            proteins:             'f_prot',
            minerals:             'f_min',
            inbody_score:         'f_score',
            obesity_degree:       'f_obes',
        };
        for (const [key, id] of Object.entries(map)) {
            const el = document.getElementById(id);
            if (el && data[key] != null) el.value = data[key];
        }

        const dt = new DataTransfer();
        files.forEach(f => dt.items.add(f));
        const storeInput = document.getElementById('imageStore');
        storeInput.files = dt.files;
        document.getElementById('store-count').textContent =
            'Foto pre-seleccionada automáticamente · podés cambiarla';

        document.getElementById('inbody-form').classList.remove('hidden');
        document.getElementById('inbody-form').scrollIntoView({ behavior: 'smooth', block: 'start' });

    } catch (e) {
        errDiv.textContent = 'Error inesperado: ' + e.message;
        errDiv.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        label.textContent = 'Extraer datos con IA';
    }
}

</script>
@endsection
