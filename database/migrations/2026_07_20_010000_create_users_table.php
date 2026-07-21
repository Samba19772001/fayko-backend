<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cahier des charges §5.3 - Table "users"
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('telephone')->unique();
            $table->string('nom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('numero_cni')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('ville')->nullable();
            $table->string('cni_recto_url')->nullable();
            $table->string('cni_verso_url')->nullable();
            $table->string('selfie_url')->nullable();
            $table->enum('statut_verification', ['non_verifie', 'verifie'])->default('non_verifie');
            $table->string('mot_de_passe')->nullable();
            $table->timestamp('telephone_verifie_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
