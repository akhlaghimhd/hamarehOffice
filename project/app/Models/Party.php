<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'national_id',
        'economic_code',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function roles(): HasMany
    {
        return $this->hasMany(CompanyPartyRole::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }
}
