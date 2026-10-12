<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Rides and fun activities (design 5.1).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attractions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description');
            $table->enum('category', ['rollercoaster', 'water', 'kids', 'interactive']);
            $table->enum('thrill_level', ['low', 'medium', 'high', 'extreme']);
            // Suitability guidance shown to visitors. 0 means no limit.
            $table->unsignedTinyInteger('min_age')->default(0);
            $table->unsignedSmallInteger('min_height_cm')->default(0);
            // We never delete an attraction. Retired ones stay for history and analytics.
            $table->enum('status', ['active', 'retired'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attractions');
    }
};
