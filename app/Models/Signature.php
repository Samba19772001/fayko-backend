<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    protected $fillable = [
        'contrat_id', 'user_id', 'signed_at', 'ip_address',
        'device_fingerprint', 'otp_valide', 'hash_document',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'otp_valide' => 'boolean',
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class);
    }

    public function signataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}