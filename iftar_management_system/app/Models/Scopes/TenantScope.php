<?php


namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\TenantContext;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if tenant is set
        if (!TenantContext::hasTenant()) {
            return;
        }

        $tenantId = TenantContext::getTenantId();

        // Auto-add WHERE masjid_id = ?
        $builder->where($model->getTable() . '.masjid_id', $tenantId);
    }
}