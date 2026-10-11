<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Visitor bookings (design 5.3).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // WL-YYYYMMDD-XXXXXX. This is also the only thing inside the QR code.
            $table->string('booking_reference', 30)->unique();
            // Cognito "sub". Name and email stay in Cognito.
            $table->string('user_id', 64)->index();
            // Tier comes from pricing-service, so there is no foreign key across services.
            // We copy code, name and price, so later price changes do not change this booking.
            $table->unsignedBigInteger('tier_id');
            $table->string('tier_code', 20);
            $table->string('tier_name', 50);
            $table->unsignedTinyInteger('guests');
            $table->decimal('price_paid', 10, 2);
            $table->char('currency', 3);
            $table->date('visit_date')->index();
            $table->foreignId('time_slot_id')->constrained('time_slots');
            $table->enum('status', ['PENDING_PAYMENT', 'CONFIRMED', 'PAYMENT_FAILED', 'EXPIRED']);
            $table->string('payment_id', 50)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            // Makes the capacity check fast: guests booked for one date and slot.
            $table->index(['visit_date', 'time_slot_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
