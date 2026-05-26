<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAgencyScope;
use Database\Factories\ContactEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Item on a Contact's activity timeline.
 *
 * Polymorphic: each event points to a concrete detail model via
 * (eventable_type, eventable_id). The current target is Note; future
 * targets will be Email, WhatsappMessage, Call, SmsMessage, etc.
 *
 * agency_id is denormalised here (mirrors the parent Contact) to make
 * tenant filtering cheap without joining through contacts.
 */
#[Fillable([
    'agency_id',
    'contact_id',
    'user_id',
    'eventable_type',
    'eventable_id',
    'occurred_at',
])]
#[ScopedBy(BelongsToAgencyScope::class)]
class ContactEvent extends Model
{
    /** @use HasFactory<ContactEventFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('occurred_at')->orderByDesc('created_at');
    }
}
