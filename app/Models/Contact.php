<?php

namespace App\Models;

use App\Models\Scopes\BelongsToAgencyScope;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A person tracked by the CRM.
 *
 * A Contact belongs to one Agency (tenant) and is owned by one User (assignee).
 * Contacts have many phones and emails; one of each can be marked as the
 * "primary" via the contact_phone_id / contact_email_id FKs.
 *
 * Deduplication is scoped per agency: when a Lead arrives with an email/phone
 * already present on a Contact within the same agency, the existing Contact
 * is reused (see App\Services\LeadContactResolver).
 *
 * Tenant scoping is automatic via BelongsToAgencyScope; bypass with
 * `Contact::withoutGlobalScope(BelongsToAgencyScope::class)` when working
 * across agencies (e.g. background jobs, super-admin).
 *
 * @property int $id
 * @property int $agency_id
 * @property int $user_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $avatar
 * @property int|null $contact_phone_id
 * @property int|null $contact_email_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activitiesAsSubject
 * @property-read int|null $activities_as_subject_count
 * @property-read \App\Models\Agency|null $agency
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContactEmail> $emails
 * @property-read int|null $emails_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContactEvent> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lead> $leads
 * @property-read int|null $leads_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ContactPhone> $phones
 * @property-read int|null $phones_count
 * @property-read \App\Models\ContactEmail|null $primaryEmail
 * @property-read \App\Models\ContactPhone|null $primaryPhone
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\ContactFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereAgencyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereContactEmailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereContactPhoneId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'agency_id',
    'user_id',
    'first_name',
    'last_name',
    'avatar',
    'contact_phone_id',
    'contact_email_id',
])]
#[ScopedBy(BelongsToAgencyScope::class)]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'user_id', 'contact_phone_id', 'contact_email_id'])
            ->logOnlyDirty();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(ContactPhone::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(ContactEmail::class);
    }

    public function primaryPhone(): BelongsTo
    {
        return $this->belongsTo(ContactPhone::class, 'contact_phone_id');
    }

    public function primaryEmail(): BelongsTo
    {
        return $this->belongsTo(ContactEmail::class, 'contact_email_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ContactEvent::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
