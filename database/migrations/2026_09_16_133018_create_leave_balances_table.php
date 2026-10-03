<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->integer('year'); // Année de référence (ex: 2026)
            $table->decimal('total_days', 5, 2)->default(25); // Droit annuel (25 jours en France)
            $table->decimal('taken_days', 5, 2)->default(0); // Jours déjà pris
            $table->decimal('remaining_days', 5, 2)->default(25); // Jours restants (calculé)
            $table->decimal('pending_days', 5, 2)->default(0); // Jours en attente de validation
            $table->unique(['employee_id', 'year']); // Un seul solde par employé et par an
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};