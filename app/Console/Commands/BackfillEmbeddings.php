<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\GroupAttendance;
use App\Models\InbodyRecord;
use App\Models\PatientEmbedding;
use App\Models\User;
use App\Models\WeightRecord;
use App\Services\EmbeddingSyncService;
use Illuminate\Console\Command;

class BackfillEmbeddings extends Command
{
    protected $signature = 'ai:backfill-embeddings {--dry-run : Muestra qué se generaría sin llamar a la IA}';
    protected $description = 'Genera embeddings para las notas de texto libre existentes que todavía no los tienen';

    private const RECORD_SOURCES = [
        ['type' => 'weight_note', 'model' => WeightRecord::class, 'patientField' => 'user_id', 'textField' => 'notes'],
        ['type' => 'inbody_note', 'model' => InbodyRecord::class, 'patientField' => 'user_id', 'textField' => 'notes'],
        ['type' => 'coordinator_note', 'model' => GroupAttendance::class, 'patientField' => 'user_id', 'textField' => 'coordinator_notes'],
        ['type' => 'appointment_note', 'model' => Appointment::class, 'patientField' => 'patient_id', 'textField' => 'notes'],
    ];

    private const USER_SOURCES = ['personal_goal', 'patient_status_note'];

    public function handle(EmbeddingSyncService $sync): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) $this->warn('-- DRY RUN: no se llamará a la IA ni se guardará nada --');

        $total = 0;

        foreach (self::RECORD_SOURCES as $source) {
            $existingIds = PatientEmbedding::where('source_type', $source['type'])->pluck('source_id');

            $rows = $source['model']::query()
                ->whereNotNull($source['textField'])
                ->where($source['textField'], '!=', '')
                ->whereNotIn('id', $existingIds)
                ->get();

            foreach ($rows as $row) {
                $this->line('  ' . ($isDryRun ? '[dry] ' : '') . "{$source['type']}#{$row->id}");

                if (! $isDryRun) {
                    $sync->sync($source['type'], $row->{$source['patientField']}, $row->id, $row->{$source['textField']});
                }

                $total++;
            }
        }

        foreach (self::USER_SOURCES as $field) {
            $existingIds = PatientEmbedding::where('source_type', $field)->pluck('source_id');

            $users = User::query()
                ->where('role', 'patient')
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->whereNotIn('id', $existingIds)
                ->get();

            foreach ($users as $user) {
                $this->line('  ' . ($isDryRun ? '[dry] ' : '') . "{$field}#{$user->id}");

                if (! $isDryRun) {
                    $sync->sync($field, $user->id, $user->id, $user->{$field});
                }

                $total++;
            }
        }

        $this->info("Resultado: {$total} embedding(s) " . ($isDryRun ? 'a generar' : 'generados') . '.');

        return self::SUCCESS;
    }
}
