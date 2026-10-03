<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('payroll_item_id')->constrained()->onDelete('cascade');
            $table->boolean('isActive')->default(true);
            $table->integer('displayOrder')->default(0);
            $table->unique(['payslip_template_id', 'payroll_item_id'], 'tpl_item_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_template_items');
    }
};