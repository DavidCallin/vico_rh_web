<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslip_lines', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable();
            $table->unique(['employee_id', 'pay_period_id']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->string('reminder_stage')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'pay_period_id']);
            $table->dropColumn('delivered_at');
        });

        Schema::table('payslip_lines', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['reminder_stage', 'last_reminded_at']);
        });
    }
};
