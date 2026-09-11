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
        Schema::create('payslips', function (Blueprint $table) { 
            $table->id();
            $table->date('generation_date');
            $table->decimal('gross_salary', 12, 2); // Salaire brut
            $table->decimal('total_earnings', 12, 2); // Total des gains
            $table->decimal('total_deductions', 12, 2); // Total des retenues
            $table->decimal('net_salary', 12, 2); // Salaire net
            $table->enum('status', ['brouillon', 'valide', 'paye'])->default('brouillon');
            $table->date('payment_date')->nullable();
            
            // FK vers employees
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            // FK vers pay_periods
            $table->foreignId('pay_period_id')->constrained()->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
