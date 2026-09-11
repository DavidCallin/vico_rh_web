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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->enum('contract_type', ['cdi', 'cdd', 'stage', 'interim', 'apprenticeship']);
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Null pour CDI
            $table->decimal('base_salary', 10, 2);
            $table->string('pdf_path')->nullable(); // Chemin vers le PDF signé
            
            // FK vers employees
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
