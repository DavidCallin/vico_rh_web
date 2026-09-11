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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            //Identité
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F']);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            //Professionnel
            $table->date('hire_date');
            $table->string('site')->nullable();
            $table->enum('status', ['actif', 'demission', 'fin_contrat', 'periode_de_test'])->default('periode_de_test');

            //Relations metier
            $table->foreignId('position_id')->contrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
