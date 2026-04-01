@extends('layouts.app')
@section('title', 'Ayuda')

@section('content')
<div class="space-y-6 max-w-3xl">

    {{-- Header --}}
    <div>
        <a href="{{ route('patient.dashboard') }}" class="inline-flex items-center text-sm text-teal-600 hover:text-teal-700 mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver al inicio
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Centro de Ayuda</h1>
        <p class="text-gray-500 text-sm mt-0.5">Todo lo que necesitás saber sobre tu grupo terapéutico</p>
    </div>

    {{-- Attendance System --}}
    <x-attendance-help-patient :sessionDuration="120" />

    {{-- Weight Tracking --}}
    <x-help-card title="Registro de peso" icon="check" color="green">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📏 ¿Cuándo registrar?</p>
                <p class="text-xs opacity-80">Registrá tu peso cada vez que asistís a una sesión. Esto te permite ver tu evolución y progreso en tiempo real.</p>
            </div>

            <div>
                <p class="font-medium mb-1">⏱️ ¿Olvidé registrar mi peso?</p>
                <p class="text-xs opacity-80">¡No hay problema! En tu <strong>Historial de sesiones</strong> verás un botón "Registrar peso de esta sesión" en las asistencias que no tienen peso. Podés agregarlo después cuando quieras.</p>
            </div>

            <div>
                <p class="font-medium mb-1">🎯 Peso ideal y rangos</p>
                <p class="text-xs opacity-80">Tu coordinador puede definir un <strong>peso piso</strong> y <strong>peso techo</strong> para tu seguimiento. El objetivo es mantenerte en ese rango saludable.</p>
            </div>

            <div>
                <p class="font-medium mb-1">📈 Gráficos y tendencias</p>
                <p class="text-xs opacity-80">El sistema calcula automáticamente tu tendencia de peso y te muestra si estás bajando, estable o subiendo.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Your Dashboard --}}
    <x-help-card title="Tu Dashboard" icon="info" color="blue">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📊 Estadísticas</p>
                <p class="text-xs opacity-80">En tu inicio podés ver:</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <strong>Último peso:</strong> Tu registro más reciente</li>
                    <li>• <strong>Progreso:</strong> Diferencia entre tu primer y último peso</li>
                    <li>• <strong>Visitas:</strong> Total de veces que registraste peso</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">🔄 Ciclo de plan</p>
                <p class="text-xs opacity-80">Tu plan tiene ciclos de 30 días desde tu fecha de inicio. Podés ver tus sesiones y minutos por ciclo actual.</p>
            </div>

            <div>
                <p class="font-medium mb-1">📅 Historial</p>
                <p class="text-xs opacity-80">Mirá todas tus asistencias anteriores con fecha, hora, duración y peso registrado.</p>
            </div>
        </div>
    </x-help-card>

    {{-- QR Scanner --}}
    <x-help-card title="Escaneo de QR" icon="check" color="teal">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📱 Cómo usar el escáner</p>
                <ol class="text-xs opacity-80 ml-4 mt-1 space-y-1 list-decimal">
                    <li>Tocá el botón "Escanear QR del grupo" en tu inicio</li>
                    <li>Permitir acceso a la cámara cuando te lo pida</li>
                    <li>Apuntá la cámara al código QR mostrado por tu coordinador</li>
                    <li>El sistema te registrará automáticamente en la sesión</li>
                </ol>
            </div>

            <div>
                <p class="font-medium mb-1">⚠️ Problemas comunes</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• Si no funciona, verificá los permisos de cámara en tu navegador</li>
                    <li>• Asegurate de tener buena iluminación</li>
                    <li>• Mantené el QR centrado en la pantalla</li>
                </ul>
            </div>
        </div>
    </x-help-card>

    {{-- Support --}}
    <div class="bg-gray-50 border border-gray-200 rounded-xl px-5 py-4 text-center">
        <p class="text-sm text-gray-700 mb-2">¿Necesitás más ayuda?</p>
        <p class="text-xs text-gray-500">Consultá con tu coordinador o administrador del programa.</p>
    </div>

</div>
@endsection
