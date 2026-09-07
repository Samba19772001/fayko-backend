<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cahier des charges §5.3 - Table "remboursements"
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrat_id')->constrained('contrats')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->foreignId('declare_par')->constrained('users'); // qui déclare (en général le prêteur)
            $table->enum('statut_confirmation', ['en_attente', 'confirme', 'conteste'])->default('en_attente');
            $table->timestamp('date_declaration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements');
    }
};
