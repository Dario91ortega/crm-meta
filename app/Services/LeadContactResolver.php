<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use App\Models\Lead;
use App\Models\Scopes\BelongsToAgencyScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the Contact for a given Lead within the lead's agency.
 *
 * Strategy:
 *   1. Try to find an existing Contact in the same agency whose email matches
 *      the lead's payload email (case-insensitive).
 *   2. Fallback to phone match.
 *   3. If no match, create a new Contact (plus its primary email/phone rows)
 *      owned by the agency's default owner (first manager, falling back to
 *      first admin).
 *
 * Returns null if the lead payload has neither email nor phone — the caller
 * should treat this as LeadStatus::Skipped.
 */
class LeadContactResolver
{
    public function resolve(Lead $lead): ?Contact
    {
        $payload = $lead->payload ?? [];
        $email = isset($payload['email']) ? mb_strtolower(trim((string) $payload['email'])) : null;
        $phone = $this->normalizePhone($payload['phone'] ?? null);

        if (! $email && ! $phone) {
            return null;
        }

        $existing = $this->findExisting($lead->agency_id, $email, $phone);

        return $existing ?? $this->createContact($lead, $email, $phone);
    }

    /**
     * Strip every non-digit and cast to int for storage as unsignedBigInteger.
     * Returns null when the result has zero digits — caller treats that the
     * same as a missing phone.
     */
    protected function normalizePhone(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $raw);

        return $digits !== '' ? (int) $digits : null;
    }

    protected function findExisting(int $agencyId, ?string $email, ?int $phone): ?Contact
    {
        $query = Contact::query()
            ->withoutGlobalScope(BelongsToAgencyScope::class)
            ->where('agency_id', $agencyId);

        if ($email !== null) {
            $byEmail = (clone $query)
                ->whereHas('emails', fn ($q) => $q->where('email', $email))
                ->first();

            if ($byEmail !== null) {
                return $byEmail;
            }
        }

        if ($phone !== null) {
            return (clone $query)
                ->whereHas('phones', fn ($q) => $q->where('phone', $phone))
                ->first();
        }

        return null;
    }

    protected function createContact(Lead $lead, ?string $email, ?int $phone): Contact
    {
        $payload = $lead->payload ?? [];

        return DB::transaction(function () use ($lead, $email, $phone, $payload): Contact {
            $contact = Contact::create([
                'agency_id' => $lead->agency_id,
                'user_id' => $this->defaultOwnerId($lead->agency_id),
                'first_name' => $payload['first_name'] ?? null,
                'last_name' => $payload['last_name'] ?? null,
            ]);

            $primaryPhone = null;
            $primaryEmail = null;

            if ($phone !== null) {
                $primaryPhone = ContactPhone::create([
                    'contact_id' => $contact->id,
                    'phone' => $phone,
                    'is_primary' => true,
                ]);
            }

            if ($email !== null) {
                $primaryEmail = ContactEmail::create([
                    'contact_id' => $contact->id,
                    'email' => $email,
                    'is_primary' => true,
                ]);
            }

            if ($primaryPhone || $primaryEmail) {
                $contact->update([
                    'contact_phone_id' => $primaryPhone?->id,
                    'contact_email_id' => $primaryEmail?->id,
                ]);
            }

            return $contact;
        });
    }

    /**
     * Picks the user that will own newly created contacts within an agency.
     * Preference order: first active manager → first active admin →
     * any user in the agency (fallback for partially-seeded tenants).
     */
    protected function defaultOwnerId(int $agencyId): int
    {
        foreach (['manager', 'admin'] as $role) {
            $owner = User::query()
                ->where('agency_id', $agencyId)
                ->where('is_active', true)
                ->whereNotNull('approved_at')
                ->role($role)
                ->orderBy('id')
                ->first();

            if ($owner !== null) {
                return $owner->id;
            }
        }

        return User::query()
            ->where('agency_id', $agencyId)
            ->orderBy('id')
            ->firstOrFail()
            ->id;
    }
}
