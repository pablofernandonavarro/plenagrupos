<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Command;

class PurgeSoftDeleted extends Command
{
    protected $signature = 'purge:soft-deleted {--days=30 : Días en la papelera antes de purgar} {--dry-run : Muestra qué se borraría sin hacerlo}';
    protected $description = 'Elimina definitivamente los usuarios y grupos en la papelera hace más de N días';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        if ($isDryRun) {
            $this->warn('-- DRY RUN: no se borrará nada --');
        }

        foreach ([User::class => 'usuarios', Group::class => 'grupos'] as $model => $label) {
            $trashed = $model::onlyTrashed()->where('deleted_at', '<=', $cutoff)->get();

            foreach ($trashed as $record) {
                $this->line('  ' . ($isDryRun ? '[dry] ' : '') . "Purgar {$label}: {$record->name} (papelera desde {$record->deleted_at->format('d/m/Y')})");

                if (! $isDryRun) {
                    $record->forceDelete();
                }
            }

            $this->info(ucfirst($label) . " purgados: {$trashed->count()}");
        }

        return self::SUCCESS;
    }
}
