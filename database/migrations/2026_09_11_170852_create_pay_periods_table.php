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
        Schema::create('pay_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month'); // 1-12
            $table->unsignedInteger('year');
            $table->enum('status', ['open', 'closed', 'archived'])->default('open');
            $table->date('opening_date');
            $table->date('closing_date')->nullable();
            $table->string('closure_type')->nullable();

            // Qui a clôturé (users existe maintenant)
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->unique(['month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_periods');
    }
};
