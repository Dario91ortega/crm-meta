<?php

namespace App\Models;

use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Plain-text note authored by a user.
 *
 * Notes live as a polymorphic target of ContactEvent (eventable). They do not
 * carry a direct contact_id; the link is via the event. This lets us reuse
 * Note as a target of other timelines in the future (Deal, Lead, etc.)
 * without schema changes.
 */
#[Fillable(['user_id', 'body'])]
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['body'])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): MorphOne
    {
        return $this->morphOne(ContactEvent::class, 'eventable');
    }
}
