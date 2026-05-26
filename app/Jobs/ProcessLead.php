<?php

namespace App\Jobs;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Services\LeadContactResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Background job that turns a freshly created Lead into a linked Contact.
 *
 * Dispatched by LeadObserver after a Lead is committed to the database.
 * Webhook handlers must return < 5s, so all the dedup + side-effects work
 * happens here, asynchronously.
 *
 * Retries: 3 attempts with exponential backoff (1m / 5m / 15m).
 */
class ProcessLead implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Lead $lead)
    {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(LeadContactResolver $resolver): void
    {
        $this->lead->update(['status' => LeadStatus::Processing]);

        try {
            $contact = $resolver->resolve($this->lead);
            $this->lead->markProcessed($contact);
        } catch (Throwable $e) {
            $this->lead->markFailed();
            report($e);
            throw $e;
        }
    }
}
