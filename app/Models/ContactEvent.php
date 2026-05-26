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
 *
 * @property int $id
 * @property int $agency_id
 * @property int $contact_id
 * @property int|null $user_id
 * @property string $eventable_type
 * @property int $eventable_id
 * @property \Illuminate\Support\Carbon|null $occurred_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Contact|null $contact
 * @property-read Model|\Eloquent $eventable
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\ContactEventFactory factory($count = null, $state = [])
 * @method static Builder<static>|ContactEvent newModelQuery()
 * @method static Builder<static>|ContactEvent newQuery()
 * @method static Builder<static>|ContactEvent query()
 * @method static Builder<static>|ContactEvent recent()
 * @method static Builder<static>|ContactEvent whereAgencyId($value)
 * @method static Builder<static>|ContactEvent whereContactId($value)
 * @method static Builder<static>|ContactEvent whereCreatedAt($value)
 * @method static Builder<static>|ContactEvent whereEventableId($value)
 * @method static Builder<static>|ContactEvent whereEventableType($value)
 * @method static Builder<static>|ContactEvent whereId($value)
 * @method static Builder<static>|ContactEvent whereOccurredAt($value)
 * @method static Builder<static>|ContactEvent whereUpdatedAt($value)
 * @method static Builder<static>|ContactEvent whereUserId($value)
 * @mixin \Eloquent
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
