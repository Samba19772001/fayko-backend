<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cahier des charges §5.3 - Table "contrats"
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preteur_id')->constrained('users');
            $table->foreignId('emprunteur_id')->constrained('users');
            $table->decimal('montant', 12, 2);
            $table->string('devise', 8)->default('FCFA');
            $table->date('date_remise_fonds');
            $table->date('date_echeance');
            $table->decimal('taux_interet', 5, 2)->nullable(); // encadré légalement (§4.2 / §8)
            $table->text('garanties')->nullable();
            $table->enum('mode_remboursement', ['especes', 'mobile_money', 'virement']);

            // NB : le cahier des charges (§3.3) précise qu'un contrat n'est "conclu"
            // qu'une fois les deux signatures apposées ET les frais payés. `statut`
            // reste donc NULL ("en attente de signature") jusqu'à la conclusion ;
            // les 5 statuts du §3.6 ne s'appliquent qu'après conclusion.
            $table->enum('statut', ['actif', 'en_retard', 'impaye', 'solde', 'litige'])->nullable();

            $table->boolean('frais_payes')->default(false); // §3.4
            $table->string('pdf_url')->nullable();
            $table->string('hash_document')->nullable(); // SHA-256 du contrat final
            $table->timestamp('conclu_at')->nullable();
            $table->timestamps();

            $table->index(['statut']);
            $table->index(['date_echeance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrats');
    }
};