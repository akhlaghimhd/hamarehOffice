<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Support\BelongsToCompany;

class CompanyPartyRole extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'party_id',
        'role',
        'local_code',
        'is_active',
        'account_owner_id',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_owner_id');
    }
}
