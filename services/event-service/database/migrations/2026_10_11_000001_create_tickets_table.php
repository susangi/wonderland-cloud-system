<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Local read copy of confirmed bookings (design 5.5 and 7.5).
// Filled from TICKET_PURCHASE events, so gate check-in and ride scans keep working
// even when booking-service is down.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            // The booking reference is unique, so it is the primary key. This also blocks duplicate purchases.
            $table->string('booking_reference', 30)->primary();
            $table->string('user_id', 64)->index();
            $table->string('tier_code', 20);
            $table->unsignedTinyInteger('guests');
            $table->date('visit_date');
            $table->string('time_slot_label', 50);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
