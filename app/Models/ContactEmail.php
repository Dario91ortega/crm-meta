<?php

namespace App\Models;

use Database\Factories\ContactEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Email address associated to a Contact.
 *
 * One contact may have many emails; one of them is marked as primary and
 * referenced from contacts.contact_email_id for quick access.
 *
 * Emails are stored lowercased to make equality checks reliable during the
 * lead-to-contact deduplication step (see LeadContactResolver).
 *
 * @property int $id
 * @property int $contact_id
 * @property string $email
 * @property string|null $label
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Contact|null $contact
 * @method static \Database\Factories\ContactEmailFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail whereContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactEmail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable(['contact_id', 'email', 'label', 'is_primary'])]
class ContactEmail extends Model
{
    /** @use HasFactory<ContactEmailFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'email' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ContactEmail $email): void {
            if ($email->email !== null) {
                $email->email = mb_strtolower($email->email);
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
