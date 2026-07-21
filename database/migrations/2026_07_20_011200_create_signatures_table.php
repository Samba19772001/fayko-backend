<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cahier des charges §5.3 - Table "signatures"
// Champ hash_document ajouté par rapport au tableau du §5.3 pour respecter
// littéralement le §3.5 : "hash SHA-256 du document au moment de la signature".
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('signed_at');
            $table->string('ip_address');
            $table->string('device_fingerprint');
            $table->boolean('otp_valide')->default(false);
            $table->string('hash_document'); // intégrité au moment T de la signature (§3.5)
            $table->timestamps();

            $table->unique(['contrat_id', 'user_id']); // une seule signature par partie et par contrat
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};