<?php

namespace App\Concerns;

use App\Scopes\CurrentCompanyScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Wallo\FilamentCompanies\FilamentCompanies;

trait CompanyOwned
{
    public static function bootCompanyOwned(): void
    {
        static::creating(static function ($model) {
            if (empty($model->company_id)) {
                if (Auth::check() && Auth::user()->currentCompany) {
                    $model->company_id = Auth::user()->currentCompany->id;
                } else {
                    Log::info('CompanyOwned trait: No company_id set on model ' . get_class($model) . ' ' . $model->id);

                    throw new ModelNotFoundException('CompanyOwned trait: No company_id set on model ' . get_class($model) . ' ' . $model->id);
                }
            }
        });

        static::addGlobalScope(new CurrentCompanyScope);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }
}
