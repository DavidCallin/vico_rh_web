<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslip_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('payslip_templates', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('name')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payslip_templates', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};