<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_department', function (Blueprint $table) {
            // FK vers roles (table créée par Spatie)
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            
            // FK vers departments
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            
            // Clé primaire composite
            $table->primary(['role_id', 'department_id']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_department');
    }
};