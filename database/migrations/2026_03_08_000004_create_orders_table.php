<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('foodtruck_id');
            $table->string('customer_name')->default('Customer');
            $table->json('items');
            $table->decimal('total', 8, 2)->default(0);
            $table->enum('status', [
                'pending',
                'accepted',
                'preparing',
                'prepared',
                'ready_for_pickup',
                'delivery',
                'done',
            ])->default('pending');
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('foodtruck_id')->references('id')->on('food_trucks')->cascadeOnDelete();
            $table->foreign('accepted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
