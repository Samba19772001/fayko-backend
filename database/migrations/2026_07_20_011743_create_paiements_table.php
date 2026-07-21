<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cahier des charges §5.3 - Table "paiements" (frais de contrat, §3.4)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->decimal('montant', 10, 2)->default(200); // 200 FCFA
            $table->enum('operateur', ['wave', 'orange_money', 'free_money']);
            $table->string('reference_transaction')->nullable();
            $table->enum('statut', ['en_attente', 'reussi', 'echoue'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};