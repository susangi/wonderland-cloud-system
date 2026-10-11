<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Single analytics fact table (design 5.5). Metabase and the admin monitoring view read it.
// One row per ticket purchase, gate check-in or ride use, each with its own timestamp (FR-M-01).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('park_events', function (Blueprint $table) {
            $table->id();
            $table->enum('event_type', ['TICKET_PURCHASE', 'GATE_CHECKIN', 'RIDE_USAGE']);
            $table->string('booking_reference', 30)->index();
            $table->string('user_id', 64)->nullable()->index();
            $table->string('tier_code', 20)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->unsignedTinyInteger('guests')->nullable();
            $table->unsignedBigInteger('attraction_id')->nullable();
            // Copied here so analytics do not need attraction-service.
            $table->string('attraction_name', 100)->nullable();
            // When it happened, in UTC.
            $table->dateTime('occurred_at')->index();
            // Honest labelling: rows made by the simulator are marked "simulated".
            $table->enum('source', ['live', 'simulated'])->default('live');
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('park_events');
    }
};
