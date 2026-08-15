<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'ideal_weight',
        'peso_piso',
        'peso_techo',
        'role',
        'plan',
        'fase_actual',
        'plan_start_date',
        'patient_status',
        'patient_status_at',
        'patient_status_note',
        'belonging_group_id',
        'birth_date',
        'gender',
        'height_cm',
        'personal_goal',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'plan_start_date'    => 'date',
            'birth_date'         => 'date',
            'patient_status_at'  => 'datetime',
            'password'           => 'hashed',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (User $user) {
            if (! $user->isForceDeleting()) {
                return;
            }
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            foreach ($user->inbodyRecords as $record) {
                if ($record->image_path) {
                    Storage::disk('public')->delete($record->image_path);
                }
            }
        });
    }

    /**
     * Returns [cycleStart, cycleEnd] for the patient's $days-long billing period containing
     * $date (defaults to now, $days defaults to 30). Falls back to $date's calendar month if
     * no plan_start_date is set.
     */
    public function currentPlanCycle(?Carbon $date = null, int $days = 30): array
    {
        $date = $date ?? now();

        if (! $this->plan_start_date) {
            return [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
        }

        $start = $this->plan_start_date->copy();
        // Step in $days increments (forward or backward) until the cycle containing $date is found.
        // plan_start_date can end up in the future relative to $date (e.g. a plan renewal scheduled
        // in advance), so this must handle both directions, not just forward.
        while ($start->copy()->addDays($days)->lte($date)) {
            $start->addDays($days);
        }
        while ($start->gt($date)) {
            $start->subDays($days);
        }

        return [$start->startOfDay(), $start->copy()->addDays($days - 1)->endOfDay()];
    }

    /**
     * Fase efectiva: `fase_actual` o, si el coordinador no definió una, el plan contratado.
     * Se usa para límites de asistencia (PlanRule), vista del coordinador e informes (IA).
     * El ciclo de 30 días sigue usando `plan_start_date` / facturación.
     */
    public function faseEfectiva(): ?string
    {
        return $this->fase_actual ?? $this->plan;
    }

    /**
     * true si la fase efectiva es mantenimiento (usa rango piso/techo).
     * false para descenso o fase sin definir (usa peso objetivo).
     */
    public function estaEnMantenimiento(): bool
    {
        return in_array($this->faseEfectiva(), ['mantenimiento', 'mantenimiento_pleno'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }

    public function isMedico(): bool
    {
        return $this->role === 'medico';
    }

    public function isNutricionista(): bool
    {
        return $this->role === 'nutricionista';
    }

    public function isProfessional(): bool
    {
        return $this->isMedico() || $this->isNutricionista();
    }

    public function professionalSchedules()
    {
        return $this->hasMany(ProfessionalSchedule::class, 'professional_id');
    }

    public function professionalUnavailabilities()
    {
        return $this->hasMany(ProfessionalUnavailability::class, 'professional_id');
    }

    public function appointmentsAsPatient()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function appointmentsAsProfessional()
    {
        return $this->hasMany(Appointment::class, 'professional_id');
    }

    /**
     * Turnos ya tomados (pendientes/confirmados/completados) en el ciclo vigente de $specialty
     * para el plan de este paciente (currentPlanCycle, con el largo configurado en
     * AppointmentRequirement). Mismos estados que el tope de reserva en Appointment::bookSlot(),
     * para que "cuántos ya tomó" sea consistente con "cuántos más puede tomar".
     */
    public function turnosThisMonth(string $specialty): int
    {
        $requirement = AppointmentRequirement::requirementFor($specialty, $this->plan);
        [$cycleStart, $cycleEnd] = $this->currentPlanCycle(null, $requirement->cycle_days);

        return $this->appointmentsAsPatient()
            ->where('specialty', $specialty)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->count();
    }

    /**
     * Estado del turno de $specialty en el ciclo actual: 'completed' (asistió),
     * 'scheduled' (reservado y confirmado por el paciente), 'pending' (reservado
     * por un admin, todavía espera que el paciente lo confirme por WhatsApp)
     * o 'none' (no reservó nada este ciclo).
     */
    public function monthlyTurnoState(string $specialty): string
    {
        $requirement = AppointmentRequirement::requirementFor($specialty, $this->plan);
        [$cycleStart, $cycleEnd] = $this->currentPlanCycle(null, $requirement->cycle_days);

        $hasCompleted = $this->appointmentsAsPatient()
            ->where('specialty', $specialty)
            ->where('status', 'completed')
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->exists();

        if ($hasCompleted) {
            return 'completed';
        }

        $hasPending = $this->appointmentsAsPatient()
            ->where('specialty', $specialty)
            ->where('status', 'pending')
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->exists();

        if ($hasPending) {
            return 'pending';
        }

        $hasScheduled = $this->appointmentsAsPatient()
            ->where('specialty', $specialty)
            ->where('status', 'confirmed')
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->exists();

        return $hasScheduled ? 'scheduled' : 'none';
    }

    /**
     * true si este paciente comparte un único cupo de turno entre médico y nutricionista
     * (hoy solo Mantenimiento Pleno) en vez de un requisito independiente por especialidad.
     */
    public function usesCombinedTurnoRequirement(): bool
    {
        return $this->plan === 'mantenimiento_pleno';
    }

    /**
     * Turnos ya tomados (de cualquiera de las 2 especialidades) en el ciclo combinado vigente.
     * Análogo a turnosThisMonth() pero sin filtrar por especialidad — para pacientes con
     * usesCombinedTurnoRequirement().
     */
    public function combinedTurnosThisMonth(): int
    {
        $requirement = AppointmentRequirement::combinedRequirementFor($this->plan);
        [$cycleStart, $cycleEnd] = $this->currentPlanCycle(null, $requirement->cycle_days);

        return $this->appointmentsAsPatient()
            ->whereIn('specialty', ['medico', 'nutricionista'])
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->count();
    }

    /**
     * Análogo a monthlyTurnoState() pero combinando ambas especialidades en un solo estado —
     * para pacientes con usesCombinedTurnoRequirement().
     */
    public function combinedMonthlyTurnoState(): string
    {
        $requirement = AppointmentRequirement::combinedRequirementFor($this->plan);
        [$cycleStart, $cycleEnd] = $this->currentPlanCycle(null, $requirement->cycle_days);

        $hasCompleted = $this->appointmentsAsPatient()
            ->whereIn('specialty', ['medico', 'nutricionista'])
            ->where('status', 'completed')
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->exists();

        if ($hasCompleted) {
            return 'completed';
        }

        $hasPending = $this->appointmentsAsPatient()
            ->whereIn('specialty', ['medico', 'nutricionista'])
            ->where('status', 'pending')
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->exists();

        if ($hasPending) {
            return 'pending';
        }

        $hasScheduled = $this->appointmentsAsPatient()
            ->whereIn('specialty', ['medico', 'nutricionista'])
            ->where('status', 'confirmed')
            ->whereBetween('starts_at', [$cycleStart, $cycleEnd])
            ->exists();

        return $hasScheduled ? 'scheduled' : 'none';
    }

    public function belongingGroup()
    {
        return $this->belongsTo(Group::class, 'belonging_group_id');
    }

    public function coordinatorGroups()
    {
        return $this->belongsToMany(Group::class, 'group_coordinator');
    }

    public function patientGroups()
    {
        return $this->belongsToMany(Group::class, 'group_patient')->withPivot(
            'joined_at',
            'left_at',
            'maintenance_weight',
            'join_source',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'first_device_user_agent',
        );
    }

    public function attendances()
    {
        return $this->hasMany(GroupAttendance::class);
    }

    public function weightRecords()
    {
        return $this->hasMany(WeightRecord::class);
    }

    public function inbodyRecords()
    {
        return $this->hasMany(InbodyRecord::class);
    }
}
