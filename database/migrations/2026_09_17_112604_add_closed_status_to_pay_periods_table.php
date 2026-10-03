<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modifier la colonne status pour accepter 'closed'
        Schema::table('pay_periods', function (Blueprint $table) {
            $table->enum('status', ['draft', 'open', 'closed', 'finalized'])->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pay_periods', function (Blueprint $table) {
            $table->enum('status', ['draft', 'open', 'finalized'])->default('draft')->change();
        });
    }
};