<?php

namespace App\Observers;

use App\Models\WeightRecord;
use App\Services\EmbeddingSyncService;

class WeightRecordObserver
{
    public function __construct(private EmbeddingSyncService $sync)
    {
    }

    public function created(WeightRecord $record): void
    {
        $this->sync->sync('weight_note', $record->user_id, $record->id, $record->notes);
    }

    public function updated(WeightRecord $record): void
    {
        if ($record->wasChanged('notes')) {
            $this->sync->sync('weight_note', $record->user_id, $record->id, $record->notes);
        }
    }

    public function deleted(WeightRecord $record): void
    {
        $this->sync->sync('weight_note', $record->user_id, $record->id, null);
    }
}
