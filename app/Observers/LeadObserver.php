<?php

namespace App\Observers;

use App\Jobs\ProcessLead;
use App\Models\Lead;

class LeadObserver
{
    /**
     * Dispatch ProcessLead AFTER the surrounding DB transaction (if any)
     * commits, so the queue worker always sees a persisted Lead row.
     */
    public function created(Lead $lead): void
    {
        ProcessLead::dispatch($lead)->afterCommit();
    }
}
