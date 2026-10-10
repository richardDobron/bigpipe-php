<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Every visitor of the demo is a tenant with their own playground: the records of a model that uses this trait
 * are limited to the tenant of the signed-in user, and new records belong to them.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query) {
            if ($tenant = Auth::user()?->tenant_id) {
                $query->where($query->getModel()->qualifyColumn('tenant_id'), $tenant);
            }
        });

        static::creating(function ($model) {
            $model->tenant_id ??= Auth::user()?->tenant_id;
        });
    }
}
