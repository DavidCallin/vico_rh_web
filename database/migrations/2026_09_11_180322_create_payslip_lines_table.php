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
        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2); // Montant de la ligne
            $table->decimal('quantity', 8, 2)->default(1); // Quantité (ex: heures, jours)

            // FK vers payslips
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();

            // FK vers payroll_items (rubriques de paie)
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
    }
};
