<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Models\Scopes\BelongsToAgencyScope;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A contact request originated outside the CRM (Meta Lead Ads webhook,
 * manual entry, future channels) that needs to be linked to a Contact.
 *
 * The `payload` column stores the raw source payload as a snapshot — it is
 * immutable once persisted and used as the historical record of what the
 * platform reported, even if our parsing rules change later.
 *
 * The lifecycle is driven by the `status` enum:
 *   Pending → Processing → Processed | Failed | Skipped
 *
 * Background processing happens in App\Jobs\ProcessLead, dispatched by the
 * LeadObserver right after creation. Idempotency is enforced by the unique
 * index on (platform, platform_lead_id).
 */
#[Fillable([
    'agency_id',
    'contact_id',
    'platform',
    'platform_lead_id',
    'form_id',
    'ad_id',
    'campaign_id',
    'payload',
    'status',
    'captured_at',
    'processed_at',
])]
#[ScopedBy(BelongsToAgencyScope::class)]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => LeadStatus::class,
            'captured_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => LeadStatus::Pending->value,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'contact_id', 'processed_at'])
            ->logOnlyDirty();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function markProcessed(?Contact $contact): void
    {
        $this->update([
            'contact_id' => $contact?->id,
            'status' => $contact !== null ? LeadStatus::Processed : LeadStatus::Skipped,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(): void
    {
        $this->update([
            'status' => LeadStatus::Failed,
            'processed_at' => now(),
        ]);
    }
}
