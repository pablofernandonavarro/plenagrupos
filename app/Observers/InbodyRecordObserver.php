<?php

namespace App\Observers;

use App\Models\InbodyRecord;
use App\Services\EmbeddingSyncService;

class InbodyRecordObserver
{
    public function __construct(private EmbeddingSyncService $sync)
    {
    }

    public function created(InbodyRecord $record): void
    {
        $this->sync->sync('inbody_note', $record->user_id, $record->id, $record->notes);
    }

    public function updated(InbodyRecord $record): void
    {
        if ($record->wasChanged('notes')) {
            $this->sync->sync('inbody_note', $record->user_id, $record->id, $record->notes);
        }
    }

    public function deleted(InbodyRecord $record): void
    {
        $this->sync->sync('inbody_note', $record->user_id, $record->id, null);
    }
}
