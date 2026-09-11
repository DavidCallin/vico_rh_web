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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->enum('leave_type', ['annual', 'sick', 'unpaid', 'maternity', 'paternity', 'family', 'other']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_count', 5, 2); // Nombre de jours (peut être décimal pour temps partiel)
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->date('validation_date')->nullable();
            $table->text('reason')->nullable();
            
            // FK vers employees (demandeur)
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            // FK réflexive vers employees (validateur) - nullable car pas encore validé
            $table->unsignedBigInteger('validator_id')->nullable();
            
            // FK vers pay_periods
            $table->foreignId('pay_period_id')->nullable()->constrained()->nullOnDelete();
            
            $table->timestamps();
        });
        
        // Ajout de la FK réflexive après la création de la table
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreign('validator_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
