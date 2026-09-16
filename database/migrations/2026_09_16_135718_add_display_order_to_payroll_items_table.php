<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            // Ajouter la colonne seulement si elle n'existe pas déjà
            if (!Schema::hasColumn('payroll_items', 'displayOrder')) {
                $table->integer('displayOrder')->default(0)->after('isActive');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn('displayOrder');
        });
    }
};