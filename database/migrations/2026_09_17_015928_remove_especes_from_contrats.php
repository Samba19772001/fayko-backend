<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Retire "especes" des modes de remboursement possibles : un paiement en
// espèces ne laisse aucune trace vérifiable, ce qui rend impossible tout
// arbitrage en cas de litige (§3.6). Seuls mobile_money et virement
// restent, tous deux traçables via une référence de transaction.
return new class extends Migration
{
    public function up(): void
    {
        // On convertit d'abord les éventuels contrats existants en
        // "espèces" vers mobile_money, pour ne pas casser l'enum ensuite.
        DB::table('contrats')->where('mode_remboursement', 'especes')->update(['mode_remboursement' => 'mobile_money']);

        DB::statement("ALTER TABLE contrats MODIFY mode_remboursement ENUM('mobile_money', 'virement') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE contrats MODIFY mode_remboursement ENUM('especes', 'mobile_money', 'virement') NOT NULL");
    }
};