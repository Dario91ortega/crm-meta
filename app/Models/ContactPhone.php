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
 * The `phone` value is stored as written (E.164 normalisation happens in
 * the service layer when matching across leads, not at persistence time).
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
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
