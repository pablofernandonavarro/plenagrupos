<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\ProfessionalSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HolidayBlocksAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    private const DAY_NAMES = [
        0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles',
        4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
    ];

    public function test_holiday_empties_available_slots_and_blocks_booking(): void
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $date = Carbon::now($tz)->addDays(7)->startOfDay();
        $dayName = self::DAY_NAMES[$date->dayOfWeek];

        $professional = User::factory()->create(['role' => 'medico']);
        $patient = User::factory()->create(['role' => 'patient', 'plan' => 'descenso']);

        ProfessionalSchedule::create([
            'professional_id' => $professional->id,
            'day_of_week' => $dayName,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration_minutes' => 30,
            'active' => true,
        ]);

        $slotTime = $date->copy()->setTime(9, 0);

        $this->assertTrue(
            Appointment::availableSlotsFor($professional, $date)->contains(fn ($s) => $s->equalTo($slotTime)),
            'El horario debería estar disponible antes de cargar el feriado.'
        );

        Holiday::create(['date' => $date->toDateString(), 'name' => 'Feriado de prueba']);

        $this->assertTrue(
            Appointment::availableSlotsFor($professional, $date)->isEmpty(),
            'No debería haber horarios disponibles en un día feriado.'
        );

        $this->expectException(ValidationException::class);

        Appointment::bookSlot($patient, $professional, $slotTime, 'patient');
    }

    public function test_holiday_on_a_different_date_does_not_block_booking(): void
    {
        $tz = 'America/Argentina/Buenos_Aires';
        $date = Carbon::now($tz)->addDays(7)->startOfDay();
        $otherDate = Carbon::now($tz)->addDays(14)->startOfDay();
        $dayName = self::DAY_NAMES[$date->dayOfWeek];

        $professional = User::factory()->create(['role' => 'medico']);
        $patient = User::factory()->create(['role' => 'patient', 'plan' => 'descenso']);

        ProfessionalSchedule::create([
            'professional_id' => $professional->id,
            'day_of_week' => $dayName,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'slot_duration_minutes' => 30,
            'active' => true,
        ]);

        Holiday::create(['date' => $otherDate->toDateString(), 'name' => 'Feriado en otra fecha']);

        $slotTime = $date->copy()->setTime(9, 0);

        $appointment = Appointment::bookSlot($patient, $professional, $slotTime, 'patient');

        $this->assertSame($professional->id, $appointment->professional_id);
    }
}
