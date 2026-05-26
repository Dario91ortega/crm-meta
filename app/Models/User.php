<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Application user.
 *
 * Authenticates into the Filament admin panel and acts as the "causer" for
 * audit/activity logs. Roles managed via spatie/laravel-permission
 * (see RoleSeeder for the canonical list: admin, manager, sales).
 *
 * Sign-up flow:
 *   1. User registers and picks an agency (or hits an agency-scoped URL).
 *   2. `agency_id` is set but `approved_at` stays null.
 *   3. A manager of that agency approves → `approved_at` is filled.
 *   4. `isApproved()` becomes true and the user can access the panel.
 *
 * The legacy `name` column is kept for compatibility with Filament helpers
 * (make:filament-user, default display) and stays auto-synced from
 * first_name + last_name via a saving hook in booted().
 */
#[Fillable([
    'name',
    'first_name',
    'last_name',
    'email',
    'phone',
    'avatar',
    'password',
    'is_active',
    'agency_id',
    'approved_at',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            // Keep `name` in sync with first_name/last_name when at least one
            // of them is set. Filament's make:filament-user only fills `name`,
            // so we leave it alone in that path.
            if ($user->first_name !== null || $user->last_name !== null) {
                $user->name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null && $this->agency_id !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('admin') && $this->agency_id === null;
    }
}
