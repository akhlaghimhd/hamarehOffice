<?php

namespace App\Models;

use App\Support\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'organization_name',
        'product_name',
        'products',
        'industry',
        'industry_note',
        'contact_name',
        'contact_role',
        'contact_mobile',
        'contact_phone',
        'contact_email',
        'address',
        'application_need',
        'tirage',
        'budget_band',
        'purchase_timing',
        'customer_value_focus',
        'main_objection',
        'stage_id',
        'status',
        'lost_reason',
        'result_note',
        'owner_user_id',
        'assigned_to_user_id',
        'next_step',
        'next_step_due_on',
        'opening_note',
        'last_interaction_at',
        'party_id',
    ];

    protected function casts(): array
    {
        return [
            'products' => 'array',
            'next_step_due_on' => 'date',
            'last_interaction_at' => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class, 'stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(OpportunityStep::class);
    }

    public function currentStep(): HasOne
    {
        return $this->hasOne(OpportunityStep::class)->where('is_current', true)->where('status', 'open');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OpportunityActivity::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(OpportunityReferral::class);
    }
}
