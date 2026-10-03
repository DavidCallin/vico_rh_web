<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('payslips', function (Blueprint $table) {
            if (!Schema::hasColumn('payslips', 'file_path')) {
                $table->string('file_path')->nullable()->after('net_salary');
            }
        });
    }
    public function down(): void {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
