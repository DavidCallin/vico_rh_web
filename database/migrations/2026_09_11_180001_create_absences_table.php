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
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['unjustified', 'medical', 'family_emergency', 'other']);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('proof_path')->nullable(); // Chemin vers le justificatif
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('validation_date')->nullable();
            
            // FK vers employees (absent)
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            // FK réflexive vers employees (validateur)
            $table->unsignedBigInteger('validator_id')->nullable();
            
            // FK vers pay_periods
            $table->foreignId('pay_period_id')->nullable()->constrained()->nullOnDelete();
            
            $table->timestamps();
        });
        
        // Ajout de la FK réflexive
        Schema::table('absences', function (Blueprint $table) {
            $table->foreign('validator_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
