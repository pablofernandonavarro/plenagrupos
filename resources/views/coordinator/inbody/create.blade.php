@extends('layouts.app')
@section('title', 'InBody — ' . $patient->name)

@section('content')
<div class="max-w-2xl space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('coordinator.patients.show', $patient) }}" class="text-gray-400 hover:text-gray-600 shrink-0">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-800">Registro InBody</h1>
            <p class="text-sm text-gray-400">{{ $patient->name }}</p>
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

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Fotos del reporte InBody
                <span class="text-gray-400 font-normal">(podés seleccionar hasta 3 imágenes a la vez)</span>
            </label>
            <input type="file" id="imageInput" accept="image/*" multiple
                class="block w-full text-sm text-gray-600
                       file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                       file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700
                       hover:file:bg-teal-100 cursor-pointer"
                onchange="updateFileCount(this)">
            <p id="file-count" class="text-xs text-gray-400 mt-1 hidden"></p>
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

    {{-- Confirmation form (hidden until extraction) --}}
    <form id="inbody-form" method="POST" action="{{ route('coordinator.patients.inbody.store', $patient) }}"
          enctype="multipart/form-data" class="hidden space-y-4">
        @csrf

        {{-- Hidden image field — will be populated via JS --}}
        <input type="hidden" name="image_submitted" id="image_submitted" value="0">

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-5 py-5 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">2. Revisá y confirmá los datos</h2>
                <span class="text-xs text-gray-400 bg-teal-50 text-teal-600 px-2.5 py-1 rounded-full font-medium">Extraído por IA</span>
            </div>

            {{-- Date --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Fecha del estudio</label>
                <input type="date" name="test_date" id="f_test_date" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>

            {{-- Primary body composition --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Composición corporal</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['weight',               'Peso (kg)',            'f_weight'],
                        ['skeletal_muscle_mass', 'Masa muscular (kg)',   'f_smm'],
                        ['body_fat_mass',        'Masa grasa (kg)',      'f_bfm'],
                        ['body_fat_percentage',  'Grasa corporal (%)',   'f_bfp'],
                    ] as [$name, $label, $id])
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                        <input type="number" step="0.1" name="{{ $name }}" id="{{ $id }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Secondary metrics --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Métricas adicionales</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach([
                        ['bmi',                 'IMC',                   'f_bmi',   '0.1'],
                        ['basal_metabolic_rate','Metabolismo basal (kcal)','f_bmr', '1'],
                        ['visceral_fat_level',  'Grasa visceral',        'f_vfl',   '0.1'],
                        ['total_body_water',    'Agua corporal (kg)',     'f_tbw',   '0.1'],
                        ['proteins',            'Proteínas (kg)',         'f_prot',  '0.1'],
                        ['minerals',            'Minerales (kg)',         'f_min',   '0.1'],
                        ['inbody_score',        'Puntaje InBody',        'f_score', '1'],
                        ['obesity_degree',      'Grado de obesidad (%)', 'f_obes',  '0.1'],
                    ] as [$name, $label, $id, $step])
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                        <input type="number" step="{{ $step }}" name="{{ $name }}" id="{{ $id }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Notas (opcional)</label>
                <textarea name="notes" rows="2" placeholder="Observaciones del coordinador..."
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 resize-none"></textarea>
            </div>

            {{-- Image re-upload for actual storage --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Imágenes del reporte (se guardarán)</label>
                <input type="file" name="images[]" id="imageStore" accept="image/*" multiple
                    class="block w-full text-sm text-gray-600
                           file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                           file:text-sm file:font-semibold file:bg-gray-50 file:text-gray-600
                           hover:file:bg-gray-100 cursor-pointer">
                <p id="store-count" class="text-xs text-gray-400 mt-1">Pre-seleccionadas automáticamente · podés cambiarlas</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-semibold px-5 py-3 rounded-xl transition text-sm">
                Guardar registro InBody
            </button>
            <a href="{{ route('coordinator.patients.show', $patient) }}"
               class="px-5 py-3 border border-gray-200 text-gray-500 text-sm rounded-xl hover:bg-gray-50 transition text-center">
                Cancelar
            </a>
        </div>
    </form>

    {{-- History --}}
    @if($records->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Historial InBody</h2>
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
                        @if($r->weight)           <span>Peso: <strong class="text-gray-700">{{ $r->weight }} kg</strong></span>@endif
                        @if($r->body_fat_percentage)<span>Grasa: <strong class="text-gray-700">{{ $r->body_fat_percentage }}%</strong></span>@endif
                        @if($r->skeletal_muscle_mass)<span>Músculo: <strong class="text-gray-700">{{ $r->skeletal_muscle_mass }} kg</strong></span>@endif
                        @if($r->visceral_fat_level)<span>Visceral: <strong class="text-gray-700">{{ $r->visceral_fat_level }}</strong></span>@endif
                        @if($r->bmi)              <span>IMC: <strong class="text-gray-700">{{ $r->bmi }}</strong></span>@endif
                    </div>
                    @if($r->notes)
                        <p class="text-xs text-gray-400 italic truncate">{{ $r->notes }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if($r->image_path)
                        <a href="{{ Storage::url($r->image_path) }}" target="_blank"
                           class="text-gray-400 hover:text-teal-600 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                    @endif
                    <form method="POST" action="{{ route('coordinator.patients.inbody.destroy', [$patient, $r]) }}"
                          onsubmit="return confirm('¿Eliminar este registro?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-gray-300 hover:text-red-500 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
const extractUrl = '{{ route("coordinator.patients.inbody.extract", $patient) }}';
const csrfToken  = '{{ csrf_token() }}';

function updateFileCount(input) {
    const p = document.getElementById('file-count');
    if (input.files.length > 0) {
        p.textContent = input.files.length === 1
            ? '1 imagen seleccionada'
            : `${input.files.length} imágenes seleccionadas`;
        p.classList.remove('hidden');
    } else {
        p.classList.add('hidden');
    }
}

async function extractData() {
    const fileInput = document.getElementById('imageInput');
    if (!fileInput.files.length) {
        alert('Seleccioná al menos una imagen primero.');
        return;
    }

    const btn    = document.getElementById('btn-extract');
    const label  = document.getElementById('extract-label');
    const errDiv = document.getElementById('extract-error');

    btn.disabled = true;
    label.textContent = fileInput.files.length > 1
        ? `Analizando ${fileInput.files.length} imágenes...`
        : 'Extrayendo datos...';
    errDiv.classList.add('hidden');

    const formData = new FormData();
    formData.append('_token', csrfToken);
    for (const file of fileInput.files) {
        formData.append('images[]', file);
    }

    try {
        const res = await fetch(extractUrl, { method: 'POST', body: formData });
        const data = await res.json();

        if (!res.ok) {
            errDiv.textContent = data.error ?? 'Error al procesar las imágenes.';
            errDiv.classList.remove('hidden');
            return;
        }

        // Populate form fields
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

        // Copy all selected images to the storage upload field
        const dt = new DataTransfer();
        for (const file of fileInput.files) dt.items.add(file);
        const storeInput = document.getElementById('imageStore');
        storeInput.files = dt.files;
        document.getElementById('store-count').textContent =
            `${fileInput.files.length} imagen(es) pre-seleccionada(s) · podés cambiarlas`;

        document.getElementById('inbody-form').classList.remove('hidden');
        document.getElementById('inbody-form').scrollIntoView({ behavior: 'smooth', block: 'start' });

    } catch (e) {
        errDiv.textContent = 'Error de conexión. Intentá nuevamente.';
        errDiv.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        label.textContent = 'Extraer datos con IA';
    }
}
</script>
@endsection
