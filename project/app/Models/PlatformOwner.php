<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformOwner extends Model
{
    protected $fillable = [
        'user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isOwner(int $userId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }
}
