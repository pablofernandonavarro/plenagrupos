<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupAttendance;
use App\Models\User;
use App\Models\WeightRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@plena.com',
            'role' => 'admin',
            'password' => Hash::make('password'),
        ]);

        // Coordinators
        $coord1 = User::create([
            'name' => 'María González',
            'email' => 'maria@plena.com',
            'role' => 'coordinator',
            'phone' => '+54 9 11 1234-5678',
            'password' => Hash::make('password'),
        ]);

        $coord2 = User::create([
            'name' => 'Carlos Pérez',
            'email' => 'carlos@plena.com',
            'role' => 'coordinator',
            'phone' => '+54 9 11 8765-4321',
            'password' => Hash::make('password'),
        ]);

        // Patients
        $patientData = [
            ['Ana López', 'paciente1@plena.com'],
            ['Roberto Martínez', 'paciente2@plena.com'],
            ['Lucía Fernández', 'paciente3@plena.com'],
            ['Diego Torres', 'paciente4@plena.com'],
        ];

        $patients = [];
        foreach ($patientData as [$name, $email]) {
            $patients[] = User::create([
                'name' => $name,
                'email' => $email,
                'role' => 'patient',
                'patient_status' => 'active',
                'password' => Hash::make('password'),
            ]);
        }

        // Group with QR auto-generated
        $group = Group::create([
            'name' => 'Grupo Lunes — Mañana',
            'description' => 'Sesiones los lunes de 9:00 a 10:30 hs.',
            'admin_id' => $admin->id,
        ]);

        $group->coordinators()->attach([$coord1->id, $coord2->id]);
        foreach ($patients as $patient) {
            $group->patients()->attach($patient->id, [
                'joined_at' => now(),
                'join_source' => 'manual',
            ]);
        }

        // Sample attendance + weight records for first 2 patients
        $sampleWeights = [78.5, 92.0];
        $session = $group->findOrCreateSessionForDate(now()->subDays(7));
        foreach (array_slice($patients, 0, 2) as $i => $patient) {
            $attendance = GroupAttendance::create([
                'group_id' => $group->id,
                'group_session_id' => $session->id,
                'user_id' => $patient->id,
                'attended_at' => now()->subDays(7),
            ]);
            WeightRecord::create([
                'user_id' => $patient->id,
                'group_id' => $group->id,
                'attendance_id' => $attendance->id,
                'weight' => $sampleWeights[$i],
            ]);
        }

        $this->command->info('✓ Admin:        admin@plena.com / password');
        $this->command->info('✓ Coordinador:  maria@plena.com / password');
        $this->command->info('✓ Paciente:     paciente1@plena.com / password');
        $this->command->info('✓ QR del grupo: /grupo/'.$group->qr_token);
    }
}
