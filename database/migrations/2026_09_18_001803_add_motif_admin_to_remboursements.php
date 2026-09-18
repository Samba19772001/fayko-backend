<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Trace la justification de l'admin lorsqu'il tranche un litige (§3.6) :
// jamais une décision "silencieuse", toujours une raison écrite.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remboursements', function (Blueprint $table) {
            $table->text('motif_admin')->nullable()->after('statut_confirmation');
        });
    }

    public function down(): void
    {
        Schema::table('remboursements', function (Blueprint $table) {
            $table->dropColumn('motif_admin');
        });
    }
};