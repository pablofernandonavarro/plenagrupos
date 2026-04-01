<x-help-card title="Sistema de Asistencias - Referencia Técnica" icon="info" color="blue">
    <div class="space-y-3">
        <div>
            <p class="font-medium mb-1">📋 Modelo de datos</p>
            <p class="text-xs opacity-80"><code class="bg-white/50 px-1 rounded">GroupAttendance</code>: Registra cada asistencia con <code class="bg-white/50 px-1 rounded">attended_at</code> (entrada) y <code class="bg-white/50 px-1 rounded">left_at</code> (salida).</p>
        </div>

        <div>
            <p class="font-medium mb-1">⚙️ Configuración por grupo</p>
            <p class="text-xs opacity-80">Cada grupo tiene <code class="bg-white/50 px-1 rounded">session_duration_minutes</code> (15-480 min, default 120). Define la duración de cada sesión.</p>
        </div>

        <div>
            <p class="font-medium mb-1">🤖 Cierre automático</p>
            <p class="text-xs opacity-80">Comando: <code class="bg-white/50 px-1 rounded">php artisan attendances:auto-close</code></p>
            <p class="text-xs opacity-80 mt-1">Ejecuta las siguientes acciones:</p>
            <ol class="text-xs opacity-80 ml-4 mt-1 space-y-1 list-decimal">
                <li>Cierra asistencias de días anteriores (huérfanas)</li>
                <li>Cierra asistencias de grupos recurrentes cuya ventana terminó</li>
                <li>Cierra asistencias de grupos cerrados manualmente</li>
                <li>Resetea flags <code class="bg-white/50 px-1 rounded">started_at</code> obsoletos</li>
                <li>Saca pacientes de grupos que alcanzaron <code class="bg-white/50 px-1 rounded">recurrence_end_date</code></li>
            </ol>
        </div>

        <div>
            <p class="font-medium mb-1">📊 Cálculo de tiempo</p>
            <p class="text-xs opacity-80">Minutos = <code class="bg-white/50 px-1 rounded">attended_at->diffInMinutes(left_at)</code>. Se suma por ciclo de plan (30 días) o globalmente según vista.</p>
        </div>

        <div>
            <p class="font-medium mb-1">🔄 Grupos recurrentes</p>
            <p class="text-xs opacity-80">El estado <code class="bg-white/50 px-1 rounded">active/pending/closed</code> se calcula dinámicamente según horario actual, no requiere cron para abrir/cerrar.</p>
        </div>

        <div class="pt-2 border-t border-current opacity-30">
            <p class="text-xs">⚠️ <strong>Importante:</strong> Configurar cron job para ejecutar <code class="bg-white/50 px-1 rounded">attendances:auto-close</code> cada hora o al final del día.</p>
        </div>
    </div>
</x-help-card>
