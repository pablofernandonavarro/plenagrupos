@extends('layouts.app')
@section('title', 'Documentación del Sistema')

@section('content')
<div class="space-y-6 max-w-5xl">

    {{-- Header --}}
    <div>
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-700 mb-3">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver al dashboard
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Documentación del Sistema</h1>
        <p class="text-gray-500 text-sm mt-0.5">Referencia técnica completa del funcionamiento interno</p>
    </div>

    {{-- Attendance System --}}
    <x-attendance-help-admin />

    {{-- Group Types --}}
    <x-help-card title="Tipos de Grupos" icon="info" color="purple">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">🔵 Grupos Manuales (recurrence_type: none)</p>
                <p class="text-xs opacity-80">Requieren apertura/cierre manual por coordinador o admin.</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <code class="bg-white/50 px-1 rounded">active=true</code> → Sesión abierta</li>
                    <li>• <code class="bg-white/50 px-1 rounded">started_at</code> registra cuándo se abrió</li>
                    <li>• <code class="bg-white/50 px-1 rounded">ended_at</code> registra cuándo se cerró</li>
                    <li>• Se cierran auto después de <code class="bg-white/50 px-1 rounded">session_duration_minutes</code></li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">🔄 Grupos Recurrentes (daily/weekly/monthly/yearly)</p>
                <p class="text-xs opacity-80">Se abren/cierran automáticamente según horario configurado.</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• Estado calculado dinámicamente: <code class="bg-white/50 px-1 rounded">isLiveSessionNow()</code></li>
                    <li>• <code class="bg-white/50 px-1 rounded">meeting_days</code>: días de la semana (array)</li>
                    <li>• <code class="bg-white/50 px-1 rounded">meeting_time</code>: hora de inicio (HH:mm)</li>
                    <li>• <code class="bg-white/50 px-1 rounded">recurrence_interval</code>: cada N días/semanas/meses</li>
                    <li>• <code class="bg-white/50 px-1 rounded">recurrence_end_date</code>: fecha de finalización</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">📍 Modalidades</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <strong>Presencial:</strong> Solo QR escaneable</li>
                    <li>• <strong>Virtual:</strong> Link de acceso + QR</li>
                    <li>• <strong>Híbrido:</strong> Ambas opciones disponibles</li>
                </ul>
            </div>
        </div>
    </x-help-card>

    {{-- Session States --}}
    <x-help-card title="Estados de Sesión" icon="check" color="green">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">Estado del grupo (status attribute)</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <code class="bg-white/50 px-1 rounded">pending</code>: No iniciado aún</li>
                    <li>• <code class="bg-white/50 px-1 rounded">active</code>: En sesión ahora (ventana horaria activa)</li>
                    <li>• <code class="bg-white/50 px-1 rounded">closed</code>: Finalizado permanentemente</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">Funciones helper en Group model</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <code class="bg-white/50 px-1 rounded">isLiveSessionNow()</code>: ¿Hay sesión en curso ahora?</li>
                    <li>• <code class="bg-white/50 px-1 rounded">meetsOnDate($date)</code>: ¿El grupo se reúne esta fecha?</li>
                    <li>• <code class="bg-white/50 px-1 rounded">isProgramVigente()</code>: ¿Programa activo? (no finalizado)</li>
                    <li>• <code class="bg-white/50 px-1 rounded">isProgramClosed()</code>: ¿Programa terminado?</li>
                    <li>• <code class="bg-white/50 px-1 rounded">nextSessionAt</code>: Próxima sesión programada</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">⏰ Zona horaria</p>
                <p class="text-xs opacity-80">Todo el sistema usa <code class="bg-white/50 px-1 rounded">America/Argentina/Buenos_Aires</code> para cálculos de fechas/horas.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Database Schema --}}
    <x-help-card title="Esquema de Base de Datos" icon="info" color="teal">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">Tablas principales</p>
                <div class="text-xs opacity-80 space-y-2 mt-2">
                    <div class="bg-white/50 rounded p-2">
                        <p class="font-mono font-semibold">groups</p>
                        <p class="mt-1">Información del grupo, horarios, recurrencia</p>
                    </div>
                    <div class="bg-white/50 rounded p-2">
                        <p class="font-mono font-semibold">group_sessions</p>
                        <p class="mt-1">Una fila por día calendario por grupo (sequence_number global)</p>
                    </div>
                    <div class="bg-white/50 rounded p-2">
                        <p class="font-mono font-semibold">group_attendances</p>
                        <p class="mt-1">Registro individual de cada asistencia (attended_at, left_at)</p>
                    </div>
                    <div class="bg-white/50 rounded p-2">
                        <p class="font-mono font-semibold">group_patient (pivot)</p>
                        <p class="mt-1">Membresía de pacientes (joined_at, left_at, maintenance_weight)</p>
                    </div>
                    <div class="bg-white/50 rounded p-2">
                        <p class="font-mono font-semibold">group_membership_logs</p>
                        <p class="mt-1">Historial de altas/bajas de pacientes</p>
                    </div>
                    <div class="bg-white/50 rounded p-2">
                        <p class="font-mono font-semibold">weight_records</p>
                        <p class="mt-1">Registros de peso (vinculados a attendance_id)</p>
                    </div>
                    <div class="bg-white/50 rounded p-2">
                        <p class="font-mono font-semibold">inbody_records</p>
                        <p class="mt-1">Mediciones InBody completas</p>
                    </div>
                </div>
            </div>
        </div>
    </x-help-card>

    {{-- Commands --}}
    <x-help-card title="Comandos Artisan" icon="check" color="blue">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1 font-mono text-xs">php artisan attendances:auto-close</p>
                <p class="text-xs opacity-80 mb-2">Cierra asistencias abiertas cuya ventana horaria terminó.</p>
                <p class="text-xs opacity-80"><strong>Frecuencia recomendada:</strong> Cada hora o al final del día (cron)</p>
                <p class="text-xs opacity-80 mt-1 bg-white/50 rounded p-2 font-mono">
                    0 * * * * cd /path/to/app && php artisan attendances:auto-close >> /dev/null 2>&1
                </p>
            </div>

            <div>
                <p class="font-medium mb-1 font-mono text-xs">php artisan sessions:generate-recurring</p>
                <p class="text-xs opacity-80">Genera entradas en <code class="bg-white/50 px-1 rounded">group_sessions</code> para grupos recurrentes (opcional, se crean on-demand).</p>
            </div>
        </div>
    </x-help-card>

    {{-- Plan Rules --}}
    <x-help-card title="Reglas de Planes" icon="info" color="purple">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">Sistema de límites</p>
                <p class="text-xs opacity-80">Tabla <code class="bg-white/50 px-1 rounded">plan_rules</code> define límites por fase:</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <strong>Adaptación:</strong> Límites mensuales de sesiones</li>
                    <li>• <strong>Pérdida:</strong> Límites más flexibles</li>
                    <li>• <strong>Mantenimiento:</strong> Límites ajustados</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">Ciclo de plan</p>
                <p class="text-xs opacity-80">Los pacientes tienen ciclos de 30 días desde <code class="bg-white/50 px-1 rounded">plan_start_date</code>. El método <code class="bg-white/50 px-1 rounded">currentPlanCycle()</code> en User calcula el ciclo actual.</p>
            </div>

            <div>
                <p class="font-medium mb-1">Fase efectiva</p>
                <p class="text-xs opacity-80"><code class="bg-white/50 px-1 rounded">faseEfectiva()</code> = <code class="bg-white/50 px-1 rounded">fase_actual ?? plan</code>. Permite al coordinador override manual de la fase.</p>
            </div>
        </div>
    </x-help-card>

    {{-- Analytics & Exports --}}
    <x-help-card title="Analíticas y Exportación" icon="info" color="green">
        <div class="space-y-3">
            <div>
                <p class="font-medium mb-1">📊 Analytics disponibles</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• Análisis por grupos (asistencia, peso promedio, tendencias)</li>
                    <li>• Seguimiento InBody (masa muscular, grasa, etc.)</li>
                    <li>• Análisis de cohortes (comparación entre grupos)</li>
                </ul>
            </div>

            <div>
                <p class="font-medium mb-1">📥 Exports (Excel)</p>
                <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                    <li>• <code class="bg-white/50 px-1 rounded">/admin/exports/asistencias</code>: Todas las asistencias</li>
                    <li>• <code class="bg-white/50 px-1 rounded">/admin/exports/pesos</code>: Registros de peso</li>
                    <li>• <code class="bg-white/50 px-1 rounded">/admin/exports/inbody</code>: Mediciones InBody</li>
                    <li>• <code class="bg-white/50 px-1 rounded">/admin/exports/pacientes-por-grupo</code>: Membresías</li>
                </ul>
            </div>
        </div>
    </x-help-card>

    {{-- Technical Notes --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-5 py-4">
        <p class="text-sm font-semibold text-yellow-900 mb-2">⚠️ Notas Técnicas Importantes</p>
        <ul class="text-xs text-yellow-800 space-y-1 ml-4">
            <li>• No usar <code class="bg-white/50 px-1 rounded">storage:link</code> en deploy (requiere exec())</li>
            <li>• Avatares se guardan en <code class="bg-white/50 px-1 rounded">public/avatars/</code></li>
            <li>• Laravel Pulse requiere <code class="bg-white/50 px-1 rounded">PULSE_ENABLED=true</code> en .env</li>
            <li>• Queue driver: database (no requiere Redis/Beanstalk)</li>
            <li>• Session driver: database (más confiable que file en múltiples workers)</li>
        </ul>
    </div>

</div>
@endsection
