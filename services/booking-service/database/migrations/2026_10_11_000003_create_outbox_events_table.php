<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Transactional outbox (design 5.3 and 7.3).
// Events that must reach event-service are saved here first, in the same transaction as the booking.
// If event-service is down the row stays unsent and "outbox:flush" sends it later.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
