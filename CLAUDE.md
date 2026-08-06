# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Proyecto

App Laravel 13 ("Plena Grupos") para gestionar programas grupales de descenso de peso/bienestar: los pacientes registran su asistencia a sesiones grupales escaneando un QR, los coordinadores llevan el seguimiento de asistencia/peso/estudios InBody, y los administradores gestionan grupos, usuarios y reglas de plan. Vistas Blade renderizadas en servidor (sin Livewire, sin capa SPA/API) — los textos de la interfaz están en español (`APP_LOCALE=es`).

## Comandos

- `composer dev` — levanta todo el stack local en paralelo: `php artisan serve`, worker de colas, `php artisan pail` (logs en vivo) y `npm run dev` (Vite).
- `composer setup` — setup completo desde cero: `composer install`, copia `.env.example`, genera key, corre migraciones y siembra seeders.
- `php artisan test` / `composer test` — corre toda la suite de tests (phpunit.xml usa SQLite en memoria).
- `php artisan test --filter=NombreDelTest` — corre un solo test.
- `vendor/bin/pint` — aplica el estilo de código (preset default de Laravel, sin `pint.json` propio).
- `npm run build` — assets de frontend para producción (Tailwind v4 + Vite).
- `php artisan migrate` — corre las migraciones (MySQL en producción, SQLite en tests).

## Arquitectura

### Roles y ruteo
Cinco roles posibles en la columna string `users.role` (`admin`, `coordinator`, `patient`, `medico`, `nutricionista`) — no hay paquete de permisos. Tres grupos de rutas principales en `routes/web.php` (`admin`, `coordinator`, `patient`), protegidos por el alias de middleware `role:<nombre>` (`App\Http\Middleware\RoleMiddleware`, registrado en `bootstrap/app.php`). `User::isAdmin()/isCoordinator()/isPatient()` son los checks canónicos para esos tres; `medico`/`nutricionista` son roles de profesional que solo aterrizan en la agenda de turnos (`role:admin,medico,nutricionista` sobre `admin/turnos/calendario*`, ver sección Turnos) y se identifican por `$user->role` directamente. El redirect raíz en `routes/web.php` decide destino según `role`.

### Scheduling de grupos — la lógica de dominio central
`app/Models/Group.php` es la parte más compleja y más editada del código. Toda la lógica horaria está fijada a `America/Argentina/Buenos_Aires`. Dos conceptos relacionados pero *no intercambiables*:
- `status` (accessor, `'pending' | 'active' | 'closed'`) — una vista más permisiva usada en listados de admin, ventanas de reapertura, etc.
- `isLiveSessionNow()` — el check estricto de "¿hay una sesión ocurriendo en este instante exacto?". Esto es lo que deben usar badges como "En vivo". Buena parte del historial reciente de este repo (ver git log) son fixes de vistas que chequeaban `status` o un flag manual `sessionEndedToday` en lugar de llamar a `isLiveSessionNow()` — al tocar cualquier indicador "en vivo" en una vista, usar `isLiveSessionNow()`, no `status`.

Un grupo es no-recurrente (`recurrence_type = 'none'`, iniciado/detenido manualmente vía `active`/`started_at`/`ended_at`) o recurrente (`daily|weekly|monthly|yearly`, calculado a partir de `meeting_days`/`meeting_time`/`session_duration_minutes`/`recurrence_interval`, sin necesidad de cron para cambiar de estado — todo se deriva en el momento de leer). `TESTING_LIVE_SESSION.md` documenta la tabla de verdad esperada para `isLiveSessionNow()` en todos estos casos y es una referencia útil al modificar esta lógica.

### Flujo de check-in por QR
`GroupJoinController` (`/grupo/{token}`) es el camino de check-in en vivo: valida que el grupo esté activo, aplica los límites mensuales de visitas vía `PlanRule` (indexado por `patient_plan` = `User::faseEfectiva()`, es decir `fase_actual` con fallback a `plan`, y `group_type`: `descenso|mantenimiento|mantenimiento_pleno`), y luego crea o reutiliza un `GroupAttendance` del día. `GroupSession` es una fila por día calendario (zona AR) por grupo (`Group::findOrCreateSessionForDate`), usada para agrupar asistencias y derivar un número de secuencia global. `group_patient` es una tabla pivot con seguimiento de ingreso/salida y columnas de atribución UTM.

**Dos conceptos de membresía distintos**: `group_patient` (pivot) registra todos los grupos en que un paciente está actualmente inscripto (puede ser más de uno); `users.belonging_group_id` es el grupo "de pertenencia" asignado manualmente por el admin, usado para filtrado y ordenamiento visual (p.ej. en dropdowns de "Agregar paciente"). No son intercambiables.

### Ciclo de vida del paciente
`users.patient_status` tiene tres valores: `active` (en programa), `pause` (suspendido temporalmente) y `exited` (egresado). El cambio de estado lo registra el admin vía `UserController` y queda auditado automáticamente en `GroupMembershipLog` (tabla `group_membership_logs`, `timestamps = false`, campos `joined_at`/`left_at`/`join_source`) cuando el usuario es agregado o removido de un grupo. `WeightRecord` puede estar vinculado opcionalmente a una `GroupAttendance` vía `attendance_id` (nullable desde la migración del 2026-08-04).

**Código legacy/muerto**: `TherapeuticSession`, `SessionAttendance` y `SessionJoinController` implementan un modelo de sesión QR más viejo y paralelo. No hay ninguna ruta registrada para `SessionJoinController` en `routes/web.php` — el flujo en vivo es enteramente `Group`/`GroupAttendance`/`GroupSession` vía `GroupJoinController`. No extender el camino de `TherapeuticSession` asumiendo que está activo.

### Turnos (citas con médico/nutricionista)
Subsistema separado del scheduling de grupos, con su propio modelo de dominio en `app/Models/Appointment.php`. Los profesionales (`role` = `medico`/`nutricionista`) tienen una grilla semanal en `ProfessionalSchedule` (día/hora/duración de slot) y bloqueos puntuales en `ProfessionalUnavailability`. `Appointment::availableSlotsFor()` deriva los huecos libres de un profesional en una fecha (grilla menos turnos ya tomados menos ausencias); `Appointment::bookSlot()` reserva dentro de una transacción, revalida disponibilidad, aplica el cupo mensual por especialidad vía `AppointmentRequirement::requiredCountFor()` sobre el ciclo de plan del paciente (`User::currentPlanCycle()`), y usa el índice único `(professional_id, starts_at)` como backstop final ante condiciones de carrera. `status` de un turno reservado por el propio paciente nace `confirmed`; reservado por un admin nace `pending` hasta que el paciente lo confirma. Rutas: `Admin\AppointmentController`/`Patient\AppointmentController` para crear/listar, y `admin/turnos/calendario*` (middleware `role:admin,medico,nutricionista`) para la agenda visual, donde el profesional ve la suya propia y el admin ve todas.

Las confirmaciones/cancelaciones desde WhatsApp usan links firmados (`middleware('signed')`) a `AppointmentActionController` — el GET solo muestra la pantalla (para no disparar la acción con el preview automático que genera WhatsApp), el POST es el que aplica el cambio. Los mensajes salen por `AppointmentWhatsapp` (best-effort, nunca debe romper el flujo de reservar/cancelar) usando plantillas de `WhatsappTemplate` sobre `WahaClient`, un cliente delgado de la API HTTP de WAHA (`config/services.php` → `waha.url`/`waha.key`/`waha.session`). El comando `turnos:enviar-recordatorios` (`SendAppointmentReminders`) dispara los recordatorios previos a la cita (ver Scheduling).

### Extracción de InBody con IA
`Coordinator\InbodyController` y `Patient\InbodyController` envían fotos de reportes InBody a la API de visión de Groq (`config/services.php` → `groq.key` / `GROQ_API_KEY`) con un prompt fijo, parseando la respuesta JSON hacia los campos de `InbodyRecord` (peso, grasa corporal, masa muscular esquelética, etc.).

### Scheduling
Cinco comandos programados en `routes/console.php`, cuatro de ellos fijados a la zona horaria `America/Argentina/Buenos_Aires`: `sessions:generate-recurring` (diario a las 08:00, pre-genera los `GroupSession` del día siguiente), `attendances:auto-close` (cada 5 min, cierra asistencias una vez pasada la ventana horaria de la sesión), `purge:soft-deleted` (diario, purga permanentemente usuarios/grupos soft-deleted con más de 30 días), `turnos:enviar-recordatorios` (diario a las 10:00, recordatorio por WhatsApp de turnos del día siguiente) y `pulse:check` (cada minuto, sin timezone AR, agrega datos para Laravel Pulse).

### Base de conocimiento para IA
`AiDocument` (CRUD en `Admin\AiDocumentController`, resembrado por `AiDocumentSeeder`) guarda documentos activos/ordenados que se usan como contexto adicional para funcionalidades de IA de la app (p. ej. la extracción de InBody). No confundir con `InbodyRecord`, que es el dato estructurado ya extraído.

### Deploy
`.github/workflows/deploy.yml` sincroniza el repo directamente a un servidor de producción por SSH (rsync) en cada push a `main` (sin correr tests en el pipeline), y luego ejecuta `migrate --force` y resiembra `AiDocumentSeeder`.
