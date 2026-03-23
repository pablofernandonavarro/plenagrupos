@extends('layouts.app')
@section('title', 'Importar usuarios')

@section('content')
<div class="max-w-2xl space-y-5">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Importar usuarios desde Excel</h1>
        <a href="{{ route('admin.users.import.template') }}"
           class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Descargar modelo
        </a>
    </div>

    {{-- Format guide --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm text-blue-700 space-y-2">
        <p class="font-semibold">Formato esperado del archivo (.xlsx, .xls o .csv)</p>
        <p>La primera fila debe contener los encabezados. Columnas reconocidas:</p>
        <div class="overflow-x-auto">
            <table class="text-xs w-full mt-1 border-collapse">
                <thead>
                    <tr class="bg-blue-100">
                        <th class="px-2 py-1 text-left border border-blue-200">Columna</th>
                        <th class="px-2 py-1 text-left border border-blue-200">Descripción</th>
                        <th class="px-2 py-1 text-left border border-blue-200">Requerido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['email',        'Email del usuario',                                  'Sí'],
                        ['nombre',       'Nombre completo',                                    'Sí'],
                        ['telefono',     'Teléfono',                                           'No'],
                        ['plan',         'descenso / mantenimiento / mantenimiento_pleno',     'No'],
                        ['fecha_inicio', 'Fecha de inicio del plan (ej: 18/03/2026)',          'No'],
                        ['peso_ideal',   'Peso ideal en kg',                                   'No'],
                        ['peso_piso',    'Peso mínimo de mantenimiento',                       'No'],
                        ['peso_techo',   'Peso máximo de mantenimiento',                       'No'],
                        ['rol',          'patient (default) o coordinator',                   'No'],
                    ] as [$col, $desc, $req])
                    <tr class="border border-blue-200">
                        <td class="px-2 py-1 font-mono font-semibold">{{ $col }}</td>
                        <td class="px-2 py-1">{{ $desc }}</td>
                        <td class="px-2 py-1">{{ $req }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-blue-600">
            Si el email ya existe, se actualizan los datos. Si no existe, se crea el usuario con contraseña aleatoria (deberá cambiarla).
        </p>
    </div>

    {{-- Errors from last import --}}
    @if(session('import_errors'))
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-700">
        <p class="font-semibold mb-2">Filas con errores (el resto se importó correctamente):</p>
        <ul class="list-disc list-inside space-y-0.5 text-xs">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Upload form --}}
    <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-gray-100 shadow-sm px-6 py-5 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Archivo Excel / CSV</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv"
                class="block w-full text-sm text-gray-600
                       file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                       file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700
                       hover:file:bg-teal-100 cursor-pointer">
            @error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="flex gap-3 pt-1">
            <button type="submit"
                class="bg-teal-600 hover:bg-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                Importar
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="px-6 py-2.5 border border-gray-200 text-gray-500 text-sm rounded-lg hover:bg-gray-50 transition">
                Cancelar
            </a>
        </div>
    </form>

</div>
@endsection
