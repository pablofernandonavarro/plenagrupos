<x-help-card title="¿Cómo funcionan las asistencias?" icon="clock" color="teal">
    <div class="space-y-3">
        <div>
            <p class="font-medium mb-1">📍 Registrar entrada</p>
            <p class="text-xs opacity-80">Escaneá el código QR del grupo cuando llegás a la sesión. Esto registra tu hora de entrada.</p>
        </div>

        <div>
            <p class="font-medium mb-1">⏱️ Tiempo en sesión</p>
            <p class="text-xs opacity-80">Tu asistencia se cierra automáticamente cuando termina la sesión ({{ $sessionDuration ?? '120' }} minutos). El tiempo se calcula desde tu entrada hasta la salida.</p>
        </div>

        <div>
            <p class="font-medium mb-1">📊 Tu historial</p>
            <p class="text-xs opacity-80">Podés ver todas tus asistencias, minutos acumulados y progreso en tu dashboard.</p>
        </div>

        <div class="pt-2 border-t border-current opacity-30">
            <p class="text-xs">💡 <strong>Tip:</strong> Llegá puntual para aprovechar toda la sesión y acumular el máximo de tiempo terapéutico.</p>
        </div>
    </div>
</x-help-card>
