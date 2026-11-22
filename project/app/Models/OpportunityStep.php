<?php

namespace App\Models;

use App\Support\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpportunityStep extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'opportunity_id',
        'title',
        'due_on',
        'assignee_user_id',
        'status',
        'result',
        'closed_at',
        'is_current',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'closed_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OpportunityActivity::class, 'step_id');
    }
}
