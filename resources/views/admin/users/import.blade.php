@extends('layouts.app')
@section('title', 'Importar usuarios')

@section('content')
<div class="max-w-2xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Importar usuarios desde Excel</h1>
            <p class="text-sm text-gray-500 mt-1">Cargá un archivo con pacientes o coordinadores para crearlos o actualizarlos masivamente.</p>
        </div>
    </div>

    {{-- ── RESULTADO DE IMPORTACIÓN ───────────────────────────────── --}}
    @if(session('import_done'))
    @php
        $created = session('import_created', 0);
        $updated = session('import_updated', 0);
        $errs    = session('import_errors', []);
        $total   = $created + $updated;
        $hasErrs = count($errs) > 0;
    @endphp
    <div class="rounded-xl border {{ $hasErrs ? 'border-amber-200 bg-amber-50' : 'border-green-200 bg-green-50' }} px-5 py-4 space-y-3">
        <div class="flex items-center gap-2">
            @if($hasErrs)
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <p class="font-semibold text-amber-800">Importación completada con observaciones</p>
            @else
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="font-semibold text-green-800">Importación completada exitosamente</p>
            @endif
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg bg-white border border-green-200 px-3 py-2.5 text-center">
                <p class="text-2xl font-bold text-green-600">{{ $created }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $created === 1 ? 'Usuario creado' : 'Usuarios creados' }}</p>
            </div>
            <div class="rounded-lg bg-white border border-blue-200 px-3 py-2.5 text-center">
                <p class="text-2xl font-bold text-blue-600">{{ $updated }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ $updated === 1 ? 'Usuario actualizado' : 'Usuarios actualizados' }}</p>
            </div>
            <div class="rounded-lg bg-white border {{ $hasErrs ? 'border-red-200' : 'border-gray-100' }} px-3 py-2.5 text-center">
                <p class="text-2xl font-bold {{ $hasErrs ? 'text-red-500' : 'text-gray-300' }}">{{ count($errs) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">{{ count($errs) === 1 ? 'Fila con error' : 'Filas con errores' }}</p>
            </div>
        </div>

        @if($created > 0)
        <p class="text-xs text-green-700">
            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Los usuarios nuevos fueron creados con contraseña aleatoria. Deberán cambiarla al ingresar por primera vez.
        </p>
        @endif

        @if($hasErrs)
        <div class="rounded-lg bg-white border border-amber-200 px-3 py-3">
            <p class="text-xs font-semibold text-amber-700 mb-2">Detalle de filas no procesadas:</p>
            <ul class="space-y-1">
                @foreach($errs as $err)
                <li class="flex items-start gap-1.5 text-xs text-amber-800">
                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ $err }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="flex gap-2 pt-1">
            <a href="{{ route('admin.users.index') }}"
               class="text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 px-4 py-2 rounded-lg transition">
                Ver usuarios importados
            </a>
            <button onclick="document.getElementById('import-card').scrollIntoView({behavior:'smooth'})"
                class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 border border-gray-200 rounded-lg transition">
                Importar otro archivo
            </button>
        </div>
    </div>
    @endif

    {{-- ── MODELO PARA DESCARGAR ──────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-4 flex items-start gap-4">
        <div class="w-10 h-10 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-800">Paso 1 — Descargá el modelo</p>
            <p class="text-xs text-gray-500 mt-0.5">El archivo ya viene con los encabezados correctos y los pacientes actuales precargados. Solo agregá o modificá filas.</p>
        </div>
        <a href="{{ route('admin.users.import.template') }}" id="template-btn"
           onclick="this.innerHTML='<svg class=\'w-4 h-4 animate-spin\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8v8z\'></path></svg> Descargando…'; setTimeout(()=>this.innerHTML='<svg class=\'w-4 h-4\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\'/></svg> Descargar modelo',2500)"
           class="shrink-0 flex items-center gap-2 px-4 py-2 bg-teal-50 border border-teal-200 text-teal-700 text-sm font-medium rounded-lg hover:bg-teal-100 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Descargar modelo
        </a>
    </div>

    {{-- ── FORMATO ESPERADO ───────────────────────────────────────── --}}
    <details class="bg-blue-50 border border-blue-200 rounded-xl overflow-hidden">
        <summary class="px-4 py-3 cursor-pointer text-sm font-semibold text-blue-700 flex items-center justify-between [&::-webkit-details-marker]:hidden">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ver formato esperado del archivo
            </span>
            <svg class="w-4 h-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="px-4 pb-4 text-sm text-blue-700 space-y-2 border-t border-blue-200">
            <p class="pt-3">La primera fila debe contener los encabezados. Columnas reconocidas:</p>
            <div class="overflow-x-auto">
                <table class="text-xs w-full border-collapse">
                    <thead>
                        <tr class="bg-blue-100">
                            <th class="px-2 py-1.5 text-left border border-blue-200">Columna</th>
                            <th class="px-2 py-1.5 text-left border border-blue-200">Descripción</th>
                            <th class="px-2 py-1.5 text-center border border-blue-200">Requerido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['email',                 'Email del usuario (identificador único)',                      true],
                            ['nombre',                'Nombre completo',                                               true],
                            ['telefono',              'Solo dígitos con código de país (ej: 5491112345678)',           false],
                            ['plan',                  'descenso · mantenimiento · mantenimiento_pleno',                false],
                            ['fase_actual',           'descenso · mantenimiento · mantenimiento_pleno',                false],
                            ['fecha inicio del plan', 'Fecha inicio del ciclo (ej: 18/03/2026)',                       false],
                            ['peso_ideal',            'Peso ideal en kg',                                              false],
                            ['peso_piso',             'Peso mínimo de mantenimiento',                                  false],
                            ['peso_techo',            'Peso máximo de mantenimiento',                                  false],
                            ['rol',                   'patient (default) o coordinator',                               false],
                        ] as [$col, $desc, $req])
                        <tr class="border border-blue-200 hover:bg-blue-50/60">
                            <td class="px-2 py-1.5 font-mono font-semibold text-blue-900">{{ $col }}</td>
                            <td class="px-2 py-1.5">{{ $desc }}</td>
                            <td class="px-2 py-1.5 text-center">
                                @if($req)
                                    <span class="text-xs font-medium text-red-600 bg-red-50 px-1.5 py-0.5 rounded">Sí</span>
                                @else
                                    <span class="text-xs text-gray-400">Opcional</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-blue-600 bg-blue-100/60 rounded-lg px-3 py-2">
                💡 Si el email ya existe en el sistema, se actualizan los datos del usuario. Si no existe, se crea con contraseña aleatoria.
            </p>
        </div>
    </details>

    {{-- ── FORMULARIO DE CARGA ────────────────────────────────────── --}}
    <div id="import-card" class="bg-white rounded-xl border border-gray-100 shadow-sm px-5 py-5 space-y-4">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-800">Paso 2 — Subí el archivo completado</p>
                <p class="text-xs text-gray-500 mt-0.5">Formatos aceptados: .xlsx, .xls, .csv · Tamaño máximo: 5 MB</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data"
              id="import-form" class="space-y-4">
            @csrf

            <div class="border-2 border-dashed border-gray-200 rounded-xl px-4 py-5 text-center hover:border-teal-300 transition" id="drop-zone">
                <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p id="file-label" class="text-sm text-gray-400 mb-2">Ningún archivo seleccionado</p>
                <label class="cursor-pointer inline-block px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition">
                    Elegir archivo
                    <input type="file" name="file" id="file-input" accept=".xlsx,.xls,.csv" class="hidden"
                        onchange="document.getElementById('file-label').textContent = this.files[0]?.name ?? 'Ningún archivo seleccionado'; document.getElementById('file-label').className = this.files[0] ? 'text-sm text-teal-700 font-medium mb-2' : 'text-sm text-gray-400 mb-2'">
                </label>
                @error('file')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" id="submit-btn"
                    onclick="this.disabled=true; this.innerHTML='<svg class=\'w-4 h-4 animate-spin inline mr-2\' fill=\'none\' viewBox=\'0 0 24 24\'><circle class=\'opacity-25\' cx=\'12\' cy=\'12\' r=\'10\' stroke=\'currentColor\' stroke-width=\'4\'></circle><path class=\'opacity-75\' fill=\'currentColor\' d=\'M4 12a8 8 0 018-8v8z\'></path></svg>Procesando…'; this.form.submit()"
                    class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Importar usuarios
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="px-5 py-2.5 border border-gray-200 text-gray-500 text-sm rounded-lg hover:bg-gray-50 transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

</div>
@endsection
