<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('booking_id')
                ->nullable()
                ->after('property_id')
                ->constrained('bookings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Booking::class);
            $table->dropColumn('booking_id');
        });
    }
};