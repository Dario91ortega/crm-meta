<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts tenant-bound models to the agency of the currently authenticated user.
 *
 * Bypass paths:
 *   - No authenticated user (CLI, queue worker, tests) → scope returns early.
 *   - Authenticated user marked as super-admin (admin role + no agency_id)
 *     → scope returns early; super-admin sees all agencies.
 *
 * Users with an agency see only rows where the model's `agency_id` matches
 * their own. Users without an agency (not super-admin) match no rows because
 * `WHERE agency_id = NULL` is never true on tenant-bound tables (the column
 * is NOT NULL there).
 *
 * Manual bypass:
 *   Contact::withoutGlobalScope(BelongsToAgencyScope::class)->where(...)
 */
class BelongsToAgencyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        $builder->where($model->getTable().'.agency_id', $user->agency_id);
    }
}
