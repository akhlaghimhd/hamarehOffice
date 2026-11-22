<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartyContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'party_id',
        'company_id',
        'name',
        'title',
        'phone',
        'mobile',
        'email',
        'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
