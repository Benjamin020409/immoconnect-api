<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('invitation_codes', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique();
        $table->string('email')->nullable(); // optionnel : limiter à un email
        $table->boolean('is_used')->default(false);
        $table->foreignId('used_by')->nullable()->constrained('users');
        $table->foreignId('created_by')->constrained('users'); // l'admin
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_codes');
    }
};
