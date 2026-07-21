<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'telephone', 'nom', 'prenom', 'numero_cni', 'date_naissance', 'ville',
        'cni_recto_url', 'cni_verso_url', 'selfie_url', 'statut_verification',
        'mot_de_passe',
    ];

    protected $hidden = [
        'mot_de_passe', 'numero_cni', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'numero_cni' => 'encrypted', // chiffré au repos (§4.1)
            'date_naissance' => 'date',
            'telephone_verifie_at' => 'datetime',
        ];
    }

    // Laravel utilise "password" pour l'auth par défaut ; on l'aliase sur mot_de_passe.
    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function estVerifie(): bool
    {
        return $this->statut_verification === 'verifie';
    }

    public function contratsEnTantQuePreteur(): HasMany
    {
        return $this->hasMany(Contrat::class, 'preteur_id');
    }

    public function contratsEnTantQuEmprunteur(): HasMany
    {
        return $this->hasMany(Contrat::class, 'emprunteur_id');
    }

    public function accessCodes(): HasMany
    {
        return $this->hasMany(AccessCode::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }
}