# Testing isLiveSessionNow() - Todos los escenarios

## Casos a verificar

### ✅ CASO 1: Programa cerrado permanentemente
**Setup:**
- `recurrence_end_date` < hoy
- O grupo manual con `ended_at` != NULL y != hoy

**Expectativa:** `isLiveSessionNow() = FALSE` (siempre)

---

### ✅ CASO 2: Sesión cerrada manualmente HOY
**Setup:**
- `ended_at` = hoy (ej: 2025-04-01 20:30:00)
- Hora actual: cualquiera después del cierre

**Expectativa:** `isLiveSessionNow() = FALSE`

**Ejemplo real (Grupo 18):**
- Cerrado hoy a las 20:30
- Hora actual: 22:12
- Resultado esperado: FALSE ❌ NO en vivo

---

### ✅ CASO 3a: Grupo recurrente CON horario - EN ventana
**Setup:**
- `recurrence_type` = 'weekly'
- `meeting_days` = ['Martes']
- `meeting_time` = '19:30'
- `session_duration_minutes` = 120
- Hoy = Martes 19:45

**Expectativa:** `isLiveSessionNow() = TRUE` ✅

---

### ✅ CASO 3b: Grupo recurrente CON horario - FUERA de ventana
**Setup:**
- Misma config que 3a
- Hoy = Martes 22:00 (después de 19:30 + 120min = 21:30)

**Expectativa:** `isLiveSessionNow() = FALSE` ❌

---

### ✅ CASO 3c: Grupo recurrente - DÍA INCORRECTO
**Setup:**
- `meeting_days` = ['Martes']
- Hoy = Miércoles

**Expectativa:** `isLiveSessionNow() = FALSE` ❌

---

### ✅ CASO 4a: Grupo manual CON horario - activo y en ventana
**Setup:**
- `recurrence_type` = 'none'
- `active` = true
- `meeting_time` = '10:00'
- `session_duration_minutes` = 60
- `meeting_days` = ['Lunes']
- Hoy = Lunes 10:30

**Expectativa:** `isLiveSessionNow() = TRUE` ✅

---

### ✅ CASO 4b: Grupo manual CON horario - inactivo
**Setup:**
- Misma config que 4a
- `active` = false

**Expectativa:** `isLiveSessionNow() = FALSE` ❌

---

### ✅ CASO 5: Grupo SIN horario - control manual
**Setup:**
- `meeting_time` = NULL
- `recurrence_type` = 'none'
- `started_at` = hoy 09:00
- `ended_at` = NULL

**Expectativa:** `isLiveSessionNow() = TRUE` ✅

---

### ✅ CASO 6: Grupo SIN horario - no iniciado
**Setup:**
- `meeting_time` = NULL
- `started_at` = NULL

**Expectativa:** `isLiveSessionNow() = FALSE` ❌

---

## Comando para probar en Tinker

```php
$group = App\Models\Group::find(18);

echo "Grupo: {$group->name}\n";
echo "Tipo: {$group->recurrence_type}\n";
echo "Horario: {$group->meeting_time}\n";
echo "Duración: {$group->session_duration_minutes} min\n";
echo "Activo: " . ($group->active ? 'Sí' : 'No') . "\n";
echo "Started: {$group->started_at}\n";
echo "Ended: {$group->ended_at}\n";
echo "Hora actual (ARG): " . \Carbon\Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d H:i:s') . "\n";
echo "\n";
echo "isLiveSessionNow(): " . ($group->isLiveSessionNow() ? 'TRUE ✅' : 'FALSE ❌') . "\n";

// Para grupo 18 a las 22:12 con sesión cerrada hoy a las 20:30:
// Esperado: FALSE ❌
```

## Casos edge

1. **Sesión que termina después de medianoche:**
   - Meeting time: 23:00, duration: 180 min
   - Sesión real: 23:00 → 02:00 (día siguiente)
   - TODO: Verificar comportamiento

2. **Cambio de horario de verano/invierno:**
   - Zona horaria maneja automáticamente
   - No requiere ajuste manual

3. **Sesión cerrada y reabierta el mismo día:**
   - Grupo recurrente con ended_at hoy
   - Expectativa: NO puede reabrir hasta nuevo día de reunión
