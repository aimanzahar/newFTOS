<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_punch_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('foodtruck_id')->constrained('food_trucks')->cascadeOnDelete();
            $table->timestamp('punched_in_at');
            $table->timestamp('punched_out_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'punched_out_at']);
            $table->index(['foodtruck_id', 'punched_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_punch_cards');
    }
};
