<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('plan_id')
                ->constrained('subscription_plans')
                ->onDelete('cascade');
            $table->integer('max_properties');             // Copié du plan au moment de la souscription
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();   // null = illimité
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->foreignId('created_by')
                ->constrained('users');                    // L'admin qui a créé
            $table->text('notes')->nullable();             // Notes de l'admin
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};