@extends('layouts.app')
@section('title', 'Guía del Coordinador')

@section('content')
<div class="space-y-6 max-w-4xl">

    {{-- Header --}}
    <div>
        <a href="{{ route('coordinator.dashboard') }}" class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver al dashboard
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Guía del Coordinador</h1>
        <p class="text-gray-500 text-sm mt-0.5">Información técnica sobre el funcionamiento del sistema</p>
    </div>

    {{-- Attendance System --}}
    <x-attendance-help-coordinator />

    {{-- Session Management --}}
    <x-help-card title="Gestión de Sesiones" icon="check" color="green">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">🟢 Abrir sesión manualmente</p>
                <p class="text-xs opacity-80">Si tu grupo es <strong>manual</strong> (no recurrente), debés abrirlo con el botón "Abrir sesión". Los grupos <strong>recurrentes</strong> se abren automáticamente según horario configurado.</p>
            </div>

            <div>
                <p class="font-medium mb-1">🔴 Cerrar sesión</p>
                <p class="text-xs opacity-80">Podés cerrar la sesión manualmente antes de que termine la ventana horaria. Esto cierra todas las asistencias abiertas con el timestamp actual.</p>
            </div>

            <div>
                <p class="font-medium mb-1">👥 Vista en vivo</p>
                <p class="text-xs opacity-80">Desde "Ver asistencias en vivo" podés:</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• Ver quién está presente ahora mismo</li>
                    <li>• Hacer checkout manual de pacientes que se retiran</li>
                    <li>• Agregar notas del coordinador a cada asistencia</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">📋 QR del grupo</p>
                <p class="text-xs opacity-80">Mostrá el QR en pantalla o impreso para que los pacientes se registren al llegar. El QR es único por grupo.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Patient Management --}}
    <x-help-card title="Seguimiento de Pacientes" icon="info" color="blue">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📊 Perfil del paciente</p>
                <p class="text-xs opacity-80">Desde la vista individual de cada paciente podés:</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• Ver evolución de peso y gráficos</li>
                    <li>• Actualizar fase actual (Adaptación/Pérdida/Mantenimiento)</li>
                    <li>• Modificar perfil clínico</li>
                    <li>• Agregar notas a asistencias específicas</li>
                    <li>• Registrar datos InBody</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">⚖️ Peso de mantenimiento</p>
                <p class="text-xs opacity-80">Cuando un paciente alcanza su peso objetivo, podés establecer su peso de mantenimiento. Esto se registra en su membresía al grupo.</p>
            </div>

            <div>
                <p class="font-medium mb-1">🎯 Peso piso y techo</p>
                <p class="text-xs opacity-80">Define los límites saludables para cada paciente. El sistema alertará cuando estén fuera de rango.</p>
            </div>
        </div>
    </x-help-card>

    {{-- InBody --}}
    <x-help-card title="Registros InBody" icon="check" color="teal">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📸 Extracción automática</p>
                <p class="text-xs opacity-80">Podés subir una foto del reporte InBody y el sistema extraerá automáticamente los valores usando IA.</p>
            </div>

            <div>
                <p class="font-medium mb-1">✏️ Registro manual</p>
                <p class="text-xs opacity-80">También podés ingresar los valores manualmente si preferís o si la extracción automática no funciona correctamente.</p>
            </div>

            <div>
                <p class="font-medium mb-1">📈 Seguimiento</p>
                <p class="text-xs opacity-80">Los registros InBody se muestran en el perfil del paciente junto con su evolución de peso regular.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Best Practices --}}
    <x-help-card title="Mejores Prácticas" icon="info" color="purple">
        <div class="space-y-2">
            <p class="font-medium mb-1">💡 Recomendaciones</p>
            <ul class="text-xs opacity-80 ml-4 space-y-1">
                <li>• Abrí la sesión 5-10 minutos antes del horario oficial</li>
                <li>• Mantené el QR visible durante toda la sesión para llegadas tarde</li>
                <li>• Revisá la vista en vivo periódicamente para detectar ausencias</li>
                <li>• Agregá notas relevantes durante la sesión (no después)</li>
                <li>• Verificá que todos tengan registro de peso antes de cerrar</li>
                <li>• Cerrá la sesión manualmente si termina antes de lo previsto</li>
            </ul>
        </div>
    </x-help-card>

</div>
@endsection
