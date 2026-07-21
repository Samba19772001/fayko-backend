<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Contrat extends Model
{
    use HasFactory;

    protected $fillable = [
        'preteur_id', 'emprunteur_id', 'montant', 'devise',
        'date_remise_fonds', 'date_echeance', 'taux_interet', 'garanties',
        'mode_remboursement', 'statut', 'frais_payes', 'pdf_url',
        'hash_document', 'conclu_at',
    ];

    protected function casts(): array
    {
        return [
            'date_remise_fonds' => 'date',
            'date_echeance' => 'date',
            'montant' => 'decimal:2',
            'taux_interet' => 'decimal:2',
            'frais_payes' => 'boolean',
            'conclu_at' => 'datetime',
        ];
    }

    public function preteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'preteur_id');
    }

    public function emprunteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emprunteur_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    public function remboursements(): HasMany
    {
        return $this->hasMany(Remboursement::class);
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Un contrat est "conclu" (§3.3) une fois les deux signatures apposées
     * ET les frais de 500 FCFA payés. Avant cela, `statut` est NULL.
     */
    public function estConclu(): bool
    {
        return $this->signatures()->count() === 2 && $this->frais_payes;
    }

    public function estEntierementRembourse(): bool
    {
        $totalConfirme = $this->remboursements()
            ->where('statut_confirmation', 'confirme')
            ->sum('montant');

        return bccomp((string) $totalConfirme, (string) $this->montant, 2) >= 0;
    }

    public function scopeEnRetardNonSignale(Builder $query): Builder
    {
        return $query->where('statut', 'actif')
            ->where('date_echeance', '<', now()->toDateString());
    }

    public function scopeEligiblesImpaye(Builder $query, int $joursDeGrace): Builder
    {
        return $query->where('statut', 'en_retard')
            ->where('date_echeance', '<', now()->subDays($joursDeGrace)->toDateString());
    }
}