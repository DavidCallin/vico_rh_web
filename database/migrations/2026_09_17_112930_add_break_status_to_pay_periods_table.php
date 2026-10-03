<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_periods', function (Blueprint $table) {
            $table->enum('status', ['draft', 'open', 'break', 'closed'])->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pay_periods', function (Blueprint $table) {
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft')->change();
        });
    }
};