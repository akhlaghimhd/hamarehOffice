<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function (Model $model): void {
            if (! $model->getAttribute('company_id') && app()->bound('currentCompanyId')) {
                $model->setAttribute('company_id', app('currentCompanyId'));
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, int|string $companyId): Builder
    {
        return $query->where($query->getModel()->getTable().'.company_id', $companyId);
    }
}
