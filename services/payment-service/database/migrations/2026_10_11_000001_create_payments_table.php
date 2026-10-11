<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mock payment records (design 5.4).
// There is no column for the full card number or CVC on purpose. We keep only the last 4 digits.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id', 50)->unique();
            $table->string('booking_reference', 30)->index();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3);
            $table->char('card_last4', 4);
            $table->enum('status', ['SUCCESS', 'FAILED']);
            $table->string('failure_reason', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
