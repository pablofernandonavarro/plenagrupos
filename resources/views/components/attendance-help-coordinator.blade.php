<x-help-card title="Sistema de Asistencias" icon="check" color="purple">
    <div class="space-y-3">
        <div>
            <p class="font-medium mb-1">🎯 Registro automático</p>
            <p class="text-xs opacity-80">Los pacientes escanean el QR para registrar su entrada. El sistema guarda el timestamp de <code class="bg-white/50 px-1 rounded">attended_at</code>.</p>
        </div>

        <div>
            <p class="font-medium mb-1">⏰ Cierre automático</p>
            <p class="text-xs opacity-80">Las asistencias se cierran automáticamente cuando:</p>
            <ul class="text-xs opacity-80 ml-4 mt-1 space-y-1">
                <li>• Termina la ventana horaria de la sesión (hora de inicio + duración configurada)</li>
                <li>• El coordinador cierra manualmente la sesión</li>
                <li>• Se ejecuta el comando <code class="bg-white/50 px-1 rounded">attendances:auto-close</code></li>
            </ul>
        </div>

        <div>
            <p class="font-medium mb-1">⏱️ Cálculo de tiempo</p>
            <p class="text-xs opacity-80">Duración = <code class="bg-white/50 px-1 rounded">left_at - attended_at</code> (en minutos). Si un paciente llegó tarde, solo cuenta el tiempo que estuvo presente.</p>
        </div>

        <div>
            <p class="font-medium mb-1">🔧 Checkout manual</p>
            <p class="text-xs opacity-80">Podés cerrar asistencias individualmente desde la vista en vivo si un paciente se retira antes.</p>
        </div>

        <div class="pt-2 border-t border-current opacity-30">
            <p class="text-xs">💡 Las asistencias de días anteriores se cierran automáticamente a las 00:00 con la duración configurada del grupo.</p>
        </div>
    </div>
</x-help-card>
