<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ticket tiers and prices (design 5.2). The pricing model lives in the database (FR-A-04).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_tiers', function (Blueprint $table) {
            $table->id();
            // INDIVIDUAL, COUPLE, FAMILY, GROUP. Bookings and analytics keep this code.
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->unsignedTinyInteger('min_guests');
            $table->unsignedTinyInteger('max_guests');
            // Price for the whole ticket, not per person.
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tiers');
    }
};
