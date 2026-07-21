<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cahier des charges §5.3 - Table "access_codes" (vérification de solvabilité, §3.7)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // débiteur concerné
            $table->string('code_hash'); // code à 6 chiffres, jamais stocké en clair
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_user_id')->nullable()->constrained('users'); // prêteur ayant consulté
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_codes');
    }
};