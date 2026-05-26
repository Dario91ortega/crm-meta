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
