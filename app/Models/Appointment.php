<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id',
        'professional_id',
        'specialty',
        'starts_at',
        'ends_at',
        'status',
        'reminded_at',
        'booked_by',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    private const TZ = 'America/Argentina/Buenos_Aires';

    private const DAY_MAP = [
        'Domingo' => 0, 'Lunes' => 1, 'Martes' => 2, 'Miércoles' => 3,
        'Jueves' => 4, 'Viernes' => 5, 'Sábado' => 6,
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    /**
     * Slots disponibles (Carbon[]) para $professional en $date, a partir de su horario
     * semanal (professional_schedules) menos turnos ya reservados menos ausencias/vacaciones.
     */
    public static function availableSlotsFor(User $professional, Carbon $date): Collection
    {
        $date = $date->copy()->timezone(self::TZ)->startOfDay();
        $dayName = array_search($date->dayOfWeek, self::DAY_MAP, true);

        if ($dayName === false) {
            return collect();
        }

        $schedules = ProfessionalSchedule::query()
            ->where('professional_id', $professional->id)
            ->where('day_of_week', $dayName)
            ->where('active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $unavailabilities = ProfessionalUnavailability::query()
            ->where('professional_id', $professional->id)
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->get();

        if ($unavailabilities->contains(fn ($u) => $u->isFullDay())) {
            return collect();
        }

        $bookedStarts = self::query()
            ->where('professional_id', $professional->id)
            ->whereBetween('starts_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->where('status', '!=', 'cancelled')
            ->pluck('starts_at')
            ->map(fn ($t) => Carbon::parse($t)->timezone(self::TZ)->format('H:i'))
            ->all();

        $now = Carbon::now(self::TZ);
        $slots = collect();

        foreach ($schedules as $schedule) {
            [$sh, $sm] = array_pad(explode(':', $schedule->start_time), 2, '0');
            [$eh, $em] = array_pad(explode(':', $schedule->end_time), 2, '0');

            $slotStart = $date->copy()->setTime((int) $sh, (int) $sm, 0);
            $windowEnd = $date->copy()->setTime((int) $eh, (int) $em, 0);
            $duration = $schedule->slot_duration_minutes ?: 30;

            while ($slotStart->copy()->addMinutes($duration)->lte($windowEnd)) {
                $blockedByVacation = $unavailabilities->contains(function ($u) use ($slotStart) {
                    if ($u->isFullDay()) {
                        return true;
                    }
                    $blockStart = Carbon::parse($u->start_time)->format('H:i');
                    $blockEnd = Carbon::parse($u->end_time)->format('H:i');

                    return $slotStart->format('H:i') >= $blockStart && $slotStart->format('H:i') < $blockEnd;
                });

                $isPast = $slotStart->lt($now);
                $isBooked = in_array($slotStart->format('H:i'), $bookedStarts, true);

                if (! $blockedByVacation && ! $isPast && ! $isBooked) {
                    $slots->push($slotStart->copy());
                }

                $slotStart->addMinutes($duration);
            }
        }

        return $slots->sortBy(fn ($s) => $s->timestamp)->values();
    }

    /**
     * Reserva un turno dentro de una transacción, revalidando disponibilidad. El índice único
     * (professional_id, starts_at) actúa como backstop final ante condiciones de carrera.
     */
    public static function bookSlot(User $patient, User $professional, Carbon $startsAt, string $bookedBy, ?string $notes = null): self
    {
        return DB::transaction(function () use ($patient, $professional, $startsAt, $bookedBy, $notes) {
            $startsAt = $startsAt->copy()->timezone(self::TZ);
            $dayName = array_search($startsAt->dayOfWeek, self::DAY_MAP, true);

            $schedule = ProfessionalSchedule::query()
                ->where('professional_id', $professional->id)
                ->where('day_of_week', $dayName)
                ->where('active', true)
                ->get()
                ->first(function ($s) use ($startsAt) {
                    [$sh, $sm] = array_pad(explode(':', $s->start_time), 2, '0');
                    [$eh, $em] = array_pad(explode(':', $s->end_time), 2, '0');
                    $windowStart = $startsAt->copy()->setTime((int) $sh, (int) $sm, 0);
                    $windowEnd = $startsAt->copy()->setTime((int) $eh, (int) $em, 0);

                    return $startsAt->gte($windowStart)
                        && $startsAt->copy()->addMinutes($s->slot_duration_minutes ?: 30)->lte($windowEnd);
                });

            if (! $schedule) {
                throw ValidationException::withMessages(['starts_at' => 'Ese horario no está disponible.']);
            }

            $stillAvailable = self::availableSlotsFor($professional, $startsAt->copy())
                ->contains(fn ($s) => $s->equalTo($startsAt));

            if (! $stillAvailable) {
                throw ValidationException::withMessages(['starts_at' => 'Ese horario ya fue tomado.']);
            }

            try {
                return self::create([
                    'patient_id' => $patient->id,
                    'professional_id' => $professional->id,
                    'specialty' => $professional->role,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMinutes($schedule->slot_duration_minutes ?: 30),
                    // Reservado por el paciente = ya confirmado (lo eligió él). Reservado por el
                    // admin = queda pendiente hasta que el paciente lo confirme por WhatsApp.
                    'status' => $bookedBy === 'patient' ? 'confirmed' : 'pending',
                    'booked_by' => $bookedBy,
                    'notes' => $notes,
                ]);
            } catch (QueryException $e) {
                $sqlState = $e->errorInfo[0] ?? null;
                $driverCode = $e->errorInfo[1] ?? null;

                if ($sqlState === '23000' || $driverCode === 1062) {
                    throw ValidationException::withMessages(['starts_at' => 'Ese horario ya fue tomado.']);
                }

                throw $e;
            }
        });
    }
}
