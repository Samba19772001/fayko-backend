<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cahier des charges §3.6, révisé : chaque remboursement déclaré doit
// pouvoir être associé à une référence de transaction vérifiable
// (mobile money ou virement), donnant à l'admin un vrai élément à
// vérifier en cas de litige, plutôt qu'une simple parole contre une autre.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remboursements', function (Blueprint $table) {
            $table->string('reference_transaction')->nullable()->after('montant');
        });
    }

    public function down(): void
    {
        Schema::table('remboursements', function (Blueprint $table) {
            $table->dropColumn('reference_transaction');
        });
    }
};