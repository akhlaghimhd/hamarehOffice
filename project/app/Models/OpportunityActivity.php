<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\BelongsToCompany;

class OpportunityActivity extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'opportunity_id',
        'user_id',
        'type',
        'subject',
        'body',
        'happened_at',
    ];

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
