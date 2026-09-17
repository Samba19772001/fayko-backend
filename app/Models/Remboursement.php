<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Remboursement extends Model
{
    protected $fillable = [
        'contrat_id', 'montant', 'reference_transaction', 'declare_par', 'statut_confirmation', 'date_declaration',
    ];

    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_declaration' => 'datetime',
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class);
    }

    public function declarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declare_par');
    }
}