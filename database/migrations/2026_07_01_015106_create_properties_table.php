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
    Schema::create('properties', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->text('description');
        $table->enum('type', ['room', 'apartment', 'house', 'studio']);
        $table->enum('listing_type', ['rent', 'sale']);
        $table->decimal('price', 10, 2);
        $table->string('city');
        $table->string('address');
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->integer('rooms')->default(1);
        $table->integer('bathrooms')->default(1);
        $table->decimal('area', 8, 2)->nullable();
        $table->json('amenities')->nullable();
        $table->enum('status', ['active', 'rented', 'sold', 'pending'])->default('pending');
        $table->boolean('is_furnished')->default(false);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
