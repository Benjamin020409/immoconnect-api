<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // Gratuit, Basic, Pro, Premium
            $table->integer('max_properties');             // 2, 5, 15, -1 (illimité)
            $table->integer('duration_days');              // 0=illimité, 30, 90, 365
            $table->decimal('price', 10, 2)->default(0);  // Prix en FCFA
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};