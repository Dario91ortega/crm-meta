<?php

namespace App\Models;

use Database\Factories\ContactPhoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phone number associated to a Contact.
 *
 * One contact may have many phones; one of them is marked as primary and
 * referenced from contacts.contact_phone_id for quick access.
 *
 * The `phone` column is `unsignedBigInteger`: only digits, no `+`, no
 * formatting. Callers must strip non-digits before persisting or matching
 * (LeadContactResolver does this when processing inbound webhook payloads).
 *
 * @property int $id
 * @property int $contact_id
 * @property string $phone
 * @property string|null $label
 * @property bool $is_primary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Contact|null $contact
 * @method static \Database\Factories\ContactPhoneFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone whereContactId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone whereIsPrimary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactPhone whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable(['contact_id', 'phone', 'label', 'is_primary'])]
class ContactPhone extends Model
{
    /** @use HasFactory<ContactPhoneFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'phone' => 'integer',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
