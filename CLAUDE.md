# CLAUDE.md

Este archivo le da contexto a Claude Code (claude.ai/code) para trabajar en este repositorio.

## Proyecto

App Laravel 13 ("Plena Grupos") para gestionar programas grupales de descenso de peso/bienestar: los pacientes registran su asistencia a sesiones grupales escaneando un QR, los coordinadores llevan el seguimiento de asistencia/peso/estudios InBody, y los administradores gestionan grupos, usuarios y reglas de plan. Vistas Blade renderizadas en servidor (sin Livewire, sin capa SPA/API) — los textos de la interfaz están en español (`APP_LOCALE=es`).

## Comandos

- `composer dev` — levanta todo el stack local en paralelo: `php artisan serve`, worker de colas, `php artisan pail` (logs en vivo) y `npm run dev` (Vite).
- `php artisan test` / `composer test` — corre toda la suite de tests (phpunit.xml usa SQLite en memoria).
- `php artisan test --filter=NombreDelTest` — corre un solo test.
- `vendor/bin/pint` — aplica el estilo de código (preset default de Laravel, sin `pint.json` propio).
- `npm run build` — assets de frontend para producción (Tailwind v4 + Vite).
- `php artisan migrate` — corre las migraciones (MySQL en producción, SQLite en tests).

## Arquitectura

### Roles y ruteo
Tres grupos de rutas protegidos por rol en `routes/web.php` (`admin`, `coordinator`, `patient`), protegidos por el alias de middleware `role:<nombre>` (`App\Http\Middleware\RoleMiddleware`, registrado en `bootstrap/app.php`). El rol es una simple columna string en `users.role` — no hay paquete de permisos. `User::isAdmin()/isCoordinator()/isPatient()` son los checks canónicos.

### Scheduling de grupos — la lógica de dominio central
`app/Models/Group.php` es la parte más compleja y más editada del código. Toda la lógica horaria está fijada a `America/Argentina/Buenos_Aires`. Dos conceptos relacionados pero *no intercambiables*:
- `status` (accessor, `'pending' | 'active' | 'closed'`) — una vista más permisiva usada en listados de admin, ventanas de reapertura, etc.
- `isLiveSessionNow()` — el check estricto de "¿hay una sesión ocurriendo en este instante exacto?". Esto es lo que deben usar badges como "En vivo". Buena parte del historial reciente de este repo (ver git log) son fixes de vistas que chequeaban `status` o un flag manual `sessionEndedToday` en lugar de llamar a `isLiveSessionNow()` — al tocar cualquier indicador "en vivo" en una vista, usar `isLiveSessionNow()`, no `status`.

Un grupo es no-recurrente (`recurrence_type = 'none'`, iniciado/detenido manualmente vía `active`/`started_at`/`ended_at`) o recurrente (`daily|weekly|monthly|yearly`, calculado a partir de `meeting_days`/`meeting_time`/`session_duration_minutes`/`recurrence_interval`, sin necesidad de cron para cambiar de estado — todo se deriva en el momento de leer). `TESTING_LIVE_SESSION.md` documenta la tabla de verdad esperada para `isLiveSessionNow()` en todos estos casos y es una referencia útil al modificar esta lógica.

### Flujo de check-in por QR
`GroupJoinController` (`/grupo/{token}`) es el camino de check-in en vivo: valida que el grupo esté activo, aplica los límites mensuales de visitas vía `PlanRule` (indexado por `patient_plan` = `User::faseEfectiva()`, es decir `fase_actual` con fallback a `plan`, y `group_type`: `descenso|mantenimiento|mantenimiento_pleno`), y luego crea o reutiliza un `GroupAttendance` del día. `GroupSession` es una fila por día calendario (zona AR) por grupo (`Group::findOrCreateSessionForDate`), usada para agrupar asistencias y derivar un número de secuencia global. `group_patient` es una tabla pivot con seguimiento de ingreso/salida y columnas de atribución UTM.

**Código legacy/muerto**: `TherapeuticSession`, `SessionAttendance` y `SessionJoinController` implementan un modelo de sesión QR más viejo y paralelo. No hay ninguna ruta registrada para `SessionJoinController` en `routes/web.php` — el flujo en vivo es enteramente `Group`/`GroupAttendance`/`GroupSession` vía `GroupJoinController`. No extender el camino de `TherapeuticSession` asumiendo que está activo.

### Extracción de InBody con IA
`Coordinator\InbodyController` y `Patient\InbodyController` envían fotos de reportes InBody a la API de visión de Groq (`config/services.php` → `groq.key` / `GROQ_API_KEY`) con un prompt fijo, parseando la respuesta JSON hacia los campos de `InbodyRecord` (peso, grasa corporal, masa muscular esquelética, etc.).

### Scheduling
Tres comandos programados en `routes/console.php`, los dos primeros fijados a la zona horaria `America/Argentina/Buenos_Aires`: `sessions:generate-recurring` (diario a las 08:00, pre-genera los `GroupSession` del día siguiente), `attendances:auto-close` (cada 5 min, cierra asistencias una vez pasada la ventana horaria de la sesión) y `pulse:check` (cada minuto, agrega datos para Laravel Pulse).

### Base de conocimiento para IA
`AiDocument` (CRUD en `Admin\AiDocumentController`, resembrado por `AiDocumentSeeder`) guarda documentos activos/ordenados que se usan como contexto adicional para funcionalidades de IA de la app (p. ej. la extracción de InBody). No confundir con `InbodyRecord`, que es el dato estructurado ya extraído.

### Deploy
`.github/workflows/deploy.yml` sincroniza el repo directamente a un servidor de producción por SSH (rsync) en cada push a `main` (sin correr tests en el pipeline), y luego ejecuta `migrate --force` y resiembra `AiDocumentSeeder`.
