<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_items', 'taxStatus')) {
                // 'taxable' ou 'non_taxable', par défaut 'taxable'
                $table->string('taxStatus')->default('taxable')->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn('taxStatus');
        });
    }
};