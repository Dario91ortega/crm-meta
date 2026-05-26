<?php

namespace App\Models;

use Database\Factories\AgencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant entity.
 *
 * Each Agency owns its own users, contacts and leads. Multi-tenancy is enforced
 * at the data layer via the `agency_id` foreign key on tenant-bound tables plus
 * a global scope on the corresponding models (BelongsToAgencyScope).
 *
 * The `slug` is used to build agency-scoped login URLs
 * (e.g. /agencies/{slug}/login) that pre-fill the agency context.
 */
#[Fillable(['name', 'slug', 'is_active'])]
class Agency extends Model
{
    /** @use HasFactory<AgencyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
