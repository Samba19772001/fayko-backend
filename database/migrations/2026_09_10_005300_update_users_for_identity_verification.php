<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ajoute un statut intermédiaire "en_attente" (§3.2 : upload de la CNI en
// attente de validation par un agent) et un flag pour distinguer les
// comptes administrateurs, qui accèdent au panneau de validation.
return new class extends Migration
{
    public function up(): void
    {
        // Modification directe de l'enum via SQL brut : évite la
        // dépendance à doctrine/dbal qu'exigerait Schema::table()->change().
        DB::statement("ALTER TABLE users MODIFY statut_verification ENUM('non_verifie', 'en_attente', 'verifie') NOT NULL DEFAULT 'non_verifie'");

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('statut_verification');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });

        DB::statement("ALTER TABLE users MODIFY statut_verification ENUM('non_verifie', 'verifie') NOT NULL DEFAULT 'non_verifie'");
    }
};