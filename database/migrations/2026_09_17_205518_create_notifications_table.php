<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Destinataire
            $table->string('type'); // leave_request, absence_request, contract_expiring, contract_expired
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable(); // URL pour accéder à la ressource
            $table->json('data')->nullable(); // Données supplémentaires (JSON)
            $table->boolean('is_read')->default(false);
            $table->boolean('email_sent')->default(false); // Trace si l'email a été envoyé
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};