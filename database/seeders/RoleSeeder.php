<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Seeds the canonical role catalog for the CRM.
 *
 * - admin   → full access (configures forms, users, integrations).
 * - manager → owns a team, sees all leads/deals belonging to its members.
 * - sales   → handles its own assigned leads/deals only.
 *
 * Permissions are deliberately not seeded here yet; they will be defined
 * alongside each domain entity (Lead, Deal, etc.) to avoid stale catalogs.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin', 'manager', 'sales'] as $name) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        // Convenience: promote the first user (the one created via
        // make:filament-user during bootstrap) to admin if they have no role yet.
        $firstUser = User::orderBy('id')->first();

        if ($firstUser !== null && ! $firstUser->hasAnyRole(Role::all())) {
            $firstUser->assignRole('admin');
        }
    }
}
