<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_balance_histories', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('movement_type', ['credit', 'debit']); // Crédit (ajout) ou Débit (retrait)
            $table->decimal('days_count', 5, 2); // Nombre de jours
            $table->decimal('balance_after', 5, 2); // Solde après ce mouvement
            $table->string('reason'); // Ex: "Dotation annuelle 2026", "Congé payé du 15 au 20 août"

            // FK vers employees
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // FK vers leaves - NULLABLE car un crédit initial n'est pas lié à un congé
            $table->unsignedBigInteger('leave_id')->nullable();

            // FK vers pay_periods
            $table->foreignId('pay_period_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });

        // Ajout de la FK vers leaves (nullable)
        Schema::table('leave_balance_histories', function (Blueprint $table) {
            $table->foreign('leave_id')->references('id')->on('leaves')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balance_histories');
    }
};
