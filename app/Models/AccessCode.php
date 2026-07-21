<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCode extends Model
{
    const UPDATED_AT = null; // pas de mise à jour, uniquement created_at

    protected $fillable = [
        'user_id', 'code_hash', 'expires_at', 'used_at', 'used_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function debiteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function consultePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function estValide(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }
}